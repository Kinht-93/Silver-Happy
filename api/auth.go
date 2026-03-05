package main

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"

	"github.com/golang-jwt/jwt/v5"
	"golang.org/x/crypto/bcrypt"
)

var jwtSecret = []byte("test")

type CustomClaims struct {
	UserID string `json:"userId"`
	Role   string `json:"role"`
	jwt.RegisteredClaims
}

// signup
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

	hashed, err := bcrypt.GenerateFromPassword([]byte(payload.Password), 10)
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

	result, err := stmt.Exec(payload.Email, string(hashed), role, payload.LastName, payload.FirstName, payload.BirthDate)
	if err != nil {
		if strings.Contains(err.Error(), "Duplicate") {
			jsonError(w, "Cet email est déjà utilisé", http.StatusConflict)
			return
		}
		jsonError(w, "Erreur lors de la création du compte", http.StatusInternalServerError)
		return
	}

	lastID, _ := result.LastInsertId()
	_ = lastID

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"message": "Compte créé avec succès",
	})
}

// login
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

	row := db.QueryRow(`
		SELECT id_user, email, password, last_name, first_name, role, birth_date, created_at 
		FROM users WHERE email = ?
	`, payload.Email)

	var u User
	if err := row.Scan(&u.ID, &u.Email, &u.Password, &u.LastName, &u.FirstName, &u.Role, &u.BirthDate, &u.CreatedAt); err != nil {
		jsonError(w, "Identifiants invalides", http.StatusUnauthorized)
		return
	}

	if err := bcrypt.CompareHashAndPassword([]byte(u.Password), []byte(payload.Password)); err != nil {
		jsonError(w, "Identifiants invalides", http.StatusUnauthorized)
		return
	}

	claims := CustomClaims{
		UserID: u.ID,
		Role:   u.Role,
		RegisteredClaims: jwt.RegisteredClaims{
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(24 * time.Hour)),
			IssuedAt:  jwt.NewNumericDate(time.Now()),
		},
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	signed, err := token.SignedString(jwtSecret)
	if err != nil {
		jsonError(w, "Erreur de génération du token", http.StatusInternalServerError)
		return
	}

	u.Password = ""

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(TokenResponse{Token: signed, User: u})
}

// verifi jwt
func authMiddleware(next http.HandlerFunc) http.HandlerFunc {
	return func(response http.ResponseWriter, request *http.Request) {
		token := request.Header.Get("X-Token")

		if token == "" {
			response.WriteHeader(http.StatusUnauthorized)
			fmt.Fprintln(response, "Token required")
			return
		}

		var userId int
		queryError := db.QueryRow("SELECT id FROM users WHERE token = ?", token).Scan(&userId)

		if queryError != nil {
			response.WriteHeader(http.StatusUnauthorized)
			fmt.Fprintln(response, "Invalid token")
			return
		}

		next(response, request)
	}
}

// admin
func adminOnly(next http.HandlerFunc) http.HandlerFunc {
	// test a mettre plus tard
}
