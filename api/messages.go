package main

import (
	"encoding/json"
	"io"
	"net/http"
)

// MESSAGES tous
func handleGetMessages(w http.ResponseWriter, r *http.Request) {
	receiver := r.URL.Query().Get("receiver")
	if receiver == "" {
		jsonError(w, "Paramètre receiver requis", http.StatusBadRequest)
		return
	}

	rows, err := db.Query(`
		SELECT id_message, content, sent_at, receiver, sender FROM messages WHERE receiver = ? ORDER BY sent_at DESC
	`, receiver)
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
