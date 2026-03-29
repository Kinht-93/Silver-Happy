package main

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
)

var allowedSeniorLanguages = map[string]bool{
	"fr": true,
	"en": true,
	"es": true,
	"de": true,
	"it": true,
}

var allowedSeniorFontSizes = map[string]bool{
	"Normale":     true,
	"Grande":      true,
	"Tres grande": true,
}

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

	if err := rows.Err(); err != nil {
		jsonError(w, "Erreur lors de la lecture des utilisateurs", http.StatusInternalServerError)
		return
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
		       city, postal_code, birth_date, emergency_contact_name, emergency_contact_phone,
		       company_name, siret_number, validation_status, average_rating, commission_rate,
		       zone, iban, provider_description, skills_text, provider_updated_at,
		       active, verified_email, tutorial_seen, created_at
		FROM users WHERE id_user = ?
	`, id)

	var u User
	err := row.Scan(&u.ID, &u.Email, &u.Role, &u.LastName, &u.FirstName,
		&u.Phone, &u.Address, &u.City, &u.PostalCode, &u.BirthDate,
		&u.EmergencyContactName, &u.EmergencyContactPhone,
		&u.CompanyName, &u.SiretNumber, &u.ValidationStatus, &u.AverageRating, &u.CommissionRate,
		&u.Zone, &u.IBAN, &u.ProviderDescription, &u.SkillsText, &u.ProviderUpdatedAt,
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
	if err := ensureActiveUsersTable(); err != nil {
		jsonError(w, "Erreur lors de la préparation du suivi d'activité", http.StatusInternalServerError)
		return
	}

	if _, err := db.Exec(`DELETE FROM active_users WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)`); err != nil {
		jsonError(w, "Erreur lors du nettoyage des utilisateurs actifs", http.StatusInternalServerError)
		return
	}

	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM active_users WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)").Scan(&count)
	if err != nil {
		jsonError(w, "Erreur lors du comptage des utilisateurs", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"count": count,
	})
}

func handleTouchUserActivity(w http.ResponseWriter, r *http.Request) {
	userID := r.PathValue("id")
	if userID == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	if err := ensureActiveUsersTable(); err != nil {
		jsonError(w, "Erreur lors de la préparation du suivi d'activité", http.StatusInternalServerError)
		return
	}

	if _, err := db.Exec(`DELETE FROM active_users WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)`); err != nil {
		jsonError(w, "Erreur lors du nettoyage des utilisateurs actifs", http.StatusInternalServerError)
		return
	}

	if _, err := db.Exec(`
		INSERT INTO active_users (id_user, last_activity)
		VALUES (?, NOW())
		ON DUPLICATE KEY UPDATE last_activity = NOW()
	`, userID); err != nil {
		jsonError(w, "Erreur lors de la mise à jour de l'activité", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Activité mise à jour"})
}

func handleGetActiveUsers(w http.ResponseWriter, r *http.Request) {
	if err := ensureActiveUsersTable(); err != nil {
		jsonError(w, "Erreur lors de la préparation du suivi d'activité", http.StatusInternalServerError)
		return
	}

	rows, err := db.Query(`
		SELECT u.id_user,
		       u.first_name,
		       u.last_name,
		       u.role,
		       TIMESTAMPDIFF(SECOND, a.last_activity, NOW()) as seconds_ago
		FROM active_users a
		INNER JOIN users u ON a.id_user = u.id_user
		WHERE a.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
		ORDER BY a.last_activity DESC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des utilisateurs actifs", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type activeUserItem struct {
		UserID     string `json:"id_user"`
		FirstName  string `json:"first_name"`
		LastName   string `json:"last_name"`
		Role       string `json:"role"`
		Username   string `json:"username"`
		Type       string `json:"type"`
		SecondsAgo int    `json:"seconds_ago"`
	}

	items := []activeUserItem{}
	for rows.Next() {
		var item activeUserItem
		if err := rows.Scan(&item.UserID, &item.FirstName, &item.LastName, &item.Role, &item.SecondsAgo); err != nil {
			continue
		}
		item.Username = strings.TrimSpace(item.FirstName + " " + item.LastName)
		if item.Username == "" {
			item.Username = item.UserID
		}
		item.Type = item.Role
		items = append(items, item)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(items)
}

