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

// SENIORS tous
func handleGetSeniors(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT id_senior, membership_number, subscription_date, emergency_contact_name,
		       emergency_contact_phone, mobility FROM seniors
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des seniors", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	seniors := []Senior{}
	for rows.Next() {
		var s Senior
		if err := rows.Scan(&s.ID, &s.MembershipNumber, &s.SubscriptionDate,
			&s.EmergencyContactName, &s.EmergencyContactPhone, &s.Mobility); err != nil {
			continue
		}
		seniors = append(seniors, s)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(seniors)
}

// SENIORS un
func handleGetSenior(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	row := db.QueryRow(`
		SELECT id_senior, membership_number, subscription_date, emergency_contact_name,
		       emergency_contact_phone, mobility FROM seniors WHERE id_senior = ?
	`, id)

	var s Senior
	if err := row.Scan(&s.ID, &s.MembershipNumber, &s.SubscriptionDate,
		&s.EmergencyContactName, &s.EmergencyContactPhone, &s.Mobility); err != nil {
		jsonError(w, "Senior introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(s)
}

// SENIORS +
func handleCreateSenior(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var s Senior
	if err := json.Unmarshal(body, &s); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO seniors (id_senior, membership_number, subscription_date, 
						     emergency_contact_name, emergency_contact_phone, mobility)
		VALUES (UUID(), ?, ?, ?, ?, ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(s.MembershipNumber, s.SubscriptionDate,
		s.EmergencyContactName, s.EmergencyContactPhone, s.Mobility)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Senior créé avec succès"})
}

// SERVICE CATEGORIES tous
func handleGetServiceCategories(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`SELECT id_service_category, name, description FROM service_categories`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des catégories", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	categories := []ServiceCategory{}
	for rows.Next() {
		var c ServiceCategory
		if err := rows.Scan(&c.ID, &c.Name, &c.Description); err != nil {
			continue
		}
		categories = append(categories, c)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(categories)
}

// SERVICE CATEGORIES un
func handleGetServiceCategory(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	row := db.QueryRow(`SELECT id_service_category, name, description FROM service_categories WHERE id_service_category = ?`, id)

	var c ServiceCategory
	if err := row.Scan(&c.ID, &c.Name, &c.Description); err != nil {
		jsonError(w, "Catégorie introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(c)
}

// SERVICE CATEGORIES +
func handleCreateServiceCategory(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var c ServiceCategory
	if err := json.Unmarshal(body, &c); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO service_categories (id_service_category, name, description)
		VALUES (UUID(), ?, ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(c.Name, c.Description)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Catégorie créée avec succès"})
}

// SERVICE TYPES tous
func handleGetServiceTypes(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT id_service_type, name, description, hourly_rate, certification_required, id_service_category
		FROM service_types
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des types de service", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	serviceTypes := []ServiceType{}
	for rows.Next() {
		var st ServiceType
		if err := rows.Scan(&st.ID, &st.Name, &st.Description, &st.HourlyRate,
			&st.CertificationRequired, &st.ServiceCategoryID); err != nil {
			continue
		}
		serviceTypes = append(serviceTypes, st)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(serviceTypes)
}

// SERVICE TYPES un
func handleGetServiceType(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	row := db.QueryRow(`
		SELECT id_service_type, name, description, hourly_rate, certification_required, id_service_category
		FROM service_types WHERE id_service_type = ?
	`, id)

	var st ServiceType
	if err := row.Scan(&st.ID, &st.Name, &st.Description, &st.HourlyRate,
		&st.CertificationRequired, &st.ServiceCategoryID); err != nil {
		jsonError(w, "Type de service introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(st)
}

// SERVICE TYPES +
func handleCreateServiceType(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var st ServiceType
	if err := json.Unmarshal(body, &st); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO service_types (id_service_type, name, description, hourly_rate, certification_required, id_service_category)
		VALUES (UUID(), ?, ?, ?, ?, ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(st.Name, st.Description, st.HourlyRate, st.CertificationRequired, st.ServiceCategoryID)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Type de service créé avec succès"})
}

// SERVICE REQUESTS tous
func handleGetServiceRequests(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT id_request, desired_date, start_time, estimated_duration, intervention_address,
		       status, created_at, id_user, id_service_category
		FROM service_requests
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des demandes", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	requests := []ServiceRequest{}
	for rows.Next() {
		var sr ServiceRequest
		if err := rows.Scan(&sr.ID, &sr.DesiredDate, &sr.StartTime, &sr.EstimatedDuration,
			&sr.InterventionAddress, &sr.Status, &sr.CreatedAt, &sr.UserID, &sr.ServiceCategoryID); err != nil {
			continue
		}
		requests = append(requests, sr)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(requests)
}

// SERVICE REQUESTS un
func handleGetServiceRequest(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	row := db.QueryRow(`
		SELECT id_request, desired_date, start_time, estimated_duration, intervention_address,
		       status, created_at, id_user, id_service_category
		FROM service_requests WHERE id_request = ?
	`, id)

	var sr ServiceRequest
	if err := row.Scan(&sr.ID, &sr.DesiredDate, &sr.StartTime, &sr.EstimatedDuration,
		&sr.InterventionAddress, &sr.Status, &sr.CreatedAt, &sr.UserID, &sr.ServiceCategoryID); err != nil {
		jsonError(w, "Demande introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(sr)
}

// SERVICE REQUESTS +
func handleCreateServiceRequest(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var sr ServiceRequest
	if err := json.Unmarshal(body, &sr); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO service_requests (id_request, desired_date, start_time, estimated_duration,
									   intervention_address, status, created_at, id_user, id_service_category)
		VALUES (UUID(), ?, ?, ?, ?, ?, NOW(), ?, ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(sr.DesiredDate, sr.StartTime, sr.EstimatedDuration,
		sr.InterventionAddress, sr.Status, sr.UserID, sr.ServiceCategoryID)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Demande créée avec succès"})
}

// QUOTES tous
func handleGetQuotes(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT id_quote, quote_number, amount_excl_tax, tax_rate, amount_incl_tax, status, created_at, id_request
		FROM quotes
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des devis", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	quotes := []Quote{}
	for rows.Next() {
		var q Quote
		if err := rows.Scan(&q.ID, &q.QuoteNumber, &q.AmountExclTax, &q.TaxRate, &q.AmountInclTax,
			&q.Status, &q.CreatedAt, &q.RequestID); err != nil {
			continue
		}
		quotes = append(quotes, q)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(quotes)
}

// QUOTES un
func handleGetQuote(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	row := db.QueryRow(`
		SELECT id_quote, quote_number, amount_excl_tax, tax_rate, amount_incl_tax, status, created_at, id_request
		FROM quotes WHERE id_quote = ?
	`, id)

	var q Quote
	if err := row.Scan(&q.ID, &q.QuoteNumber, &q.AmountExclTax, &q.TaxRate, &q.AmountInclTax,
		&q.Status, &q.CreatedAt, &q.RequestID); err != nil {
		jsonError(w, "Devis introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(q)
}

// QUOTES +
func handleCreateQuote(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var q Quote
	if err := json.Unmarshal(body, &q); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO quotes (id_quote, quote_number, amount_excl_tax, tax_rate, amount_incl_tax, status, created_at, id_request)
		VALUES (UUID(), ?, ?, ?, ?, ?, NOW(), ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(q.QuoteNumber, q.AmountExclTax, q.TaxRate, q.AmountInclTax, q.Status, q.RequestID)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Devis créé avec succès"})
}

// EVENTS tous
func handleGetEvents(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT id_event, title, event_type, start_date, end_date, max_places, price FROM events
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des événements", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	events := []Event{}
	for rows.Next() {
		var e Event
		if err := rows.Scan(&e.ID, &e.Title, &e.EventType, &e.StartDate, &e.EndDate, &e.MaxPlaces, &e.Price); err != nil {
			continue
		}
		events = append(events, e)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(events)
}

// EVENTS un
func handleGetEvent(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	row := db.QueryRow(`
		SELECT id_event, title, event_type, start_date, end_date, max_places, price FROM events WHERE id_event = ?
	`, id)

	var e Event
	if err := row.Scan(&e.ID, &e.Title, &e.EventType, &e.StartDate, &e.EndDate, &e.MaxPlaces, &e.Price); err != nil {
		jsonError(w, "Événement introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(e)
}

// EVENTS +
func handleCreateEvent(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var e Event
	if err := json.Unmarshal(body, &e); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO events (id_event, title, event_type, start_date, end_date, max_places, price)
		VALUES (UUID(), ?, ?, ?, ?, ?, ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(e.Title, e.EventType, e.StartDate, e.EndDate, e.MaxPlaces, e.Price)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Événement créé avec succès"})
}

// EVENT REGISTRATIONS tous
func handleGetEventRegistrations(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT id_registration, registration_date, status, paid, id_user, id_event
		FROM event_registrations
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des inscriptions", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	registrations := []EventRegistration{}
	for rows.Next() {
		var er EventRegistration
		if err := rows.Scan(&er.ID, &er.RegistrationDate, &er.Status, &er.Paid, &er.UserID, &er.EventID); err != nil {
			continue
		}
		registrations = append(registrations, er)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(registrations)
}

// EVENT REGISTRATIONS +
func handleCreateEventRegistration(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var er EventRegistration
	if err := json.Unmarshal(body, &er); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO event_registrations (id_registration, registration_date, status, paid, id_user, id_event)
		VALUES (UUID(), NOW(), ?, ?, ?, ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(er.Status, er.Paid, er.UserID, er.EventID)
	if err != nil {
		jsonError(w, "Erreur lors de l'inscription", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Inscription créée avec succès"})
}

// MESSAGES tous
func handleGetMessages(w http.ResponseWriter, r *http.Request) {
	receiver := r.URL.Query().Get("receiver")
	if receiver == "" {
		jsonError(w, "Paramètre receiver requis", http.StatusBadRequest)
		return
	}

	rows, err := db.Query(`
		SELECT id_message, content, sent_at, receiver, sender FROM messages WHERE receiver = ? ORDER BY sent_at DESC
	`, receiver)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des messages", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	messages := []Message{}
	for rows.Next() {
		var m Message
		if err := rows.Scan(&m.ID, &m.Content, &m.SentAt, &m.Receiver, &m.Sender); err != nil {
			continue
		}
		messages = append(messages, m)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(messages)
}

// MESSAGES - SEND
func handleSendMessage(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var m Message
	if err := json.Unmarshal(body, &m); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	if m.Sender == "" || m.Receiver == "" || m.Content == "" {
		jsonError(w, "Champs obligatoires manquants", http.StatusUnprocessableEntity)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO messages (id_message, content, sent_at, receiver, sender)
		VALUES (UUID(), ?, NOW(), ?, ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(m.Content, m.Receiver, m.Sender)
	if err != nil {
		jsonError(w, "Erreur lors de l'envoi du message", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Message envoyé avec succès"})
}

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
