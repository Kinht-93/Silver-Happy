package main

import (
	"encoding/json"
	"io"
	"net/http"
)

// INVOICES tous
func handleGetInvoices(w http.ResponseWriter, r *http.Request) {
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
