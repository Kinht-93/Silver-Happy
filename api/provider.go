package main

import (
	"encoding/json"
	"net/http"
	"strings"
)

func handleGetProviderDashboard(w http.ResponseWriter, r *http.Request) {
	providerID := r.PathValue("id")
	if providerID == "" {
		jsonError(w, "ID prestataire manquant", http.StatusBadRequest)
		return
	}

	type providerDashboard struct {
		AvailabilitiesCount   int `json:"availabilities_count"`
		AcceptedMissionsCount int `json:"accepted_missions_count"`
		InvoicesCount         int `json:"invoices_count"`
	}

	var data providerDashboard
	if err := db.QueryRow("SELECT COUNT(*) FROM provider_availabilities WHERE id_user = ?", providerID).Scan(&data.AvailabilitiesCount); err != nil {
		jsonError(w, "Erreur lors du comptage des disponibilités", http.StatusInternalServerError)
		return
	}
	if err := db.QueryRow("SELECT COUNT(*) FROM provider_missions WHERE id_user = ? AND status = 'Acceptee'", providerID).Scan(&data.AcceptedMissionsCount); err != nil {
		jsonError(w, "Erreur lors du comptage des missions", http.StatusInternalServerError)
		return
	}
	if err := db.QueryRow("SELECT COUNT(*) FROM provider_invoices WHERE id_user = ?", providerID).Scan(&data.InvoicesCount); err != nil {
		jsonError(w, "Erreur lors du comptage des factures", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(data)
}

func handleGetProviderOwnAvailabilities(w http.ResponseWriter, r *http.Request) {
	providerID := r.PathValue("id")
	if providerID == "" {
		jsonError(w, "ID prestataire manquant", http.StatusBadRequest)
		return
	}

	rows, err := db.Query(`
		SELECT id_availability, available_date, start_time, end_time, is_available
		FROM provider_availabilities
		WHERE id_user = ?
		ORDER BY available_date DESC, start_time DESC
	`, providerID)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des disponibilités", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type providerAvailability struct {
		ID          int  `json:"id_availability"`
		AvailableAt string `json:"available_date"`
		StartTime   string `json:"start_time"`
		EndTime     string `json:"end_time"`
		IsAvailable bool   `json:"is_available"`
	}

	items := []providerAvailability{}
	for rows.Next() {
		var item providerAvailability
		if err := rows.Scan(&item.ID, &item.AvailableAt, &item.StartTime, &item.EndTime, &item.IsAvailable); err != nil {
			continue
		}
		items = append(items, item)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(items)
}

func handleCreateProviderAvailability(w http.ResponseWriter, r *http.Request) {
	providerID := r.PathValue("id")
	if providerID == "" {
		jsonError(w, "ID prestataire manquant", http.StatusBadRequest)
		return
	}

	var payload struct {
		AvailableDate string `json:"available_date"`
		StartTime     string `json:"start_time"`
		EndTime       string `json:"end_time"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	if payload.AvailableDate == "" || payload.StartTime == "" || payload.EndTime == "" {
		jsonError(w, "Champs requis manquants", http.StatusBadRequest)
		return
	}
	if payload.StartTime >= payload.EndTime {
		jsonError(w, "L'heure de fin doit être après l'heure de début", http.StatusBadRequest)
		return
	}

	_, err := db.Exec(`
		INSERT INTO provider_availabilities (id_user, available_date, start_time, end_time, is_available, created_at)
		VALUES (?, ?, ?, ?, 1, NOW())
	`, providerID, payload.AvailableDate, payload.StartTime, payload.EndTime)
	if err != nil {
		jsonError(w, "Erreur lors de l'ajout de la disponibilité", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Disponibilité ajoutée"})
}

func handleDeleteProviderAvailability(w http.ResponseWriter, r *http.Request) {
	providerID := r.PathValue("id")
	availabilityID := r.PathValue("availabilityId")
	if providerID == "" || availabilityID == "" {
		jsonError(w, "Paramètres manquants", http.StatusBadRequest)
		return
	}

	result, err := db.Exec("DELETE FROM provider_availabilities WHERE id_availability = ? AND id_user = ?", availabilityID, providerID)
	if err != nil {
		jsonError(w, "Erreur lors de la suppression de la disponibilité", http.StatusInternalServerError)
		return
	}
	affected, _ := result.RowsAffected()
	if affected == 0 {
		jsonError(w, "Disponibilité introuvable", http.StatusNotFound)
		return
	}

	w.WriteHeader(http.StatusNoContent)
}

func handleGetProviderMissions(w http.ResponseWriter, r *http.Request) {
	providerID := r.PathValue("id")
	if providerID == "" {
		jsonError(w, "ID prestataire manquant", http.StatusBadRequest)
		return
	}

	rows, err := db.Query(`
		SELECT id_mission, mission_title, mission_description, mission_date, status, id_user
		FROM provider_missions
		WHERE id_user IS NULL OR id_user = ?
		ORDER BY created_at DESC
	`, providerID)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des missions", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type providerMission struct {
		ID          string  `json:"id_mission"`
		Title       string  `json:"mission_title"`
		Description *string `json:"mission_description,omitempty"`
		MissionDate *string `json:"mission_date,omitempty"`
		Status      string  `json:"status"`
		UserID      *string `json:"id_user,omitempty"`
	}

	items := []providerMission{}
	for rows.Next() {
		var item providerMission
		if err := rows.Scan(&item.ID, &item.Title, &item.Description, &item.MissionDate, &item.Status, &item.UserID); err != nil {
			continue
		}
		items = append(items, item)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(items)
}

func handleAcceptProviderMission(w http.ResponseWriter, r *http.Request) {
	missionID := r.PathValue("id")
	if missionID == "" {
		jsonError(w, "ID mission manquant", http.StatusBadRequest)
		return
	}

	var payload struct {
		ProviderID string `json:"id_user"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	if payload.ProviderID == "" {
		jsonError(w, "ID prestataire manquant", http.StatusBadRequest)
		return
	}

	result, err := db.Exec(`
		UPDATE provider_missions
		SET status = 'Acceptee', id_user = ?, accepted_at = NOW()
		WHERE id_mission = ?
		  AND (id_user IS NULL OR id_user = ?)
		  AND status = 'Proposee'
	`, payload.ProviderID, missionID, payload.ProviderID)
	if err != nil {
		jsonError(w, "Erreur lors de l'acceptation de la mission", http.StatusInternalServerError)
		return
	}
	affected, _ := result.RowsAffected()
	if affected == 0 {
		jsonError(w, "Mission indisponible ou déjà acceptée", http.StatusConflict)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Mission acceptée"})
}

func handleGetProviderBilling(w http.ResponseWriter, r *http.Request) {
	providerID := r.PathValue("id")
	if providerID == "" {
		jsonError(w, "ID prestataire manquant", http.StatusBadRequest)
		return
	}

	rows, err := db.Query(`
		SELECT i.id_invoice, i.month_label, i.amount, i.status AS invoice_status, i.generated_at,
		       p.status AS payment_status, p.paid_at
		FROM provider_invoices i
		LEFT JOIN provider_payments p ON p.id_invoice = i.id_invoice
		WHERE i.id_user = ?
		ORDER BY i.generated_at DESC
	`, providerID)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération de la facturation", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type providerBillingRow struct {
		InvoiceID      string  `json:"id_invoice"`
		MonthLabel     string  `json:"month_label"`
		Amount         float64 `json:"amount"`
		InvoiceStatus  string  `json:"invoice_status"`
		GeneratedAt    string  `json:"generated_at"`
		PaymentStatus  *string `json:"payment_status,omitempty"`
		PaidAt         *string `json:"paid_at,omitempty"`
	}

	items := []providerBillingRow{}
	for rows.Next() {
		var item providerBillingRow
		if err := rows.Scan(&item.InvoiceID, &item.MonthLabel, &item.Amount, &item.InvoiceStatus, &item.GeneratedAt, &item.PaymentStatus, &item.PaidAt); err != nil {
			continue
		}
		items = append(items, item)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(items)
}

func handleGenerateProviderInvoice(w http.ResponseWriter, r *http.Request) {
	providerID := r.PathValue("id")
	if providerID == "" {
		jsonError(w, "ID prestataire manquant", http.StatusBadRequest)
		return
	}

	monthLabel := r.URL.Query().Get("month")
	if monthLabel == "" {
		if err := db.QueryRow("SELECT DATE_FORMAT(NOW(), '%Y-%m')").Scan(&monthLabel); err != nil {
			jsonError(w, "Erreur lors du calcul du mois", http.StatusInternalServerError)
			return
		}
	}

	var missionsCount int
	if err := db.QueryRow(`
		SELECT COUNT(*)
		FROM provider_missions
		WHERE id_user = ?
		  AND status = 'Acceptee'
		  AND DATE_FORMAT(COALESCE(accepted_at, created_at), '%Y-%m') = ?
	`, providerID, monthLabel).Scan(&missionsCount); err != nil {
		jsonError(w, "Erreur lors du calcul des missions", http.StatusInternalServerError)
		return
	}

	amount := float64(missionsCount) * 25.00
	tx, err := db.Begin()
	if err != nil {
		jsonError(w, "Erreur transaction", http.StatusInternalServerError)
		return
	}
	defer tx.Rollback()

	invoiceID := "pinv_" + strings.ReplaceAll(monthLabel, "-", "") + "_" + providerID
	if _, err := tx.Exec(`
		INSERT INTO provider_invoices (id_invoice, id_user, month_label, amount, status, generated_at)
		VALUES (?, ?, ?, ?, 'Generee', NOW())
	`, invoiceID, providerID, monthLabel, amount); err != nil {
		jsonError(w, "Facture déjà générée pour ce mois", http.StatusConflict)
		return
	}

	paymentID := "ppay_" + strings.ReplaceAll(monthLabel, "-", "") + "_" + providerID
	if _, err := tx.Exec(`
		INSERT INTO provider_payments (id_payment, id_invoice, id_user, amount, paid_at, status)
		VALUES (?, ?, ?, ?, NULL, 'En attente')
	`, paymentID, invoiceID, providerID, amount); err != nil {
		jsonError(w, "Erreur lors de la création du paiement", http.StatusInternalServerError)
		return
	}

	if err := tx.Commit(); err != nil {
		jsonError(w, "Erreur transaction", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Facture mensuelle générée"})
}