func ensureActiveUsersTable() error {
	_, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS active_users (
			id_user VARCHAR(255) PRIMARY KEY,
			last_activity TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
			FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
		)
	`)
	return err
}

// USERS +
func handleCreateUser(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var u User
	if err := json.Unmarshal(body, &u); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
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
		"last_name":               "last_name",
		"first_name":              "first_name",
		"email":                   "email",
		"role":                    "role",
		"phone":                   "phone",
		"address":                 "address",
		"city":                    "city",
		"postal_code":             "postal_code",
		"birth_date":              "birth_date",
		"emergency_contact_name":  "emergency_contact_name",
		"emergency_contact_phone": "emergency_contact_phone",
		"company_name":            "company_name",
		"siret_number":            "siret_number",
		"validation_status":       "validation_status",
		"average_rating":          "average_rating",
		"commission_rate":         "commission_rate",
		"zone":                    "zone",
		"iban":                    "iban",
		"provider_description":    "provider_description",
		"skills_text":             "skills_text",
		"provider_updated_at":     "provider_updated_at",
		"active":                  "active",
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
	query := fmt.Sprintf("UPDATE users SET %s WHERE id_user = ?", strings.Join(updates, ", "))

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

// USER employes tous
func handleGetEmployees(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT id_user, email, role, last_name, first_name, phone, address, 
		city, postal_code, birth_date, active, verified_email, tutorial_seen, created_at 
		FROM users
		WHERE role = 'employee' OR role = 'employe'
		ORDER BY created_at DESC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des employés", http.StatusInternalServerError)
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

	if err := rows.Err(); err != nil {
		jsonError(w, "Erreur lors de la lecture des employés", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(users)
}

func ensureSeniorSettingsTable() error {
	_, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS senior_settings (
			id_user VARCHAR(255) PRIMARY KEY,
			language VARCHAR(10) NOT NULL DEFAULT 'fr',
			font_size VARCHAR(30) NOT NULL DEFAULT 'Normale',
			email_notifications BOOLEAN NOT NULL DEFAULT TRUE,
			emergency_relation VARCHAR(120) DEFAULT NULL,
			updated_at DATETIME NOT NULL,
			INDEX idx_senior_settings_updated_at (updated_at),
			FOREIGN KEY (id_user) REFERENCES users(id_user)
		)
	`)
	return err
}

func loadSeniorSettings(userID string) (SeniorSettings, error) {
	settings := SeniorSettings{
		UserID:             userID,
		Language:           "fr",
		FontSize:           "Normale",
		EmailNotifications: true,
	}

	if err := ensureSeniorSettingsTable(); err != nil {
		return settings, err
	}

	var emergencyRelation sql.NullString
	var emailNotifications sql.NullBool
	err := db.QueryRow(`
		SELECT language, font_size, email_notifications, emergency_relation
		FROM senior_settings
		WHERE id_user = ?
		LIMIT 1
	`, userID).Scan(&settings.Language, &settings.FontSize, &emailNotifications, &emergencyRelation)
	if err != nil {
		if err == sql.ErrNoRows {
			return settings, nil
		}
		return settings, err
	}

	if !allowedSeniorLanguages[settings.Language] {
		settings.Language = "fr"
	}
	if !allowedSeniorFontSizes[settings.FontSize] {
		settings.FontSize = "Normale"
	}
	settings.EmailNotifications = !emailNotifications.Valid || emailNotifications.Bool
	if emergencyRelation.Valid {
		settings.EmergencyRelation = &emergencyRelation.String
	}

	return settings, nil
}

func handleGetSeniorSettings(w http.ResponseWriter, r *http.Request) {
	userID := r.PathValue("id")
	if userID == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	settings, err := loadSeniorSettings(userID)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des préférences", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(settings)
}

