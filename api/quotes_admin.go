package main

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"regexp"
	"time"
)

func handleGetAdminQuotes(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT q.id_quote, q.quote_number, q.amount_incl_tax, q.created_at, q.status,
		       u.first_name, u.last_name,
		       st.name as prestation_name
		FROM quotes q
		JOIN service_requests sr ON q.id_request = sr.id_request
		JOIN users u ON sr.id_user = u.id_user
		LEFT JOIN show_type sht ON sr.id_request = sht.id_request
		LEFT JOIN service_types st ON sht.id_service_type = st.id_service_type
		ORDER BY q.created_at DESC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des devis", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type adminQuote struct {
		ID             string    `json:"id_quote"`
		QuoteNumber    string    `json:"quote_number"`
		Amount         float64   `json:"amount"`
		CreatedAt      time.Time `json:"created_at"`
		Status         string    `json:"status"`
		FirstName      string    `json:"first_name"`
		LastName       string    `json:"last_name"`
		PrestationName *string   `json:"prestation_name,omitempty"`
	}

	quotes := []adminQuote{}
	for rows.Next() {
		var quote adminQuote
		var prestationName sql.NullString
		if err := rows.Scan(&quote.ID, &quote.QuoteNumber, &quote.Amount, &quote.CreatedAt, &quote.Status, &quote.FirstName, &quote.LastName, &prestationName); err != nil {
			continue
		}
		if prestationName.Valid {
			quote.PrestationName = &prestationName.String
		}
		quotes = append(quotes, quote)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(quotes)
}

func handleCreateAdminQuote(w http.ResponseWriter, r *http.Request) {
	var payload struct {
		IDUser        string  `json:"id_user"`
		ServiceTypeID string  `json:"id_service_type"`
		Amount        float64 `json:"amount"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	if payload.IDUser == "" || payload.ServiceTypeID == "" || payload.Amount <= 0 {
		jsonError(w, "Champs requis manquants", http.StatusBadRequest)
		return
	}

	tx, err := db.Begin()
	if err != nil {
		jsonError(w, "Erreur transaction", http.StatusInternalServerError)
		return
	}
	defer tx.Rollback()

	var serviceCategoryID string
	if err := tx.QueryRow("SELECT id_service_category FROM service_types WHERE id_service_type = ?", payload.ServiceTypeID).Scan(&serviceCategoryID); err != nil {
		jsonError(w, "Type de prestation introuvable", http.StatusNotFound)
		return
	}

	idRequest := fmt.Sprintf("req_%d", time.Now().UnixNano())
	if _, err := tx.Exec(`
		INSERT INTO service_requests (
			id_request, desired_date, start_time, estimated_duration,
			intervention_address, status, created_at, id_user, id_service_category
		) VALUES (?, CURDATE(), '09:00:00', 1, ?, 'En attente', NOW(), ?, ?)
	`, idRequest, "Adresse à définir", payload.IDUser, serviceCategoryID); err != nil {
		jsonError(w, "Erreur lors de la création de la demande", http.StatusInternalServerError)
		return
	}

	if _, err := tx.Exec(`INSERT INTO show_type (id_request, id_service_type) VALUES (?, ?)`, idRequest, payload.ServiceTypeID); err != nil {
		jsonError(w, "Erreur lors de l'association de la prestation", http.StatusInternalServerError)
		return
	}

	year := time.Now().Year()
	var last sql.NullString
	if err := tx.QueryRow("SELECT quote_number FROM quotes WHERE quote_number LIKE ? ORDER BY quote_number DESC LIMIT 1", fmt.Sprintf("DV-%d-%%", year)).Scan(&last); err != nil && err != sql.ErrNoRows {
		jsonError(w, "Erreur lors de la génération du numéro de devis", http.StatusInternalServerError)
		return
	}
	seq := 1
	if last.Valid {
		re := regexp.MustCompile(fmt.Sprintf(`DV-%d-(\d{3})`, year))
		if m := re.FindStringSubmatch(last.String); len(m) == 2 {
			fmt.Sscanf(m[1], "%d", &seq)
			seq++
		}
	}
	quoteNumber := fmt.Sprintf("DV-%d-%03d", year, seq)
	amountExcl := payload.Amount / 1.20

	if _, err := tx.Exec(`
		INSERT INTO quotes (
			id_quote, quote_number, amount_excl_tax, tax_rate, amount_incl_tax, status, created_at, id_request
		) VALUES (UUID(), ?, ?, 20.0, ?, 'En attente', NOW(), ?)
	`, quoteNumber, amountExcl, payload.Amount, idRequest); err != nil {
		jsonError(w, "Erreur lors de la création du devis", http.StatusInternalServerError)
		return
	}

	if err := tx.Commit(); err != nil {
		jsonError(w, "Erreur transaction", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Devis créé avec succès"})
}

func handleUpdateAdminQuote(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}
	var payload struct {
		Amount float64 `json:"amount"`
		Status string  `json:"status"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	if payload.Amount <= 0 {
		jsonError(w, "Montant invalide", http.StatusBadRequest)
		return
	}
	amountExcl := payload.Amount / 1.20
	_, err := db.Exec(`
		UPDATE quotes
		SET amount_excl_tax = ?, tax_rate = 20.0, amount_incl_tax = ?, status = ?
		WHERE id_quote = ?
	`, amountExcl, payload.Amount, payload.Status, id)
	if err != nil {
		jsonError(w, "Erreur lors de la mise à jour", http.StatusInternalServerError)
		return
	}
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Devis mis à jour"})
}

func handleDeleteAdminQuote(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}
	_, err := db.Exec("DELETE FROM quotes WHERE id_quote = ?", id)
	if err != nil {
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}
	w.WriteHeader(http.StatusNoContent)
}
