package main

import (
	"encoding/json"
	"io"
	"net/http"
)

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