func handleUpdateSeniorSettings(w http.ResponseWriter, r *http.Request) {
	userID := r.PathValue("id")
	if userID == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	body, _ := io.ReadAll(r.Body)
	var payload map[string]interface{}
	if err := json.Unmarshal(body, &payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	settings, err := loadSeniorSettings(userID)
	if err != nil {
		jsonError(w, "Erreur lors du chargement des préférences", http.StatusInternalServerError)
		return
	}

	if language, ok := payload["language"]; ok {
		value, valid := language.(string)
		if !valid || !allowedSeniorLanguages[value] {
			jsonError(w, "Langue invalide", http.StatusBadRequest)
			return
		}
		settings.Language = value
	}

	if fontSize, ok := payload["font_size"]; ok {
		value, valid := fontSize.(string)
		if !valid || !allowedSeniorFontSizes[value] {
			jsonError(w, "Taille de texte invalide", http.StatusBadRequest)
			return
		}
		settings.FontSize = value
	}

	if emailNotifications, ok := payload["email_notifications"]; ok {
		value, valid := emailNotifications.(bool)
		if !valid {
			jsonError(w, "Valeur email_notifications invalide", http.StatusBadRequest)
			return
		}
		settings.EmailNotifications = value
	}

	if emergencyRelation, ok := payload["emergency_relation"]; ok {
		switch value := emergencyRelation.(type) {
		case string:
			trimmed := strings.TrimSpace(value)
			settings.EmergencyRelation = &trimmed
		case nil:
			settings.EmergencyRelation = nil
		default:
			jsonError(w, "Valeur emergency_relation invalide", http.StatusBadRequest)
			return
		}
	}

	if err := ensureSeniorSettingsTable(); err != nil {
		jsonError(w, "Erreur lors de la préparation des préférences", http.StatusInternalServerError)
		return
	}

	_, err = db.Exec(`
		INSERT INTO senior_settings (id_user, language, font_size, email_notifications, emergency_relation, updated_at)
		VALUES (?, ?, ?, ?, ?, NOW())
		ON DUPLICATE KEY UPDATE
			language = VALUES(language),
			font_size = VALUES(font_size),
			email_notifications = VALUES(email_notifications),
			emergency_relation = VALUES(emergency_relation),
			updated_at = NOW()
	`, userID, settings.Language, settings.FontSize, settings.EmailNotifications, settings.EmergencyRelation)
	if err != nil {
		jsonError(w, "Erreur lors de la mise à jour des préférences", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Préférences mises à jour"})
}

//  USER GET 1 SMALL

func handleGetUsersSummary(w http.ResponseWriter, r *http.Request) {
	rolesParam := strings.TrimSpace(r.URL.Query().Get("roles"))
	query := `
		SELECT id_user, first_name, last_name, role, active
		FROM users
		WHERE 1 = 1
	`
	args := []interface{}{}

	if rolesParam != "" {
		roles := []string{}
		for _, role := range strings.Split(rolesParam, ",") {
			role = strings.TrimSpace(role)
			if role == "" {
				continue
			}
			roles = append(roles, strings.ToLower(role))
		}
		if len(roles) > 0 {
			placeholders := make([]string, len(roles))
			for i, role := range roles {
				placeholders[i] = "?"
				args = append(args, role)
			}
			query += " AND LOWER(role) IN (" + strings.Join(placeholders, ",") + ")"
		}
	}

	query += " ORDER BY last_name, first_name"

	rows, err := db.Query(query, args...)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des utilisateurs", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type userSummary struct {
		ID        string `json:"id_user"`
		FirstName string `json:"first_name"`
		LastName  string `json:"last_name"`
		Role      string `json:"role"`
		Active    bool   `json:"active"`
	}

	users := []userSummary{}
	for rows.Next() {
		var user userSummary
		var active sql.NullBool
		if err := rows.Scan(&user.ID, &user.FirstName, &user.LastName, &user.Role, &active); err != nil {
			continue
		}
		user.Active = active.Valid && active.Bool
		users = append(users, user)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(users)
}
