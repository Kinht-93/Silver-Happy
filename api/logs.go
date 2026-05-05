package main

import (
	"database/sql"
	"encoding/json"
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

func createLog(utilisateur, action, logType, details string, statut bool) error {
	_, err := db.Exec(`
		INSERT INTO logs (utilisateur, action, type, details, statut, created_at)
		VALUES (?, ?, ?, ?, ?, NOW())
	`, utilisateur, action, logType, details, statut)
	return err
}

func handleGetLogs(w http.ResponseWriter, r *http.Request) {
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

	query := `
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

	if len(conditions) > 0 {
		query += " WHERE " + strings.Join(conditions, " AND ")
	}

	query += " ORDER BY " + sort + " " + order + " LIMIT 1000"

	rows, err := db.Query(query, args...)
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

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(logs)
}
