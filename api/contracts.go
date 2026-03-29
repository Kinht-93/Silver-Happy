package main

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
)

// CONTRACTS Tous
func handleGetContracts(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT c.id_contract, c.id_user, c.start_date, c.end_date, c.amount, 
		       c.payment_method, c.status, c.auto_renew,
		       u.first_name, u.last_name, u.email, u.role,
		       DATEDIFF(c.end_date, CURDATE()) as jours_restants
		FROM contracts c
		INNER JOIN users u ON c.id_user = u.id_user
		ORDER BY c.end_date ASC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des contrats", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type ContractData struct {
		ID            string  `json:"id_contract"`
		UserID        string  `json:"id_user"`
		FirstName     string  `json:"first_name"`
		LastName      string  `json:"last_name"`
		Email         string  `json:"email"`
		Role          string  `json:"role"`
		StartDate     string  `json:"start_date"`
		EndDate       string  `json:"end_date"`
		Amount        float64 `json:"amount"`
		PaymentMethod string  `json:"payment_method"`
		Status        string  `json:"status"`
		AutoRenew     bool    `json:"auto_renew"`
		JoursRestants int     `json:"jours_restants"`
	}

	contracts := []ContractData{}
	for rows.Next() {
		var c ContractData
		err := rows.Scan(&c.ID, &c.UserID, &c.StartDate, &c.EndDate, &c.Amount,
			&c.PaymentMethod, &c.Status, &c.AutoRenew,
			&c.FirstName, &c.LastName, &c.Email, &c.Role,
			&c.JoursRestants)
		if err != nil {
			continue
		}
		contracts = append(contracts, c)
	}

	if err := rows.Err(); err != nil {
		jsonError(w, "Erreur lors de la lecture des contrats", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(contracts)
}

// CONTRACTS un
func handleGetContract(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	type ContractData struct {
		ID            string  `json:"id_contract"`
		UserID        string  `json:"id_user"`
		FirstName     string  `json:"first_name"`
		LastName      string  `json:"last_name"`
		Email         string  `json:"email"`
		Role          string  `json:"role"`
		StartDate     string  `json:"start_date"`
		EndDate       string  `json:"end_date"`
		Amount        float64 `json:"amount"`
		PaymentMethod string  `json:"payment_method"`
		Status        string  `json:"status"`
		AutoRenew     bool    `json:"auto_renew"`
		JoursRestants int     `json:"jours_restants"`
	}

	row := db.QueryRow(`
		SELECT c.id_contract, c.id_user, c.start_date, c.end_date, c.amount, 
		       c.payment_method, c.status, c.auto_renew,
		       u.first_name, u.last_name, u.email, u.role,
		       DATEDIFF(c.end_date, CURDATE()) as jours_restants
		FROM contracts c
		INNER JOIN users u ON c.id_user = u.id_user
		WHERE c.id_contract = ?
	`, id)

	var c ContractData
	err := row.Scan(&c.ID, &c.UserID, &c.StartDate, &c.EndDate, &c.Amount,
		&c.PaymentMethod, &c.Status, &c.AutoRenew,
		&c.FirstName, &c.LastName, &c.Email, &c.Role,
		&c.JoursRestants)
	if err != nil {
		jsonError(w, "Contrat introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(c)
}

// CONTRACTS +
func handleCreateContract(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var payload map[string]interface{}
	if err := json.Unmarshal(body, &payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	id_user, ok := payload["id_user"].(string)
	if !ok || id_user == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM contracts WHERE id_user = ? AND status = 'Actif'", id_user).Scan(&count)
	if err == nil && count > 0 {
		jsonError(w, "Cet utilisateur possède déjà un contrat actif", http.StatusConflict)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO contracts (id_contract, id_user, start_date, end_date, amount, payment_method, status, auto_renew, created_at)
		VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, NOW())
	`)
	defer stmt.Close()

	_, err = stmt.Exec(
		id_user,
		payload["start_date"],
		payload["end_date"],
		payload["amount"],
		payload["payment_method"],
		"Actif",
		payload["auto_renew"],
	)
	if err != nil {
		jsonError(w, "Erreur lors de la création du contrat", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Contrat créé avec succès"})
}

// CONTRACTS change
func handleUpdateContract(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	body, _ := io.ReadAll(r.Body)
	var payload map[string]interface{}
	if err := json.Unmarshal(body, &payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	var updates []string
	var args []interface{}

	fields := map[string]string{
		"start_date":     "start_date",
		"end_date":       "end_date",
		"amount":         "amount",
		"payment_method": "payment_method",
		"status":         "status",
		"auto_renew":     "auto_renew",
	}

	for key, dbField := range fields {
		if val, ok := payload[key]; ok {
			updates = append(updates, fmt.Sprintf("%s = ?", dbField))
			args = append(args, val)
		}
	}

	if len(updates) == 0 {
		jsonError(w, "Aucune donnée à mettre à jour", http.StatusBadRequest)
		return
	}

	args = append(args, id)
	query := fmt.Sprintf("UPDATE contracts SET %s, updated_at = NOW() WHERE id_contract = ?", updates[0])
	if len(updates) > 1 {
		for i := 1; i < len(updates); i++ {
			query += ", " + updates[i]
		}
	}

	_, err := db.Exec(query, args...)
	if err != nil {
		jsonError(w, "Erreur lors de la mise à jour", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Contrat mis à jour"})
}

// CONTRACTS -
func handleDeleteContract(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	result, err := db.Exec("DELETE FROM contracts WHERE id_contract = ?", id)
	if err != nil {
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}

	affected, _ := result.RowsAffected()
	if affected == 0 {
		jsonError(w, "Contrat introuvable", http.StatusNotFound)
		return
	}

	w.WriteHeader(http.StatusNoContent)
}

// USERS sans contrat actif
func handleGetUsersWithoutActiveContract(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT u.id_user, u.first_name, u.last_name, u.role
		FROM users u
		WHERE u.active = TRUE
		AND NOT EXISTS (
			SELECT 1 FROM contracts c 
			WHERE c.id_user = u.id_user AND c.status = 'Actif'
		)
		ORDER BY u.last_name ASC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des utilisateurs", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type UserData struct {
		ID        string `json:"id_user"`
		FirstName string `json:"first_name"`
		LastName  string `json:"last_name"`
		Role      string `json:"role"`
	}

	users := []UserData{}
	for rows.Next() {
		var u UserData
		err := rows.Scan(&u.ID, &u.FirstName, &u.LastName, &u.Role)
		if err != nil {
			continue
		}
		users = append(users, u)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(users)
}
