package main

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
)

// Helper functions
func getUUID() string {
	return fmt.Sprintf("%d", len([]byte("uuid")))
}

func jsonError(w http.ResponseWriter, msg string, code int) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(code)
	json.NewEncoder(w).Encode(ErrorResponse{Error: msg})
}

// USERS
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
	json.NewEncoder(w).Encode(map[string]string{"message": "Utilisateur créé avec succès"})
}

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
	query := fmt.Sprintf("UPDATE users SET %s WHERE id_user = ?", strings.Join(updates, ", "))

	_, err := db.Exec(query, args...)
	if err != nil {
		jsonError(w, "Erreur lors de la mise à jour", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "Utilisateur mis à jour"})
}

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

// SENIORS
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
	json.NewEncoder(w).Encode(map[string]string{"message": "Senior créé avec succès"})
}

// SERVICE CATEGORIES
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
	json.NewEncoder(w).Encode(map[string]string{"message": "Catégorie créée avec succès"})
}

// SERVICE TYPES
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
	json.NewEncoder(w).Encode(map[string]string{"message": "Type de service créé avec succès"})
}

// SERVICE REQUESTS
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
	json.NewEncoder(w).Encode(map[string]string{"message": "Demande créée avec succès"})
}

// QUOTES
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
	json.NewEncoder(w).Encode(map[string]string{"message": "Devis créé avec succès"})
}

// EVENTS
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
	json.NewEncoder(w).Encode(map[string]string{"message": "Événement créé avec succès"})
}

// EVENT REGISTRATIONS
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
	json.NewEncoder(w).Encode(map[string]string{"message": "Inscription créée avec succès"})
}

// MESSAGES
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
	json.NewEncoder(w).Encode(map[string]string{"message": "Message envoyé avec succès"})
}

// INVOICES
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
	json.NewEncoder(w).Encode(map[string]string{"message": "Facture créée avec succès"})
}
