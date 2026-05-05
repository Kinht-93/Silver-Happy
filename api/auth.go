package main

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"time"

	"github.com/golang-jwt/jwt/v5"
	"golang.org/x/crypto/bcrypt"
)

func jsonError(w http.ResponseWriter, msg string, code int) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(code)
	json.NewEncoder(w).Encode(ErrorResponse{Error: msg})
}

var jwtSecret = os.Getenv("JWT_SECRET")

func authMiddleware(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		tokenString := r.Header.Get("X-Token")

		if tokenString == "" {
			jsonError(w, "Token required", http.StatusUnauthorized)
			return
		}

		token, err := jwt.Parse(tokenString, func(token *jwt.Token) (interface{}, error) {
			if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
				return nil, fmt.Errorf("unexpected signing method: %v", token.Header["alg"])
			}
			return []byte(jwtSecret), nil
		})

		if err != nil || !token.Valid {
			jsonError(w, "Invalid token", http.StatusUnauthorized)
			return
		}

		next(w, r)
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
		createLog(payload.Email, "Tentative de connexion", "LOGIN", "Email non trouvé", false)
		jsonError(w, "Identifiants invalides", http.StatusUnauthorized)
		return
	}

	if err := bcrypt.CompareHashAndPassword([]byte(hashedPassword), []byte(payload.Password)); err != nil {
		createLog(email, "Tentative de connexion", "LOGIN", "Mot de passe incorrect", false)
		jsonError(w, "Identifiants invalides", http.StatusUnauthorized)
		return
	}

	claims := jwt.MapClaims{
		"id_user":    id,
		"email":      email,
		"role":       role,
		"first_name": firstName,
		"last_name":  lastName,
	}

	exp := time.Now().Add(time.Hour * 24).Unix()
	if role == "admin" {
		exp = time.Now().Add(time.Hour * 72).Unix()
	}
	claims["exp"] = exp

	token, err := jwt.NewWithClaims(jwt.SigningMethodHS256, claims).SignedString([]byte(jwtSecret))
	if err != nil {
		jsonError(w, "Erreur lors de la génération du token", http.StatusInternalServerError)
		return
	}

	// Log de connexion réussie
	createLog(email, "Connexion utilisateur", "LOGIN", "Connexion réussie", true)

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
