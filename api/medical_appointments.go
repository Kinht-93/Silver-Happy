package main

import (
	"database/sql"
	"encoding/json"
	"io"
	"net/http"
	"time"
)

// MEDICAL APPOINTMENTS Tous
func handleGetMedicalAppointments(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT ma.id_appointment, ma.id_user, ma.appointment_date, ma.appointment_type, ma.doctor_name, 
		       ma.medical_reason_anonymized, ma.notes_internal, ma.status, ma.created_at, ma.updated_at, ma.created_by,
		       u.first_name, u.last_name, u.email,
		       ca.first_name as creator_first, ca.last_name as creator_last
		FROM medical_appointments ma
		INNER JOIN users u ON ma.id_user = u.id_user
		LEFT JOIN users ca ON ma.created_by = ca.id_user
		ORDER BY ma.appointment_date DESC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des RDV", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type AppointmentData struct {
		ID                string         `json:"id_appointment"`
		UserID            string         `json:"id_user"`
		FirstName         string         `json:"first_name"`
		LastName          string         `json:"last_name"`
		Email             string         `json:"email"`
		AppointmentDate   time.Time      `json:"appointment_date"`
		AppointmentType   string         `json:"appointment_type"`
		DoctorName        string         `json:"doctor_name"`
		MedicalReasonAnon string         `json:"medical_reason_anonymized"`
		NotesInternal     string         `json:"notes_internal"`
		Status            string         `json:"status"`
		CreatedAt         time.Time      `json:"created_at"`
		UpdatedAt         sql.NullTime   `json:"updated_at"`
		CreatedBy         sql.NullString `json:"created_by"`
		CreatorFirstName  sql.NullString `json:"creator_first_name"`
		CreatorLastName   sql.NullString `json:"creator_last_name"`
	}

	appointments := []AppointmentData{}
	for rows.Next() {
		var a AppointmentData
		err := rows.Scan(&a.ID, &a.UserID, &a.AppointmentDate, &a.AppointmentType, &a.DoctorName,
			&a.MedicalReasonAnon, &a.NotesInternal, &a.Status, &a.CreatedAt, &a.UpdatedAt, &a.CreatedBy,
			&a.FirstName, &a.LastName, &a.Email,
			&a.CreatorFirstName, &a.CreatorLastName)
		if err != nil {
			continue
		}
		appointments = append(appointments, a)
	}

	if err := rows.Err(); err != nil {
		jsonError(w, "Erreur lors de la lecture des RDV", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(appointments)
}

// MEDICAL APPOINTMENTS un
func handleGetMedicalAppointment(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	type AppointmentData struct {
		ID                string         `json:"id_appointment"`
		UserID            string         `json:"id_user"`
		FirstName         string         `json:"first_name"`
		LastName          string         `json:"last_name"`
		Email             string         `json:"email"`
		AppointmentDate   time.Time      `json:"appointment_date"`
		AppointmentType   string         `json:"appointment_type"`
		DoctorName        string         `json:"doctor_name"`
		MedicalReasonAnon string         `json:"medical_reason_anonymized"`
		NotesInternal     string         `json:"notes_internal"`
		Status            string         `json:"status"`
		CreatedAt         time.Time      `json:"created_at"`
		UpdatedAt         sql.NullTime   `json:"updated_at"`
		CreatedBy         sql.NullString `json:"created_by"`
		CreatorFirstName  sql.NullString `json:"creator_first_name"`
		CreatorLastName   sql.NullString `json:"creator_last_name"`
	}

	row := db.QueryRow(`
		SELECT ma.id_appointment, ma.id_user, ma.appointment_date, ma.appointment_type, ma.doctor_name, 
		       ma.medical_reason_anonymized, ma.notes_internal, ma.status, ma.created_at, ma.updated_at, ma.created_by,
		       u.first_name, u.last_name, u.email,
		       ca.first_name as creator_first, ca.last_name as creator_last
		FROM medical_appointments ma
		INNER JOIN users u ON ma.id_user = u.id_user
		LEFT JOIN users ca ON ma.created_by = ca.id_user
		WHERE ma.id_appointment = ?
	`, id)

	var a AppointmentData
	err := row.Scan(&a.ID, &a.UserID, &a.AppointmentDate, &a.AppointmentType, &a.DoctorName,
		&a.MedicalReasonAnon, &a.NotesInternal, &a.Status, &a.CreatedAt, &a.UpdatedAt, &a.CreatedBy,
		&a.FirstName, &a.LastName, &a.Email,
		&a.CreatorFirstName, &a.CreatorLastName)
	if err != nil {
		jsonError(w, "RDV introuvable", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(a)
}

// MEDICAL APPOINTMENTS +
func handleCreateMedicalAppointment(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var payload map[string]interface{}
	if err := json.Unmarshal(body, &payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO medical_appointments 
		(id_appointment, id_user, appointment_date, appointment_type, doctor_name, 
		 medical_reason_anonymized, notes_internal, status, created_at, created_by)
		VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(
		payload["id_user"],
		payload["appointment_date"],
		payload["appointment_type"],
		payload["doctor_name"],
		"Visite médicale",
		payload["notes_internal"],
		"Programmé",
		payload["created_by"],
	)
	if err != nil {
		createLog("système", "Création de RDV médical", "CREATE", "Erreur lors de la création du RDV: "+err.Error(), false)
		jsonError(w, "Erreur lors de la création du RDV", http.StatusInternalServerError)
		return
	}

	createLog("système", "Création de RDV médical", "CREATE", "RDV créé pour la date: "+payload["appointment_date"].(string)+", Docteur: "+payload["doctor_name"].(string), true)
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "RDV créé avec succès"})
}

// MEDICAL APPOINTMENTS change
func handleUpdateMedicalAppointment(w http.ResponseWriter, r *http.Request) {
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

	stmt, _ := db.Prepare(`
		UPDATE medical_appointments 
		SET appointment_date=?, appointment_type=?, status=?, updated_at=NOW()
		WHERE id_appointment=?
	`)
	defer stmt.Close()

	_, err := stmt.Exec(
		payload["appointment_date"],
		payload["appointment_type"],
		payload["status"],
		id,
	)
	if err != nil {
		createLog("système", "Mise à jour de RDV médical", "UPDATE", "Erreur lors de la mise à jour du RDV "+id+": "+err.Error(), false)
		jsonError(w, "Erreur lors de la mise à jour", http.StatusInternalServerError)
		return
	}

	createLog("système", "Mise à jour de RDV médical", "UPDATE", "RDV mis à jour: "+id+", Nouveau statut: "+payload["status"].(string), true)
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(SuccessResponse{Message: "RDV mis à jour"})
}

// MEDICAL APPOINTMENTS -
func handleDeleteMedicalAppointment(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	result, err := db.Exec("DELETE FROM medical_appointments WHERE id_appointment = ?", id)
	if err != nil {
		createLog("système", "Suppression de RDV médical", "DELETE", "Erreur lors de la suppression du RDV "+id+": "+err.Error(), false)
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}

	affected, _ := result.RowsAffected()
	if affected == 0 {
		createLog("système", "Suppression de RDV médical", "DELETE", "RDV non trouvé: "+id, false)
		jsonError(w, "RDV introuvable", http.StatusNotFound)
		return
	}

	createLog("système", "Suppression de RDV médical", "DELETE", "RDV supprimé: "+id, true)
	w.WriteHeader(http.StatusNoContent)
}

// GET USERS pour sélectionner
func handleGetUsersForAppointments(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT id_user, first_name, last_name 
		FROM users 
		WHERE role IN ('senior', 'prestataire') AND active = TRUE
		ORDER BY last_name ASC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des utilisateurs", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type UserData struct {
		ID        string `json:"id_user"`
		FirstName string `json:"first_name"`
		LastName  string `json:"last_name"`
	}

	users := []UserData{}
	for rows.Next() {
		var u UserData
		err := rows.Scan(&u.ID, &u.FirstName, &u.LastName)
		if err != nil {
			continue
		}
		users = append(users, u)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(users)
}
