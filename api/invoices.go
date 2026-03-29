package main

import (
	"encoding/json"
	"io"
	"net/http"
)

// INVOICES tous
func handleGetInvoices(w http.ResponseWriter, r *http.Request) {
	idUser := r.URL.Query().Get("id_user")
	if idUser != "" {
		handleGetUserInvoices(w, r)
		return
	}

	rows, err := db.Query(`
		SELECT id_invoice, invoice_number, invoice_type, amount_excl_tax, tax_rate, amount_incl_tax,
		       issue_date, due_date, status, id_quote
		FROM invoices
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des factures", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	invoices := []Invoice{}
	for rows.Next() {
		var inv Invoice
		if err := rows.Scan(&inv.ID, &inv.InvoiceNumber, &inv.InvoiceType, &inv.AmountExclTax, &inv.TaxRate,
			&inv.AmountInclTax, &inv.IssueDate, &inv.DueDate, &inv.Status, &inv.QuoteID); err != nil {
			continue
		}
		invoices = append(invoices, inv)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(invoices)
}

// GET USER INVOICES

func handleGetUserInvoices(w http.ResponseWriter, r *http.Request) {
	idUser := r.URL.Query().Get("id_user")
	if idUser == "" {
		idUser = r.PathValue("id")
	}
	if idUser == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	rows, err := db.Query(`
		SELECT i.invoice_number, i.invoice_type, i.amount_excl_tax, i.tax_rate, i.amount_incl_tax,
		       i.issue_date, i.due_date, i.status,
		       sr.desired_date, sc.name AS category_name
		FROM invoices i
		INNER JOIN quotes q ON q.id_quote = i.id_quote
		INNER JOIN service_requests sr ON sr.id_request = q.id_request
		INNER JOIN service_categories sc ON sc.id_service_category = sr.id_service_category
		WHERE sr.id_user = ?
		ORDER BY i.issue_date DESC, i.invoice_number DESC
	`, idUser)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des factures", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type userInvoice struct {
		InvoiceNumber string  `json:"invoice_number"`
		InvoiceType   string  `json:"invoice_type"`
		AmountExclTax float64 `json:"amount_excl_tax"`
		TaxRate       float64 `json:"tax_rate"`
		AmountInclTax float64 `json:"amount_incl_tax"`
		IssueDate     string  `json:"issue_date"`
		DueDate       string  `json:"due_date"`
		Status        string  `json:"status"`
		DesiredDate   string  `json:"desired_date"`
		CategoryName  string  `json:"category_name"`
	}

	invoices := []userInvoice{}
	for rows.Next() {
		var inv userInvoice
		if err := rows.Scan(
			&inv.InvoiceNumber,
			&inv.InvoiceType,
			&inv.AmountExclTax,
			&inv.TaxRate,
			&inv.AmountInclTax,
			&inv.IssueDate,
			&inv.DueDate,
			&inv.Status,
			&inv.DesiredDate,
			&inv.CategoryName,
		); err != nil {
			continue
		}
		invoices = append(invoices, inv)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(invoices)
}

// INVOICES un
func handleGetInvoice(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	row := db.QueryRow(`
		SELECT id_invoice, invoice_number, invoice_type, amount_excl_tax, tax_rate, amount_incl_tax,
		       issue_date, due_date, status, id_quote
		FROM invoices WHERE id_invoice = ?
	`, id)

	var inv Invoice
	if err := row.Scan(&inv.ID, &inv.InvoiceNumber, &inv.InvoiceType, &inv.AmountExclTax, &inv.TaxRate,
		&inv.AmountInclTax, &inv.IssueDate, &inv.DueDate, &inv.Status, &inv.QuoteID); err != nil {
		jsonError(w, "Facture introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(inv)
}

// INVOICES +
func handleCreateInvoice(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var inv Invoice
	if err := json.Unmarshal(body, &inv); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO invoices (id_invoice, invoice_number, invoice_type, amount_excl_tax, tax_rate, amount_incl_tax,
							  issue_date, due_date, status, id_quote)
		VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(inv.InvoiceNumber, inv.InvoiceType, inv.AmountExclTax, inv.TaxRate, inv.AmountInclTax,
		inv.IssueDate, inv.DueDate, inv.Status, inv.QuoteID)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Facture créée avec succès"})
}
