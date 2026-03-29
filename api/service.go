package main

import (
	"database/sql"
	"encoding/json"
	"io"
	"net/http"
	"strings"
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
	categoryID := r.URL.Query().Get("id_service_category")
	query := `
		SELECT id_service_type, name, description, hourly_rate, certification_required, id_service_category
		FROM service_types
	`
	args := []interface{}{}
	if categoryID != "" {
		query += " WHERE id_service_category = ?"
		args = append(args, categoryID)
	}
	query += " ORDER BY name"

	rows, err := db.Query(query, args...)
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
	idUser := r.URL.Query().Get("id_user")
	status := r.URL.Query().Get("status")
	rows, err := queryServiceRequests(idUser, status)
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

func handleGetUserServiceRequests(w http.ResponseWriter, r *http.Request) {
	idUser := r.PathValue("id")
	if idUser == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	status := r.URL.Query().Get("status")
	rows, err := queryServiceRequests(idUser, status)
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

func handleGetProviderAvailabilities(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT pa.id_availability, pa.available_date, pa.start_time, pa.end_time,
		       u.id_user, u.first_name, u.last_name,
		       COALESCE(u.company_name, '') as company_name
		FROM provider_availabilities pa
		INNER JOIN users u ON u.id_user = pa.id_user
		WHERE pa.is_available = 1
		  AND LOWER(u.role) = 'prestataire'
		  AND (pa.available_date > CURDATE() OR (pa.available_date = CURDATE() AND pa.start_time > CURTIME()))
		ORDER BY pa.available_date ASC, pa.start_time ASC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des disponibilités", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type availabilityData struct {
		ID          int    `json:"id_availability"`
		AvailableAt string `json:"available_date"`
		StartTime   string `json:"start_time"`
		EndTime     string `json:"end_time"`
		ProviderID  string `json:"id_user"`
		FirstName   string `json:"first_name"`
		LastName    string `json:"last_name"`
		CompanyName string `json:"company_name"`
	}

	items := []availabilityData{}
	for rows.Next() {
		var item availabilityData
		if err := rows.Scan(&item.ID, &item.AvailableAt, &item.StartTime, &item.EndTime, &item.ProviderID, &item.FirstName, &item.LastName, &item.CompanyName); err != nil {
			continue
		}
		items = append(items, item)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(items)
}

func handleReserveProviderAvailability(w http.ResponseWriter, r *http.Request) {
	availabilityID := r.PathValue("id")
	if availabilityID == "" {
		jsonError(w, "ID disponibilité manquant", http.StatusBadRequest)
		return
	}

	var payload struct {
		UserID            string `json:"id_user"`
		ServiceCategoryID string `json:"id_service_category"`
		CategoryName      string `json:"category_name"`
		SeniorName        string `json:"senior_name"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}
	if payload.UserID == "" || payload.ServiceCategoryID == "" {
		jsonError(w, "Champs requis manquants", http.StatusBadRequest)
		return
	}

	tx, err := db.Begin()
	if err != nil {
		jsonError(w, "Erreur transaction", http.StatusInternalServerError)
		return
	}
	defer tx.Rollback()

	var slot struct {
		ProviderID   string
		AvailableDay string
		StartTime    string
		EndTime      string
		CompanyName  sql.NullString
		FirstName    sql.NullString
		LastName     sql.NullString
	}
	err = tx.QueryRow(`
		SELECT pa.id_user, pa.available_date, pa.start_time, pa.end_time,
		       u.company_name, u.first_name, u.last_name
		FROM provider_availabilities pa
		INNER JOIN users u ON u.id_user = pa.id_user
		WHERE pa.id_availability = ?
		  AND pa.is_available = 1
		  AND LOWER(u.role) = 'prestataire'
		  AND (pa.available_date > CURDATE() OR (pa.available_date = CURDATE() AND pa.start_time > CURTIME()))
		LIMIT 1
	`, availabilityID).Scan(&slot.ProviderID, &slot.AvailableDay, &slot.StartTime, &slot.EndTime, &slot.CompanyName, &slot.FirstName, &slot.LastName)
	if err != nil {
		jsonError(w, "Ce créneau n'est plus disponible", http.StatusConflict)
		return
	}

	result, err := tx.Exec("UPDATE provider_availabilities SET is_available = 0 WHERE id_availability = ? AND is_available = 1", availabilityID)
	if err != nil {
		jsonError(w, "Erreur lors du verrouillage du créneau", http.StatusInternalServerError)
		return
	}
	affected, _ := result.RowsAffected()
	if affected != 1 {
		jsonError(w, "Ce créneau vient d'être réservé", http.StatusConflict)
		return
	}

	var durationSeconds int
	if err := tx.QueryRow("SELECT TIMESTAMPDIFF(SECOND, ?, ?)", slot.StartTime, slot.EndTime).Scan(&durationSeconds); err != nil {
		durationSeconds = 3600
	}
	durationHours := durationSeconds / 3600
	if durationSeconds%3600 != 0 {
		durationHours++
	}
	if durationHours < 1 {
		durationHours = 1
	}

	providerLabel := "Prestataire"
	fullName := strings.TrimSpace(slot.FirstName.String + " " + slot.LastName.String)
	if fullName != "" {
		providerLabel = fullName
	} else if slot.CompanyName.Valid && slot.CompanyName.String != "" {
		providerLabel = slot.CompanyName.String
	}

	requestAddress := "Service: " + payload.CategoryName + " | Prestataire: " + providerLabel
	if _, err := tx.Exec(`
		INSERT INTO service_requests (id_request, desired_date, start_time, estimated_duration, intervention_address, status, created_at, id_user, id_service_category)
		VALUES (CONCAT('req_', UUID()), ?, ?, ?, ?, 'En attente', NOW(), ?, ?)
	`, slot.AvailableDay, slot.StartTime, durationHours, requestAddress, payload.UserID, payload.ServiceCategoryID); err != nil {
		jsonError(w, "Erreur lors de la création de la demande", http.StatusInternalServerError)
		return
	}

	missionDescription := "Senior: " + payload.SeniorName + " | Service: " + payload.CategoryName + " | Créneau: " + slot.AvailableDay + " " + slot.StartTime + "-" + slot.EndTime
	if _, err := tx.Exec(`
		INSERT INTO provider_missions (id_mission, mission_title, mission_description, mission_date, status, id_user, accepted_at, created_at)
		VALUES (CONCAT('mis_', UUID()), ?, ?, ?, 'Acceptee', ?, NOW(), NOW())
	`, "Demande senior - "+payload.CategoryName, missionDescription, slot.AvailableDay, slot.ProviderID); err != nil {
		jsonError(w, "Erreur lors de la création de la mission", http.StatusInternalServerError)
		return
	}

	if err := tx.Commit(); err != nil {
		jsonError(w, "Erreur transaction", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Créneau réservé avec succès"})
}

func handleGetUserCompletedServices(w http.ResponseWriter, r *http.Request) {
	idUser := r.PathValue("id")
	if idUser == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	rows, err := db.Query(`
		SELECT cs.id_completed_service, cs.service_date, cs.start_time, cs.end_time, cs.senior_amount, cs.status,
		       COALESCE(st.name, sc.name) as prestation_name
		FROM completed_services cs
		JOIN service_requests sr ON cs.id_request = sr.id_request
		JOIN service_categories sc ON sr.id_service_category = sc.id_service_category
		LEFT JOIN show_type sht ON sr.id_request = sht.id_request
		LEFT JOIN service_types st ON sht.id_service_type = st.id_service_type
		WHERE sr.id_user = ?
		ORDER BY cs.service_date DESC, cs.start_time DESC
	`, idUser)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des prestations réalisées", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type completedData struct {
		ID             string  `json:"id_completed_service"`
		ServiceDate    string  `json:"service_date"`
		StartTime      string  `json:"start_time"`
		EndTime        string  `json:"end_time"`
		SeniorAmount   float64 `json:"senior_amount"`
		Status         string  `json:"status"`
		PrestationName string  `json:"prestation_name"`
	}

	services := []completedData{}
	for rows.Next() {
		var item completedData
		if err := rows.Scan(&item.ID, &item.ServiceDate, &item.StartTime, &item.EndTime, &item.SeniorAmount, &item.Status, &item.PrestationName); err != nil {
			continue
		}
		services = append(services, item)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(services)
}

// SERVICE COMPLETED COUNT
func handleGetCompletedServiceCount(w http.ResponseWriter, r *http.Request) {
	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM completed_services WHERE status = 'Terminé'").Scan(&count)
	if err != nil {
		jsonError(w, "Erreur lors du comptage des utilisateurs", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"count": count,
	})
}

func queryServiceRequests(idUser string, status string) (*sql.Rows, error) {
	query := `
		SELECT id_request, desired_date, start_time, estimated_duration, intervention_address,
		       status, created_at, id_user, id_service_category
		FROM service_requests
		WHERE 1=1
	`
	args := []interface{}{}
	if idUser != "" {
		query += " AND id_user = ?"
		args = append(args, idUser)
	}
	if status != "" {
		query += " AND status = ?"
		args = append(args, status)
	}
	query += " ORDER BY created_at DESC"
	return db.Query(query, args...)
}
