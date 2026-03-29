package main

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"regexp"
	"time"
)

func handleGetSupportTickets(w http.ResponseWriter, r *http.Request) {
	filterStatus := r.URL.Query().Get("status")
	query := `
		SELECT st.id_ticket, st.ticket_number, st.title, st.description, st.category, st.priority, st.status,
		       st.id_user, st.assigned_to, st.created_at, st.updated_at, st.resolved_at, st.resolution_notes,
		       u.first_name, u.last_name, u.email,
		       a.first_name as assigned_first, a.last_name as assigned_last
		FROM support_tickets st
		INNER JOIN users u ON st.id_user = u.id_user
		LEFT JOIN users a ON st.assigned_to = a.id_user
	`
	args := []interface{}{}
	if filterStatus != "" && filterStatus != "tous" {
		query += " WHERE st.status = ?"
		args = append(args, filterStatus)
	}
	query += " ORDER BY st.priority DESC, st.created_at DESC"
	rows, err := db.Query(query, args...)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des tickets", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type ticketData struct {
		ID            string     `json:"id_ticket"`
		TicketNumber  string     `json:"ticket_number"`
		Title         string     `json:"title"`
		Description   string     `json:"description"`
		Category      *string    `json:"category,omitempty"`
		Priority      string     `json:"priority"`
		Status        string     `json:"status"`
		IDUser        string     `json:"id_user"`
		AssignedTo    *string    `json:"assigned_to,omitempty"`
		CreatedAt     time.Time  `json:"created_at"`
		UpdatedAt     *time.Time `json:"updated_at,omitempty"`
		ResolvedAt    *time.Time `json:"resolved_at,omitempty"`
		Resolution    *string    `json:"resolution_notes,omitempty"`
		FirstName     string     `json:"first_name"`
		LastName      string     `json:"last_name"`
		Email         string     `json:"email"`
		AssignedFirst *string    `json:"assigned_first,omitempty"`
		AssignedLast  *string    `json:"assigned_last,omitempty"`
	}
	items := []ticketData{}
	for rows.Next() {
		var item ticketData
		var category, assignedTo, resolution, assignedFirst, assignedLast sql.NullString
		var updatedAt, resolvedAt sql.NullTime
		if err := rows.Scan(&item.ID, &item.TicketNumber, &item.Title, &item.Description, &category, &item.Priority, &item.Status,
			&item.IDUser, &assignedTo, &item.CreatedAt, &updatedAt, &resolvedAt, &resolution,
			&item.FirstName, &item.LastName, &item.Email, &assignedFirst, &assignedLast); err != nil {
			continue
		}
		if category.Valid {
			item.Category = &category.String
		}
		if assignedTo.Valid {
			item.AssignedTo = &assignedTo.String
		}
		if resolution.Valid {
			item.Resolution = &resolution.String
		}
		if updatedAt.Valid {
			item.UpdatedAt = &updatedAt.Time
		}
		if resolvedAt.Valid {
			item.ResolvedAt = &resolvedAt.Time
		}
		if assignedFirst.Valid {
			item.AssignedFirst = &assignedFirst.String
		}
		if assignedLast.Valid {
			item.AssignedLast = &assignedLast.String
		}
		items = append(items, item)
	}
	json.NewEncoder(w).Encode(items)
}

func handleCreateSupportTicket(w http.ResponseWriter, r *http.Request) {
	var payload struct {
		Title       string `json:"title"`
		Description string `json:"description"`
		Category    string `json:"category"`
		Priority    string `json:"priority"`
		IDUser      string `json:"id_user"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	year := time.Now().Year()
	var last sql.NullString
	if err := db.QueryRow("SELECT ticket_number FROM support_tickets WHERE ticket_number LIKE ? ORDER BY ticket_number DESC LIMIT 1", fmt.Sprintf("TKT-%d-%%", year)).Scan(&last); err != nil && err != sql.ErrNoRows {
		jsonError(w, "Erreur lors de la génération du numéro", http.StatusInternalServerError)
		return
	}
	seq := 1
	if last.Valid {
		re := regexp.MustCompile(fmt.Sprintf(`TKT-%d-(\d{4})`, year))
		if m := re.FindStringSubmatch(last.String); len(m) == 2 {
			fmt.Sscanf(m[1], "%d", &seq)
			seq++
		}
	}
	ticketNumber := fmt.Sprintf("TKT-%d-%04d", year, seq)
	_, err := db.Exec(`
		INSERT INTO support_tickets 
		(id_ticket, ticket_number, title, description, category, priority, status, id_user, created_at)
		VALUES (CONCAT('tkt_', UUID()), ?, ?, ?, ?, ?, 'Ouvert', ?, NOW())
	`, ticketNumber, payload.Title, payload.Description, payload.Category, payload.Priority, payload.IDUser)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Ticket créé"})
}

func handleUpdateSupportTicket(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	var payload struct {
		Title       string  `json:"title"`
		Description string  `json:"description"`
		Priority    string  `json:"priority"`
		Status      string  `json:"status"`
		AssignedTo  *string `json:"assigned_to"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	_, err := db.Exec(`
		UPDATE support_tickets 
		SET title=?, description=?, priority=?, status=?, assigned_to=?, updated_at=NOW()
		WHERE id_ticket=?
	`, payload.Title, payload.Description, payload.Priority, payload.Status, payload.AssignedTo, id)
	if err != nil {
		jsonError(w, "Erreur lors de la mise à jour", http.StatusInternalServerError)
		return
	}
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Ticket mis à jour"})
}

func handleResolveSupportTicket(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	var payload struct {
		ResolutionNotes string `json:"resolution_notes"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	_, err := db.Exec(`
		UPDATE support_tickets 
		SET status='Fermé', resolved_at=NOW(), resolution_notes=?, updated_at=NOW()
		WHERE id_ticket=?
	`, payload.ResolutionNotes, id)
	if err != nil {
		jsonError(w, "Erreur lors de la résolution", http.StatusInternalServerError)
		return
	}
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Ticket fermé"})
}

func handleDeleteSupportTicket(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	_, err := db.Exec("DELETE FROM support_tickets WHERE id_ticket=?", id)
	if err != nil {
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}
	w.WriteHeader(http.StatusNoContent)
}
