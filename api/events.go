package main

import (
	"database/sql"
	"encoding/json"
	"io"
	"net/http"
	"os"
	"time"

	"github.com/stripe/stripe-go/v76"
	"github.com/stripe/stripe-go/v76/checkout/session"
	"github.com/stripe/stripe-go/v76/refund"
	"github.com/stripe/stripe-go/v76/webhook"
)

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

func handleGetAdminEvents(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT e.id_event, e.title, e.start_date, e.event_type, e.max_places, e.price,
		       COUNT(er.id_registration) as participants
		FROM events e
		LEFT JOIN event_registrations er ON er.id_event = e.id_event
		GROUP BY e.id_event, e.title, e.start_date, e.event_type, e.max_places, e.price
		ORDER BY e.start_date DESC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des événements", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type adminEvent struct {
		ID           string    `json:"id_event"`
		Title        string    `json:"title"`
		StartDate    time.Time `json:"start_date"`
		EventType    string    `json:"event_type"`
		MaxPlaces    int       `json:"max_places"`
		Price        float64   `json:"price"`
		Participants int       `json:"participants"`
	}

	events := []adminEvent{}
	for rows.Next() {
		var event adminEvent
		if err := rows.Scan(&event.ID, &event.Title, &event.StartDate, &event.EventType, &event.MaxPlaces, &event.Price, &event.Participants); err != nil {
			continue
		}
		events = append(events, event)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(events)
}

