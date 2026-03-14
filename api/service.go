package main

import (
	"encoding/json"
	"io"
	"net/http"
)

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
