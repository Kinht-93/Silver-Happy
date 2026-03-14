package main

import (
	"encoding/json"
	"io"
	"net/http"
)

// QUOTES tous
func handleGetQuotes(w http.ResponseWriter, r *http.Request) {
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
