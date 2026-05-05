package main

import (
	"database/sql"
	"encoding/json"
	"io"
	"net/http"
	"strings"
)

// notification tout

type Notification struct {
	ID          string  `json:"id_notification"`
	Type        string  `json:"type"`
	Title       string  `json:"title"`
	Message     string  `json:"message"`
	CreatedAt   string  `json:"created_at"`
	ScheduledAt *string `json:"scheduled_at,omitempty"`
	IsRead      bool    `json:"is_read"`
	LimitedAt   *string `json:"limited_at,omitempty"`
	FirstName   *string `json:"first_name,omitempty"`
	LastName    *string `json:"last_name,omitempty"`
	Recipients  *string `json:"recipients,omitempty"`
}

func handleGetNotifications(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT n.id_notification, n.type, n.title, n.message, n.created_at, n.scheduled_at, n.is_read, n.limited_at, u.first_name, u.last_name, n.recipients
		FROM notifications n
		LEFT JOIN users u ON n.id_user = u.id_user
		ORDER BY n.created_at DESC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des notifications", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	notifications := []Notification{}
	for rows.Next() {
		var n Notification
		var firstName, lastName sql.NullString
		var limitedAt, scheduledAt sql.NullString
		var recipients sql.NullString
		var isReadInt int
		err := rows.Scan(&n.ID, &n.Type, &n.Title, &n.Message, &n.CreatedAt, &scheduledAt, &isReadInt, &limitedAt, &firstName, &lastName, &recipients)
		if err != nil {
			continue
		}
		n.IsRead = isReadInt != 0
		if recipients.Valid {
			n.Recipients = &recipients.String
		}
		if scheduledAt.Valid {
			n.ScheduledAt = &scheduledAt.String
		}
		if limitedAt.Valid {
			n.LimitedAt = &limitedAt.String
		}
		if firstName.Valid {
			n.FirstName = &firstName.String
		}
		if lastName.Valid {
			n.LastName = &lastName.String
		}
		notifications = append(notifications, n)
	}
	if err := rows.Err(); err != nil {
		jsonError(w, "Erreur lors de la lecture des notifications", http.StatusInternalServerError)
		return
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(notifications)
}

func handleGetUserNotifications(w http.ResponseWriter, r *http.Request) {
	userID := r.URL.Query().Get("id_user")
	if userID == "" {
		userID = r.PathValue("id")
	}
	if userID == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	var role string
	err := db.QueryRow("SELECT role FROM users WHERE id_user = ?", userID).Scan(&role)
	if err != nil {
		jsonError(w, "Utilisateur introuvable", http.StatusNotFound)
		return
	}

	recipient := strings.ToLower(strings.TrimSpace(role))
	if recipient == "prestataire" {
		recipient = "provider"
	} else if recipient == "administrateur" {
		recipient = "admin"
	}

	rows, err := db.Query(`
		SELECT n.id_notification, n.type, n.title, n.message, n.created_at, n.scheduled_at, n.is_read, n.limited_at, u.first_name, u.last_name, n.recipients
		FROM notifications n
		LEFT JOIN users u ON n.id_user = u.id_user
		WHERE n.id_user = ? 
			OR n.recipients = 'all'
		   	OR n.recipients = ?
		ORDER BY n.created_at DESC
	`, userID, recipient)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des notifications utilisateur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	notifications := []Notification{}
	for rows.Next() {
		var n Notification
		var firstName, lastName sql.NullString
		var limitedAt, scheduledAt sql.NullString
		var recipients sql.NullString
		var isReadInt int
		err := rows.Scan(&n.ID, &n.Type, &n.Title, &n.Message, &n.CreatedAt, &scheduledAt, &isReadInt, &limitedAt, &firstName, &lastName, &recipients)
		if err != nil {
			continue
		}
		n.IsRead = isReadInt != 0
		if scheduledAt.Valid {
			n.ScheduledAt = &scheduledAt.String
		}
		if limitedAt.Valid {
			n.LimitedAt = &limitedAt.String
		}
		if firstName.Valid {
			n.FirstName = &firstName.String
		}
		if lastName.Valid {
			n.LastName = &lastName.String
		}
		if recipients.Valid {
			n.Recipients = &recipients.String
		}
		notifications = append(notifications, n)
	}
	if err := rows.Err(); err != nil {
		jsonError(w, "Erreur lors de la lecture des notifications utilisateur", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(notifications)
}

// PROBLEME COUNT
func handleGetProblemeCount(w http.ResponseWriter, r *http.Request) {
	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM notifications").Scan(&count)
	if err != nil {
		jsonError(w, "Erreur lors du comptage des notifications", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"count": count,
	})
}

// CREATE NOTIFICATION
func handleCreateNotification(w http.ResponseWriter, r *http.Request) {
	body, err := io.ReadAll(r.Body)
	if err != nil {
		jsonError(w, "Impossible de lire la requête", http.StatusBadRequest)
		return
	}

	var payload struct {
		Type        string  `json:"type"`
		Title       string  `json:"title"`
		Message     string  `json:"message"`
		Recipients  string  `json:"recipients"`
		ScheduledAt *string `json:"scheduled_at"`
		LimitedAt   *string `json:"limited_at"`
	}

	if err := json.Unmarshal(body, &payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	if payload.Type == "" || payload.Title == "" || payload.Message == "" {
		jsonError(w, "Champs obligatoires manquants", http.StatusUnprocessableEntity)
		return
	}

	stmt, err := db.Prepare(`
		INSERT INTO notifications (id_notification, type, title, message, created_at, scheduled_at, is_read, limited_at, id_user, recipients)
		VALUES (UUID(), ?, ?, ?, NOW(), ?, FALSE, ?, NULL, ?)
	`)
	if err != nil {
		jsonError(w, "Erreur interne", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	_, err = stmt.Exec(payload.Type, payload.Title, payload.Message, payload.ScheduledAt, payload.LimitedAt, payload.Recipients)
	if err != nil {
		createLog("système", "Création de notification", "CREATE", "Erreur lors de la création: "+err.Error(), false)
		jsonError(w, "Erreur lors de la création de la notification", http.StatusInternalServerError)
		return
	}

	createLog("système", "Création de notification", "CREATE", "Notification créée: "+payload.Title, true)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{"success": true})
}

// DELETE NOTIFICATION
func handleDeleteNotification(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	stmt, err := db.Prepare("DELETE FROM notifications WHERE id_notification = ?")
	if err != nil {
		jsonError(w, "Erreur interne", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	result, err := stmt.Exec(id)
	if err != nil {
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}

	rowsAffected, _ := result.RowsAffected()
	if rowsAffected == 0 {
		createLog("système", "Suppression de notification", "DELETE", "Notification non trouvée: "+id, false)
		jsonError(w, "Notification non trouvée", http.StatusNotFound)
		return
	}

	createLog("système", "Suppression de notification", "DELETE", "Notification supprimée: "+id, true)

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{"success": true})
}
