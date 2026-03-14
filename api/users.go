package main

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
)

// USERS Tous
func handleGetUsers(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT id_user, email, role, last_name, first_name, phone, address, 
		city, postal_code, birth_date, active, verified_email, tutorial_seen, created_at 
		FROM users
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des utilisateurs", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	users := []User{}
	for rows.Next() {
		var u User
		err := rows.Scan(&u.ID, &u.Email, &u.Role, &u.LastName, &u.FirstName,
			&u.Phone, &u.Address, &u.City, &u.PostalCode, &u.BirthDate,
			&u.Active, &u.VerifiedEmail, &u.TutorialSeen, &u.CreatedAt)
		if err != nil {
			continue
		}
		users = append(users, u)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(users)
}

// USERS un
func handleGetUser(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	row := db.QueryRow(`
		SELECT id_user, email, role, last_name, first_name, phone, address,
		       city, postal_code, birth_date, active, verified_email, tutorial_seen, created_at
		FROM users WHERE id_user = ?
	`, id)

	var u User
	err := row.Scan(&u.ID, &u.Email, &u.Role, &u.LastName, &u.FirstName,
		&u.Phone, &u.Address, &u.City, &u.PostalCode, &u.BirthDate,
		&u.Active, &u.VerifiedEmail, &u.TutorialSeen, &u.CreatedAt)
	if err != nil {
		jsonError(w, "Utilisateur introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(u)
}

// USERS count active
func handleGetActiveUsersCount(w http.ResponseWriter, r *http.Request) {
	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM users WHERE active = 1").Scan(&count)
	if err != nil {
		jsonError(w, "Erreur lors du comptage des utilisateurs", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"count": count,
	})
}

// USERS +
func handleCreateUser(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var u User
	if err := json.Unmarshal(body, &u); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	if u.Email == "" || u.FirstName == "" || u.LastName == "" {
		jsonError(w, "Champs obligatoires manquants", http.StatusUnprocessableEntity)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO users (id_user, email, password, role, last_name, first_name, 
						   phone, address, city, postal_code, birth_date, active, 
						   verified_email, tutorial_seen, created_at)
		VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
	`)
	defer stmt.Close()

	_, err := stmt.Exec(u.Email, u.Password, u.Role, u.LastName, u.FirstName,
		u.Phone, u.Address, u.City, u.PostalCode, u.BirthDate,
		u.Active, u.VerifiedEmail, u.TutorialSeen)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Utilisateur créé avec succès"})
}

// USERS change
func handleUpdateUser(w http.ResponseWriter, r *http.Request) {
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
		"last_name":   "last_name",
		"first_name":  "first_name",
		"phone":       "phone",
		"address":     "address",
		"city":        "city",
		"postal_code": "postal_code",
		"birth_date":  "birth_date",
		"active":      "active",
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
	query := fmt.Sprintf("UPDATE users SET %s WHERE id_user = ?", updates[0])
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
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Utilisateur mis à jour"})
}

// USERS -
func handleDeleteUser(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	result, err := db.Exec("DELETE FROM users WHERE id_user = ?", id)
	if err != nil {
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}

	affected, _ := result.RowsAffected()
	if affected == 0 {
		jsonError(w, "Utilisateur introuvable", http.StatusNotFound)
		return
	}

	w.WriteHeader(http.StatusNoContent)
}
