package main

import (
	"encoding/json"
	"io"
	"net/http"
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
