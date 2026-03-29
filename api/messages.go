package main

import (
	"database/sql"
	"encoding/json"
	"io"
	"net/http"
)

// MESSAGES tous
func handleGetMessages(w http.ResponseWriter, r *http.Request) {
	userID := r.URL.Query().Get("id_user")
	receiver := r.URL.Query().Get("receiver")
	if userID == "" {
		userID = receiver
	}
	if userID == "" {
		jsonError(w, "Paramètre id_user ou receiver requis", http.StatusBadRequest)
		return
	}

	rows, err := queryMessagesByUser(userID)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des messages", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	messages := []Message{}
	for rows.Next() {
		var m Message
		if err := rows.Scan(&m.ID, &m.Content, &m.SentAt, &m.Receiver, &m.Sender); err != nil {
			continue
		}
		messages = append(messages, m)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(messages)
}

// Get Message avec userid
func handleGetUserMessages(w http.ResponseWriter, r *http.Request) {
	userID := r.PathValue("id")
	if userID == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	rows, err := queryMessagesByUser(userID)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des messages", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	messages := []Message{}
	for rows.Next() {
		var m Message
		if err := rows.Scan(&m.ID, &m.Content, &m.SentAt, &m.Receiver, &m.Sender); err != nil {
			continue
		}
		messages = append(messages, m)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(messages)
}

func queryMessagesByUser(userID string) (*sql.Rows, error) {
	return db.Query(`
		SELECT id_message, content, sent_at, receiver, sender
		FROM messages
		WHERE receiver = ? OR sender = ?
		ORDER BY sent_at DESC
	`, userID, userID)
}

// MESSAGES SEND
func handleSendMessage(w http.ResponseWriter, r *http.Request) {
	body, _ := io.ReadAll(r.Body)
	var m Message
	if err := json.Unmarshal(body, &m); err != nil {
		jsonError(w, "JSON invalide", http.StatusBadRequest)
		return
	}

	if m.Sender == "" || m.Receiver == "" || m.Content == "" {
		jsonError(w, "Champs obligatoires manquants", http.StatusUnprocessableEntity)
		return
	}

	stmt, _ := db.Prepare(`
		INSERT INTO messages (id_message, content, sent_at, receiver, sender)
		VALUES (UUID(), ?, NOW(), ?, ?)
	`)
	defer stmt.Close()

	_, err := stmt.Exec(m.Content, m.Receiver, m.Sender)
	if err != nil {
		jsonError(w, "Erreur lors de l'envoi du message", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(SuccessResponse{Message: "Message envoyé avec succès"})
}