func handleCreateAdminEvent(w http.ResponseWriter, r *http.Request) {
	var payload struct {
		Title     string  `json:"title"`
		EventType string  `json:"event_type"`
		StartDate string  `json:"start_date"`
		MaxPlaces int     `json:"max_places"`
		Price     float64 `json:"price"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	if payload.Title == "" || payload.StartDate == "" {
		jsonError(w, "Champs requis manquants", http.StatusBadRequest)
		return
	}
	if payload.MaxPlaces <= 0 {
		payload.MaxPlaces = 20
	}

	_, err := db.Exec(`
		INSERT INTO events (id_event, title, event_type, start_date, end_date, max_places, price)
		VALUES (CONCAT('evt_', UUID()), ?, ?, ?, DATE_ADD(?, INTERVAL 2 HOUR), ?, ?)
	`, payload.Title, payload.EventType, payload.StartDate, payload.StartDate, payload.MaxPlaces, payload.Price)
	if err != nil {
		jsonError(w, "Erreur lors de la création", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Événement créé avec succès"})
}

func handleUpdateAdminEvent(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	var payload struct {
		Title     string  `json:"title"`
		StartDate string  `json:"start_date"`
		MaxPlaces int     `json:"max_places"`
		Price     float64 `json:"price"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	if payload.Title == "" || payload.StartDate == "" {
		jsonError(w, "Champs requis manquants", http.StatusBadRequest)
		return
	}
	if payload.MaxPlaces <= 0 {
		payload.MaxPlaces = 20
	}

	_, err := db.Exec(`
		UPDATE events
		SET title = ?, start_date = ?, end_date = DATE_ADD(?, INTERVAL 2 HOUR), max_places = ?, price = ?
		WHERE id_event = ?
	`, payload.Title, payload.StartDate, payload.StartDate, payload.MaxPlaces, payload.Price, id)
	if err != nil {
		jsonError(w, "Erreur lors de la mise à jour", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Événement mis à jour avec succès"})
}

func handleDeleteAdminEvent(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	_, err := db.Exec("DELETE FROM events WHERE id_event = ?", id)
	if err != nil {
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}

	w.WriteHeader(http.StatusNoContent)
}

func queryEventRegistrations(idUser string) (*sql.Rows, error) {
	query := `
		SELECT id_registration, registration_date, status, paid, id_user, id_event
		FROM event_registrations
	`
	args := []interface{}{}
	if idUser != "" {
		query += " WHERE id_user = ?"
		args = append(args, idUser)
	}
	query += " ORDER BY registration_date DESC"

	return db.Query(query, args...)
}

// EVENT REGISTRATIONS tous
func handleGetEventRegistrations(w http.ResponseWriter, r *http.Request) {
	idUser := r.URL.Query().Get("id_user")
	rows, err := queryEventRegistrations(idUser)
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

func handleGetUserEventRegistrations(w http.ResponseWriter, r *http.Request) {
	idUser := r.PathValue("id")
	if idUser == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	rows, err := queryEventRegistrations(idUser)
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

// STRIPE PAY
func handleCreateEventCheckout(w http.ResponseWriter, r *http.Request) {
	idEvent := r.PathValue("id")
	if idEvent == "" {
		jsonError(w, "ID événement manquant", http.StatusBadRequest)
		return
	}

	var body struct {
		UserID string `json:"id_user"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil || body.UserID == "" {
		jsonError(w, "JSON invalide ou id_user manquant", http.StatusBadRequest)
		return
	}

	var e Event
	err := db.QueryRow(`
		SELECT id_event, title, event_type, start_date, end_date, max_places, price
		FROM events WHERE id_event = ?
	`, idEvent).Scan(&e.ID, &e.Title, &e.EventType, &e.StartDate, &e.EndDate, &e.MaxPlaces, &e.Price)
	if err != nil {
		jsonError(w, "Événement introuvable", http.StatusNotFound)
		return
	}

	var existing int
	db.QueryRow(`
		SELECT COUNT(*) FROM event_registrations WHERE id_user = ? AND id_event = ?
	`, body.UserID, idEvent).Scan(&existing)
	if existing > 0 {
		jsonError(w, "Vous êtes déjà inscrit à cet événement", http.StatusConflict)
		return
	}

	var registrationCount int
	db.QueryRow(`SELECT COUNT(*) FROM event_registrations WHERE id_event = ?`, idEvent).Scan(&registrationCount)
	if registrationCount >= e.MaxPlaces {
		jsonError(w, "Plus de places disponibles pour cet événement", http.StatusConflict)
		return
	}

	baseURL := os.Getenv("APP_BASE_URL")
	if baseURL == "" {
		baseURL = "http://localhost/Silver-Happy"
	}

	if e.Price == 0 {
		_, err := db.Exec(`
			INSERT INTO event_registrations (id_registration, registration_date, status, paid, stripe_payment_intent_id, id_user, id_event)
			VALUES (UUID(), NOW(), 'confirmed', 1, NULL, ?, ?)
		`, body.UserID, idEvent)
		if err != nil {
			jsonError(w, "Erreur lors de l'inscription", http.StatusInternalServerError)
			return
		}
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusCreated)
		json.NewEncoder(w).Encode(map[string]string{
			"message":      "Inscription confirmée (événement gratuit)",
			"checkout_url": "",
		})
		return
	}

	params := &stripe.CheckoutSessionParams{
		Mode: stripe.String(string(stripe.CheckoutSessionModePayment)),
		LineItems: []*stripe.CheckoutSessionLineItemParams{
			{
				PriceData: &stripe.CheckoutSessionLineItemPriceDataParams{
					Currency: stripe.String("eur"),
					ProductData: &stripe.CheckoutSessionLineItemPriceDataProductDataParams{
						Name: stripe.String(e.Title),
					},
					UnitAmount: stripe.Int64(int64(e.Price * 100)),
				},
				Quantity: stripe.Int64(1),
			},
		},
		Metadata: map[string]string{
			"id_event": idEvent,
			"id_user":  body.UserID,
		},
		SuccessURL: stripe.String(baseURL + "/senior/evenements-inscriptions.php?payment=success&session_id={CHECKOUT_SESSION_ID}"),
		CancelURL:  stripe.String(baseURL + "/senior/evenements.php?payment=cancelled"),
	}

	s, err := session.New(params)
	if err != nil {
		jsonError(w, "Erreur lors de la création de la session Stripe : "+err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{
		"checkout_url": s.URL,
	})
}

func handleConfirmEventCheckout(w http.ResponseWriter, r *http.Request) {
	sessionID := r.URL.Query().Get("session_id")
	if sessionID == "" {
		jsonError(w, "session_id manquant", http.StatusBadRequest)
		return
	}

	s, err := session.Get(sessionID, nil)
	if err != nil {
		jsonError(w, "Impossible de récupérer la session Stripe : "+err.Error(), http.StatusBadRequest)
		return
	}

	if s.PaymentStatus != stripe.CheckoutSessionPaymentStatusPaid {
		jsonError(w, "Paiement Stripe non confirmé", http.StatusConflict)
		return
	}

	idEvent := s.Metadata["id_event"]
	idUser := s.Metadata["id_user"]
	if idEvent == "" || idUser == "" {
		jsonError(w, "Metadata Stripe manquante", http.StatusBadRequest)
		return
	}

	var existing int
	db.QueryRow(`
		SELECT COUNT(*) FROM event_registrations WHERE id_user = ? AND id_event = ?
	`, idUser, idEvent).Scan(&existing)
	if existing > 0 {
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]interface{}{
			"success": true,
			"message": "Inscription déjà confirmée",
		})
		return
	}

	paymentIntentID := ""
	if s.PaymentIntent.ID != "" {
		paymentIntentID = s.PaymentIntent.ID
	}

	_, err = db.Exec(`
		INSERT INTO event_registrations (id_registration, registration_date, status, paid, stripe_payment_intent_id, id_user, id_event)
		VALUES (UUID(), NOW(), 'confirmed', 1, ?, ?, ?)
	`, paymentIntentID, idUser, idEvent)
	if err != nil {
		jsonError(w, "Erreur lors de la validation de l'inscription : "+err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"message": "Inscription confirmée",
	})
}

// STRIPE CONFIRM
func handleStripeWebhook(w http.ResponseWriter, r *http.Request) {
	const maxBodyBytes = int64(65536)
	r.Body = http.MaxBytesReader(w, r.Body, maxBodyBytes)
	payload, err := io.ReadAll(r.Body)
	if err != nil {
		jsonError(w, "Erreur lecture body", http.StatusBadRequest)
		return
	}

	webhookSecret := os.Getenv("STRIPE_WEBHOOK_SECRET")
	event, err := webhook.ConstructEvent(payload, r.Header.Get("Stripe-Signature"), webhookSecret)
	if err != nil {
		jsonError(w, "Signature Stripe invalide : "+err.Error(), http.StatusBadRequest)
		return
	}

	if event.Type == "checkout.session.completed" {
		var checkoutSession stripe.CheckoutSession
		if err := json.Unmarshal(event.Data.Raw, &checkoutSession); err != nil {
			jsonError(w, "Erreur parsing session", http.StatusBadRequest)
			return
		}

		idEvent := checkoutSession.Metadata["id_event"]
		idUser := checkoutSession.Metadata["id_user"]

		if idEvent == "" || idUser == "" {
			w.WriteHeader(http.StatusOK)
			return
		}

		var existing int
		db.QueryRow(`
			SELECT COUNT(*) FROM event_registrations WHERE id_user = ? AND id_event = ?
		`, idUser, idEvent).Scan(&existing)

		if existing == 0 {
			paymentIntentID := checkoutSession.PaymentIntent.ID
			_, err = db.Exec(`
				INSERT INTO event_registrations (id_registration, registration_date, status, paid, stripe_payment_intent_id, id_user, id_event)
				VALUES (UUID(), NOW(), 'confirmed', 1, ?, ?, ?)
			`, paymentIntentID, idUser, idEvent)
			if err != nil {
				w.WriteHeader(http.StatusOK)
				return
			}
		}
	}

	w.WriteHeader(http.StatusOK)
}

// DELETE INSCRIPTION EVENT
func handleDeleteEventRegistration(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID inscription manquant", http.StatusBadRequest)
		return
	}

	var paid bool
	var stripePaymentIntentID *string
	err := db.QueryRow(`
		SELECT paid, stripe_payment_intent_id FROM event_registrations WHERE id_registration = ?
	`, id).Scan(&paid, &stripePaymentIntentID)
	if err != nil {
		jsonError(w, "Inscription introuvable", http.StatusNotFound)
		return
	}

	if paid && stripePaymentIntentID != nil && *stripePaymentIntentID != "" {
		refundParams := &stripe.RefundParams{
			PaymentIntent: stripe.String(*stripePaymentIntentID),
		}
		_, err := refund.New(refundParams)
		if err != nil {
			stripeErr, ok := err.(*stripe.Error)
			if ok && stripeErr.Code == "charge_already_refunded" {
			} else {
				jsonError(w, "Erreur lors du remboursement : "+err.Error(), http.StatusInternalServerError)
				return
			}
		}
	}

	result, err := db.Exec("DELETE FROM event_registrations WHERE id_registration = ?", id)
	if err != nil {
		jsonError(w, "Erreur lors de la suppression de l'inscription", http.StatusInternalServerError)
		return
	}

	affected, _ := result.RowsAffected()
	if affected == 0 {
		jsonError(w, "Inscription introuvable", http.StatusNotFound)
		return
	}

	w.WriteHeader(http.StatusNoContent)
}
