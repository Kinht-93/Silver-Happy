package main

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"

	"golang.org/x/crypto/bcrypt"
)

func jsonError(w http.ResponseWriter, msg string, code int) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(code)
	json.NewEncoder(w).Encode(ErrorResponse{Error: msg})
}

func authMiddleware(next http.HandlerFunc) http.HandlerFunc {
	return func(response http.ResponseWriter, request *http.Request) {
		token := request.Header.Get("X-Token")

		if token == "" {
			response.WriteHeader(http.StatusUnauthorized)
			fmt.Fprintln(response, "Token required")
			return
		}

		if len(token) < 7 || token[:6] != "token_" {
			response.WriteHeader(http.StatusUnauthorized)
			fmt.Fprintln(response, "Invalid token")
			return
		}

		next(response, request)
	}
}

// SIGNUP
func handleSignup(w http.ResponseWriter, r *http.Request) {
	body, err := io.ReadAll(r.Body)
	if err != nil {
		jsonError(w, "Impossible de lire la requête", http.StatusBadRequest)
		return
	}

	var payload SignupPayload
	if err := json.Unmarshal(body, &payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	if payload.Email == "" || payload.Password == "" || payload.FirstName == "" || payload.LastName == "" {
		jsonError(w, "Champs obligatoires manquants", http.StatusUnprocessableEntity)
		return
	}

	hashed, err := bcrypt.GenerateFromPassword([]byte(payload.Password), bcrypt.DefaultCost)
	if err != nil {
		jsonError(w, "Erreur lors du hachage du mot de passe", http.StatusInternalServerError)
		return
	}

	role := payload.Role
	if role == "" {
		role = "senior"
	}

	stmt, err := db.Prepare(`
		INSERT INTO users (id_user, email, password, role, last_name, first_name, birth_date, created_at)
		VALUES (UUID(), ?, ?, ?, ?, ?, ?, NOW())
	`)
	if err != nil {
		jsonError(w, "Erreur interne", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	_, err = stmt.Exec(payload.Email, string(hashed), role, payload.LastName, payload.FirstName, payload.BirthDate)
	if err != nil {
		jsonError(w, "Erreur lors de la création du compte", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
	})
}

// LOGIN
func handleLogin(w http.ResponseWriter, r *http.Request) {
	body, err := io.ReadAll(r.Body)
	if err != nil {
		jsonError(w, "Impossible de lire la requête", http.StatusBadRequest)
		return
	}

	var payload LoginPayload
	if err := json.Unmarshal(body, &payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	var id string
	var hashedPassword string
	var email, role, firstName, lastName string
	row := db.QueryRow("SELECT id_user, password, email, role, first_name, last_name FROM users WHERE email = ?", payload.Email)

	if err := row.Scan(&id, &hashedPassword, &email, &role, &firstName, &lastName); err != nil {
		jsonError(w, "Identifiants invalides", http.StatusUnauthorized)
		return
	}

	if err := bcrypt.CompareHashAndPassword([]byte(hashedPassword), []byte(payload.Password)); err != nil {
		jsonError(w, "Identifiants invalides", http.StatusUnauthorized)
		return
	}

	token := "token_" + id

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"token": token,
		"user": map[string]interface{}{
			"id_user":    id,
			"email":      email,
			"role":       role,
			"first_name": firstName,
			"last_name":  lastName,
		},
	})
}
