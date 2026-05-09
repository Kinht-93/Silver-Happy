package main

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"strings"
)

type Log struct {
	ID          int     `json:"id"`
	CreatedAt   string  `json:"created_at"`
	Utilisateur *string `json:"utilisateur,omitempty"`
	Action      string  `json:"action"`
	Type        string  `json:"type"`
	Details     *string `json:"details,omitempty"`
	Statut      bool    `json:"statut"`
}

type LogsResponse struct {
	Logs       []Log `json:"logs"`
	Total      int   `json:"total"`
	Page       int   `json:"page"`
	PerPage    int   `json:"per_page"`
	TotalPages int   `json:"total_pages"`
}

func createLog(utilisateur, action, logType, details string, statut bool) error {
	_, err := db.Exec(`
		INSERT INTO logs (utilisateur, action, type, details, statut, created_at)
		VALUES (?, ?, ?, ?, ?, NOW())
	`, utilisateur, action, logType, details, statut)
	return err
}

func handleGetLogs(w http.ResponseWriter, r *http.Request) {
	const perPage = 50

	page := 1
	if p := r.URL.Query().Get("page"); p != "" {
		if pInt, err := atoi(p); err == nil && pInt > 0 {
			page = pInt
		}
	}

	logType := r.URL.Query().Get("type")
	search := r.URL.Query().Get("search")
	sort := r.URL.Query().Get("sort")
	order := r.URL.Query().Get("order")

	validSortColumns := map[string]bool{
		"created_at":  true,
		"utilisateur": true,
		"action":      true,
		"type":        true,
		"statut":      true,
	}

	if sort == "" {
		sort = "created_at"
	}
	if !validSortColumns[sort] {
		sort = "created_at"
	}

	if order != "asc" && order != "desc" {
		order = "desc"
	}

	baseQuery := `
		SELECT id, created_at, utilisateur, action, type, details, statut
		FROM logs
	`

	args := []interface{}{}
	conditions := []string{}

	if logType != "" {
		if logType == "UPDATE" {
			conditions = append(conditions, "type IN ('CREATE', 'UPDATE', 'DELETE')")
		} else {
			conditions = append(conditions, "type = ?")
			args = append(args, logType)
		}
	}

	if search != "" {
		conditions = append(conditions, "(utilisateur LIKE ? OR action LIKE ? OR type LIKE ? OR details LIKE ?)")
		searchPattern := "%" + search + "%"
		args = append(args, searchPattern, searchPattern, searchPattern, searchPattern)
	}

	whereClause := ""
	if len(conditions) > 0 {
		whereClause = " WHERE " + strings.Join(conditions, " AND ")
	}

	// Count total logs
	countQuery := "SELECT COUNT(*) FROM logs" + whereClause
	var total int
	err := db.QueryRow(countQuery, args...).Scan(&total)
	if err != nil {
		jsonError(w, "Erreur lors du comptage des logs", http.StatusInternalServerError)
		return
	}

	totalPages := (total + perPage - 1) / perPage
	if page > totalPages && totalPages > 0 {
		page = totalPages
	}

	offset := (page - 1) * perPage
	query := baseQuery + whereClause + " ORDER BY " + sort + " " + order + " LIMIT ? OFFSET ?"

	queryArgs := append(args, perPage, offset)
	rows, err := db.Query(query, queryArgs...)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des logs", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	logs := []Log{}
	for rows.Next() {
		var log Log
		var utilisateur, details sql.NullString

		err := rows.Scan(&log.ID, &log.CreatedAt, &utilisateur, &log.Action,
			&log.Type, &details, &log.Statut)
		if err != nil {
			continue
		}

		if utilisateur.Valid {
			log.Utilisateur = &utilisateur.String
		}
		if details.Valid {
			log.Details = &details.String
		}

		logs = append(logs, log)
	}

	response := LogsResponse{
		Logs:       logs,
		Total:      total,
		Page:       page,
		PerPage:    perPage,
		TotalPages: totalPages,
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(response)
}

func atoi(s string) (int, error) {
	var n int
	_, err := fmt.Sscanf(s, "%d", &n)
	return n, err
}
