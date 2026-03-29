package main

import (
	"encoding/json"
	"net/http"
	"time"
)

func handleGetAdminInvoices(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT i.id_invoice, i.issue_date, i.amount_incl_tax AS amount, i.status,
		       u.first_name, u.last_name
		FROM invoices i
		JOIN quotes q ON i.id_quote = q.id_quote
		JOIN service_requests sr ON q.id_request = sr.id_request
		JOIN users u ON sr.id_user = u.id_user
		ORDER BY i.issue_date DESC
		LIMIT 20
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des factures", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type invoiceData struct {
		ID        string    `json:"id_invoice"`
		IssueDate time.Time `json:"issue_date"`
		Amount    float64   `json:"amount"`
		Status    string    `json:"status"`
		FirstName string    `json:"first_name"`
		LastName  string    `json:"last_name"`
	}
	invoices := []invoiceData{}
	for rows.Next() {
		var inv invoiceData
		if err := rows.Scan(&inv.ID, &inv.IssueDate, &inv.Amount, &inv.Status, &inv.FirstName, &inv.LastName); err != nil {
			continue
		}
		invoices = append(invoices, inv)
	}
	json.NewEncoder(w).Encode(invoices)
}

func handleGetInvoiceStats(w http.ResponseWriter, r *http.Request) {
	var ca float64
	var payees int
	var attente int
	var retard int

	db.QueryRow("SELECT COALESCE(SUM(amount_incl_tax), 0) FROM invoices WHERE status = 'Payée'").Scan(&ca)
	db.QueryRow("SELECT COUNT(*) FROM invoices WHERE status = 'Payée'").Scan(&payees)
	db.QueryRow("SELECT COUNT(*) FROM invoices WHERE status = 'En attente'").Scan(&attente)
	db.QueryRow("SELECT COUNT(*) FROM invoices WHERE status = 'En retard'").Scan(&retard)

	stats := map[string]interface{}{"ca": ca, "payees": payees, "attente": attente, "retard": retard}
	json.NewEncoder(w).Encode(stats)
}

func handleUpdateInvoiceStatus(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}
	var payload struct {
		Status string `json:"status"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	_, err := db.Exec("UPDATE invoices SET status = ? WHERE id_invoice = ?", payload.Status, id)
	if err != nil {
		jsonError(w, "Erreur lors de la mise à jour", http.StatusInternalServerError)
		return
	}
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Statut mis à jour"})
}
