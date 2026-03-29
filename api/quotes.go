package main

import (
	"encoding/json"
	"io"
	"net/http"
)

// QUOTES tous
func handleGetQuotes(w http.ResponseWriter, r *http.Request) {
	idUser := r.URL.Query().Get("id_user")
	if idUser != "" {
		handleGetUserQuotes(w, r)
		return
	}

	rows, err := db.Query(`
		SELECT id_quote, quote_number, amount_excl_tax, tax_rate, amount_incl_tax, status, created_at, id_request
		FROM quotes
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des devis", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	quotes := []Quote{}
	for rows.Next() {
		var q Quote
		if err := rows.Scan(&q.ID, &q.QuoteNumber, &q.AmountExclTax, &q.TaxRate, &q.AmountInclTax,
			&q.Status, &q.CreatedAt, &q.RequestID); err != nil {
			continue
		}
		quotes = append(quotes, q)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(quotes)
}

func handleGetUserQuotes(w http.ResponseWriter, r *http.Request) {
	idUser := r.URL.Query().Get("id_user")
	if idUser == "" {
		idUser = r.PathValue("id")
	}
	if idUser == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	rows, err := db.Query(`
		SELECT q.id_quote, q.quote_number, q.amount_excl_tax, q.tax_rate, q.amount_incl_tax, q.status, q.created_at,
		       sr.desired_date, COALESCE(st.name, sc.name) as prestation_name
		FROM quotes q
		INNER JOIN service_requests sr ON sr.id_request = q.id_request
		INNER JOIN service_categories sc ON sc.id_service_category = sr.id_service_category
		LEFT JOIN show_type sht ON sht.id_request = sr.id_request
		LEFT JOIN service_types st ON st.id_service_type = sht.id_service_type
		WHERE sr.id_user = ?
		ORDER BY q.created_at DESC, q.quote_number DESC
	`, idUser)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des devis", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type userQuote struct {
		ID            string  `json:"id_quote"`
		QuoteNumber   string  `json:"quote_number"`
		AmountExclTax float64 `json:"amount_excl_tax"`
		TaxRate       float64 `json:"tax_rate"`
		AmountInclTax float64 `json:"amount_incl_tax"`
		Status        string  `json:"status"`
		CreatedAt     string  `json:"created_at"`
		DesiredDate   string  `json:"desired_date"`
		Prestation    string  `json:"prestation_name"`
	}

	quotes := []userQuote{}
	for rows.Next() {
		var item userQuote
		if err := rows.Scan(&item.ID, &item.QuoteNumber, &item.AmountExclTax, &item.TaxRate, &item.AmountInclTax, &item.Status, &item.CreatedAt, &item.DesiredDate, &item.Prestation); err != nil {
			continue
		}
		quotes = append(quotes, item)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(quotes)
}

// QUOTES un
func handleGetQuote(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	row := db.QueryRow(`
		SELECT id_quote, quote_number, amount_excl_tax, tax_rate, amount_incl_tax, status, created_at, id_request
		FROM quotes WHERE id_quote = ?
	`, id)

	var q Quote
	if err := row.Scan(&q.ID, &q.QuoteNumber, &q.AmountExclTax, &q.TaxRate, &q.AmountInclTax,
		&q.Status, &q.CreatedAt, &q.RequestID); err != nil {
		jsonError(w, "Devis introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(q)
}

// QUOTES COUNT
func handleGetQuoteCount(w http.ResponseWriter, r *http.Request) {
	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM quotes").Scan(&count)
	if err != nil {
		jsonError(w, "Erreur lors du comptage des utilisateurs", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"count": count,
	})
}

// QUOTES +
func handleCreateQuote(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var q Quote
	if err := json.Unmarshal(body, &q); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO quotes (id_quote, quote_number, amount_excl_tax, tax_rate, amount_incl_tax, status, created_at, id_request)
		VALUES (UUID(), ?, ?, ?, ?, ?, NOW(), ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(q.QuoteNumber, q.AmountExclTax, q.TaxRate, q.AmountInclTax, q.Status, q.RequestID)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Devis créé avec succès"})
}
