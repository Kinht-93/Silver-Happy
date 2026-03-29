package main

import (
	"encoding/json"
	"net/http"
)

func handleGetSubscriptionTypesAdmin(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT st.id_subscription_type, st.name, st.user_type, st.monthly_price,
		       (SELECT COUNT(*) FROM subscribed s WHERE s.id_subscription_type = st.id_subscription_type) AS abonnes
		FROM subscription_types st
		ORDER BY st.monthly_price ASC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des abonnements", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type subscriptionData struct {
		ID           string  `json:"id_subscription_type"`
		Name         string  `json:"name"`
		UserType     string  `json:"user_type"`
		MonthlyPrice float64 `json:"monthly_price"`
		Abonnes      int     `json:"abonnes"`
	}
	items := []subscriptionData{}
	for rows.Next() {
		var item subscriptionData
		if err := rows.Scan(&item.ID, &item.Name, &item.UserType, &item.MonthlyPrice, &item.Abonnes); err != nil {
			continue
		}
		items = append(items, item)
	}
	json.NewEncoder(w).Encode(items)
}

func handleGetSubscriptionStats(w http.ResponseWriter, r *http.Request) {
	var totalActifs int
	var revenus float64

	db.QueryRow("SELECT COUNT(DISTINCT id_user) FROM subscribed").Scan(&totalActifs)
	db.QueryRow(`
		SELECT COALESCE(SUM(st.monthly_price), 0)
		FROM subscribed s
		JOIN subscription_types st ON s.id_subscription_type = st.id_subscription_type
	`).Scan(&revenus)

	stats := map[string]interface{}{"total_actifs": totalActifs, "revenus": revenus}
	json.NewEncoder(w).Encode(stats)
}

func handleCreateSubscriptionType(w http.ResponseWriter, r *http.Request) {
	var payload struct {
		Name         string  `json:"name"`
		UserType     string  `json:"user_type"`
		MonthlyPrice float64 `json:"monthly_price"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	yearly := payload.MonthlyPrice * 12
	_, err := db.Exec(`
		INSERT INTO subscription_types (id_subscription_type, name, user_type, monthly_price, yearly_price)
		VALUES (CONCAT('sub_', UUID()), ?, ?, ?, ?)
	`, payload.Name, payload.UserType, payload.MonthlyPrice, yearly)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Formule créée"})
}

func handleUpdateSubscriptionType(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	var payload struct {
		Name         string  `json:"name"`
		UserType     string  `json:"user_type"`
		MonthlyPrice float64 `json:"monthly_price"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	yearly := payload.MonthlyPrice * 12
	_, err := db.Exec(`
		UPDATE subscription_types
		SET name = ?, user_type = ?, monthly_price = ?, yearly_price = ?
		WHERE id_subscription_type = ?
	`, payload.Name, payload.UserType, payload.MonthlyPrice, yearly, id)
	if err != nil {
		jsonError(w, "Erreur lors de la mise à jour", http.StatusInternalServerError)
		return
	}
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Formule mise à jour"})
}

func handleDeleteSubscriptionType(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	_, err := db.Exec("DELETE FROM subscription_types WHERE id_subscription_type = ?", id)
	if err != nil {
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}
	w.WriteHeader(http.StatusNoContent)
}
