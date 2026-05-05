package main

import (
	"encoding/json"
	"io"
	"net/http"
)

// SERVICE CATEGORIES CRUD

// GET Toutes les catégories
func handleGetAllServiceCategories(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT c.id_service_category, c.name, c.description,
		       (SELECT COUNT(*) FROM service_types st WHERE st.id_service_category = c.id_service_category) as prestations
		FROM service_categories c
		ORDER BY c.name
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des catégories", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type CategoryData struct {
		ID          string `json:"id_service_category"`
		Name        string `json:"name"`
		Description string `json:"description"`
		Prestations int    `json:"prestations"`
	}

	categories := []CategoryData{}
	for rows.Next() {
		var c CategoryData
		err := rows.Scan(&c.ID, &c.Name, &c.Description, &c.Prestations)
		if err != nil {
			continue
		}
		categories = append(categories, c)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(categories)
}

// UPDATE Catégorie
func handleUpdateServiceCategory(w http.ResponseWriter, r *http.Request) {
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
		UPDATE service_categories 
		SET name=?, description=?
		WHERE id_service_category=?
	`)
	defer stmt.Close()

	_, err := stmt.Exec(payload["name"], payload["description"], id)
	if err != nil {
		createLog("système", "Mise à jour de catégorie de service", "UPDATE", "Erreur lors de la mise à jour de la catégorie "+id+": "+err.Error(), false)
		jsonError(w, "Erreur lors de la mise à jour", http.StatusInternalServerError)
		return
	}

	createLog("système", "Mise à jour de catégorie de service", "UPDATE", "Catégorie mise à jour: "+payload["name"].(string), true)
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Catégorie mise à jour"})
}

// DELETE Catégorie
func handleDeleteServiceCategory(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	result, err := db.Exec("DELETE FROM service_categories WHERE id_service_category = ?", id)
	if err != nil {
		createLog("système", "Suppression de catégorie de service", "DELETE", "Erreur lors de la suppression de la catégorie "+id+": "+err.Error(), false)
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}

	affected, _ := result.RowsAffected()
	if affected == 0 {
		createLog("système", "Suppression de catégorie de service", "DELETE", "Catégorie non trouvée: "+id, false)
		jsonError(w, "Catégorie introuvable", http.StatusNotFound)
		return
	}

	createLog("système", "Suppression de catégorie de service", "DELETE", "Catégorie supprimée: "+id, true)
	w.WriteHeader(http.StatusNoContent)
}

// GET Tous les types de services
func handleGetAllServiceTypes(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT st.id_service_type, st.name, st.description, st.hourly_rate, st.certification_required,
		       st.id_service_category, sc.name as category_name,
		       (SELECT COUNT(*) FROM service_requests sr INNER JOIN show_type sht ON sr.id_request = sht.id_request WHERE sht.id_service_type = st.id_service_type) as prestations
		FROM service_types st
		LEFT JOIN service_categories sc ON st.id_service_category = sc.id_service_category
		ORDER BY sc.name, st.name
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des types", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type TypeData struct {
		ID                    string  `json:"id_service_type"`
		Name                  string  `json:"name"`
		Description           string  `json:"description"`
		HourlyRate            float64 `json:"hourly_rate"`
		CertificationRequired bool    `json:"certification_required"`
		ServiceCategoryID     string  `json:"id_service_category"`
		CategoryName          string  `json:"category_name"`
		Prestations           int     `json:"prestations"`
	}

	types := []TypeData{}
	for rows.Next() {
		var t TypeData
		err := rows.Scan(&t.ID, &t.Name, &t.Description, &t.HourlyRate, &t.CertificationRequired,
			&t.ServiceCategoryID, &t.CategoryName, &t.Prestations)
		if err != nil {
			continue
		}
		types = append(types, t)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(types)
}

// UPDATE Type de service
func handleUpdateServiceType(w http.ResponseWriter, r *http.Request) {
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
		UPDATE service_types 
		SET name=?, description=?, hourly_rate=?, id_service_category=?
		WHERE id_service_type=?
	`)
	defer stmt.Close()

	_, err := stmt.Exec(payload["name"], payload["description"], payload["hourly_rate"],
		payload["id_service_category"], id)
	if err != nil {
		createLog("système", "Mise à jour de type de service", "UPDATE", "Erreur lors de la mise à jour du type "+id+": "+err.Error(), false)
		jsonError(w, "Erreur lors de la mise à jour", http.StatusInternalServerError)
		return
	}

	createLog("système", "Mise à jour de type de service", "UPDATE", "Type de service mis à jour: "+payload["name"].(string), true)
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Type mis à jour"})
}

// DELETE Type de service
func handleDeleteServiceType(w http.ResponseWriter, r *http.Request) {
	id := r.PathValue("id")
	if id == "" {
		jsonError(w, "ID manquant", http.StatusBadRequest)
		return
	}

	result, err := db.Exec("DELETE FROM service_types WHERE id_service_type = ?", id)
	if err != nil {
		createLog("système", "Suppression de type de service", "DELETE", "Erreur lors de la suppression du type "+id+": "+err.Error(), false)
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}

	affected, _ := result.RowsAffected()
	if affected == 0 {
		createLog("système", "Suppression de type de service", "DELETE", "Type non trouvé: "+id, false)
		jsonError(w, "Type introuvable", http.StatusNotFound)
		return
	}

	createLog("système", "Suppression de type de service", "DELETE", "Type de service supprimé: "+id, true)
	w.WriteHeader(http.StatusNoContent)
}

// COMPLETED SERVICES

// GET Prestations réalisées
func handleGetCompletedServicesAdmin(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT cs.id_completed_service, cs.service_date, cs.start_time, cs.end_time, cs.senior_amount, cs.status,
		       u_senior.first_name as senior_first, u_senior.last_name as senior_last,
		       st.name as prestation_name
		FROM completed_services cs
		JOIN service_requests sr ON cs.id_request = sr.id_request
		JOIN users u_senior ON sr.id_user = u_senior.id_user
		LEFT JOIN show_type sht ON sr.id_request = sht.id_request
		LEFT JOIN service_types st ON sht.id_service_type = st.id_service_type
		ORDER BY cs.service_date DESC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type CompletedData struct {
		ID             string  `json:"id_completed_service"`
		ServiceDate    string  `json:"service_date"`
		StartTime      string  `json:"start_time"`
		EndTime        string  `json:"end_time"`
		SeniorAmount   float64 `json:"senior_amount"`
		Status         string  `json:"status"`
		SeniorFirst    string  `json:"senior_first"`
		SeniorLast     string  `json:"senior_last"`
		PrestationName string  `json:"prestation_name"`
	}

	services := []CompletedData{}
	for rows.Next() {
		var c CompletedData
		err := rows.Scan(&c.ID, &c.ServiceDate, &c.StartTime, &c.EndTime, &c.SeniorAmount, &c.Status,
			&c.SeniorFirst, &c.SeniorLast, &c.PrestationName)
		if err != nil {
			continue
		}
		services = append(services, c)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(services)
}
