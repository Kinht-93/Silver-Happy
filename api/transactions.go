package main

import (
	"encoding/json"
	"net/http"
)

// Get last transactions
func handleGetLastTransactions(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT i.amount_incl_tax, i.invoice_type, i.issue_date, u.first_name, u.last_name 
		FROM invoices i 
		JOIN quotes q ON i.id_quote = q.id_quote
		JOIN service_requests sr ON q.id_request = sr.id_request
		JOIN users u ON sr.id_user = u.id_user
		ORDER BY i.issue_date DESC LIMIT 3
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des transactions", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	transactions := []map[string]interface{}{}
	for rows.Next() {
		var amountInclTax float64
		var invoiceType string
		var issueDate string
		var firstName string
		var lastName string

		if err := rows.Scan(&amountInclTax, &invoiceType, &issueDate, &firstName, &lastName); err != nil {
			continue
		}

		transactions = append(transactions, map[string]interface{}{
			"amount_incl_tax": amountInclTax,
			"invoice_type":    invoiceType,
			"issue_date":      issueDate,
			"first_name":      firstName,
			"last_name":       lastName,
		})
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(transactions)
}

// Get provider pending count
func handleGetPendingProvidersCount(w http.ResponseWriter, r *http.Request) {
	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM users WHERE role = 'prestataire' AND validation_status = 'En attente'").Scan(&count)
	if err != nil {
		jsonError(w, "Erreur lors du comptage des prestataires", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"count": count,
	})
}

// Get service pending count
func handleGetPendingServiceRequestsCount(w http.ResponseWriter, r *http.Request) {
	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM service_requests WHERE status = 'En attente'").Scan(&count)
	if err != nil {
		jsonError(w, "Erreur lors du comptage des demandes", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"count": count,
	})
}
