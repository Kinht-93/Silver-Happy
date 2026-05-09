package main

import (
	"database/sql"
	"encoding/json"
	"net/http"
)

// notification tout

func handleGetNotifications(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query(`
		SELECT n.id_notification, n.type, n.title, n.message, n.created_at, n.is_read,
			   u.first_name, u.last_name
		FROM notifications n
		LEFT JOIN users u ON n.id_user = u.id_user
		ORDER BY n.created_at DESC
	`)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des notifications", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type Notification struct {
		ID        string  `json:"id_notification"`
		Type      string  `json:"type"`
		Title     string  `json:"title"`
		Message   string  `json:"message"`
		CreatedAt string  `json:"created_at"`
		IsRead    bool    `json:"is_read"`
		FirstName *string `json:"first_name,omitempty"`
		LastName  *string `json:"last_name,omitempty"`
	}
	notifications := []Notification{}
	for rows.Next() {
		var n Notification
		var firstName, lastName sql.NullString
		var isReadInt int
		err := rows.Scan(&n.ID, &n.Type, &n.Title, &n.Message, &n.CreatedAt, &isReadInt, &firstName, &lastName)
		if err != nil {
			continue
		}
		n.IsRead = isReadInt != 0
		if firstName.Valid {
			n.FirstName = &firstName.String
		}
		if lastName.Valid {
			n.LastName = &lastName.String
		}
		notifications = append(notifications, n)
	}
	if err := rows.Err(); err != nil {
		jsonError(w, "Erreur lors de la lecture des notifications", http.StatusInternalServerError)
		return
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(notifications)
}

// PROBLEME COUNT
func handleGetProblemeCount(w http.ResponseWriter, r *http.Request) {
	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM notification").Scan(&count)
	if err != nil {
		jsonError(w, "Erreur lors du comptage des utilisateurs", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"count": count,
	})
}
