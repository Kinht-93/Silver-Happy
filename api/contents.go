package main

import (
	"database/sql"
	"encoding/json"
	"net/http"
	"strings"
	"time"
)

// Content structure
type Content struct {
	IDContent   string    `json:"id_content"`
	Title       string    `json:"title"`
	Category    string    `json:"category"`
	ContentBody string    `json:"content_body"`
	Status      string    `json:"status"`
	Views       int       `json:"views"`
	CreatedAt   time.Time `json:"created_at"`
	AuthorID    *string   `json:"author_id"`
	FirstName   *string   `json:"first_name"`
	LastName    *string   `json:"last_name"`
}

// scanContent intermediate struct for NULL handling
type scanContent struct {
	IDContent   string
	Title       string
	Category    string
	ContentBody string
	Status      string
	Views       int
	CreatedAt   time.Time
	AuthorID    sql.NullString
	FirstName   sql.NullString
	LastName    sql.NullString
}

func (sc *scanContent) toContent() *Content {
	c := &Content{
		IDContent:   sc.IDContent,
		Title:       sc.Title,
		Category:    sc.Category,
		ContentBody: sc.ContentBody,
		Status:      sc.Status,
		Views:       sc.Views,
		CreatedAt:   sc.CreatedAt,
	}

	if sc.AuthorID.Valid {
		c.AuthorID = &sc.AuthorID.String
	}
	if sc.FirstName.Valid {
		c.FirstName = &sc.FirstName.String
	}
	if sc.LastName.Valid {
		c.LastName = &sc.LastName.String
	}

	return c
}

// handleGetAllContents returns all contents with author info
func handleGetAllContents(w http.ResponseWriter, r *http.Request) {
	query := `
		SELECT c.id_content, c.title, c.category, c.content_body, c.status, c.views, c.created_at,
		       c.author_id, u.first_name, u.last_name
		FROM contents c
		LEFT JOIN users u ON c.author_id = u.id_user
		ORDER BY c.created_at DESC
	`

	rows, err := db.Query(query)
	if err != nil {
		jsonError(w, "Failed to fetch contents", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	contents := []*Content{}
	for rows.Next() {
		var sc scanContent
		if err := rows.Scan(&sc.IDContent, &sc.Title, &sc.Category, &sc.ContentBody, &sc.Status,
			&sc.Views, &sc.CreatedAt, &sc.AuthorID, &sc.FirstName, &sc.LastName); err != nil {
			continue
		}
		contents = append(contents, sc.toContent())
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(contents)
}

// handleCreateContent creates a new content
func handleCreateContent(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req struct {
		Title       string `json:"title"`
		Category    string `json:"category"`
		ContentBody string `json:"content_body"`
		Status      string `json:"status"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonError(w, "Invalid request", http.StatusBadRequest)
		return
	}

	if req.Title == "" || req.Category == "" || req.ContentBody == "" {
		jsonError(w, "Missing required fields", http.StatusBadRequest)
		return
	}

	stmt, err := db.Prepare(`
		INSERT INTO contents (id_content, title, category, content_body, status, created_at, views)
		VALUES (CONCAT('cnt_', UUID()), ?, ?, ?, ?, NOW(), 0)
	`)
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	status := req.Status
	if status == "" {
		status = "Brouillon"
	}

	if _, err := stmt.Exec(req.Title, req.Category, req.ContentBody, status); err != nil {
		jsonError(w, "Failed to create content", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]string{"Message": "Content created successfully"})
}

// handleUpdateContent updates a content
func handleUpdateContent(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPatch {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	// Extract ID from path
	id := strings.TrimPrefix(r.URL.Path, "/api/contents/")

	var req struct {
		Title       string `json:"title"`
		Category    string `json:"category"`
		ContentBody string `json:"content_body"`
		Status      string `json:"status"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonError(w, "Invalid request", http.StatusBadRequest)
		return
	}

	stmt, err := db.Prepare(`
		UPDATE contents SET title=?, category=?, content_body=?, status=? WHERE id_content=?
	`)
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	if _, err := stmt.Exec(req.Title, req.Category, req.ContentBody, req.Status, id); err != nil {
		jsonError(w, "Failed to update content", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"Message": "Content updated successfully"})
}

// handleDeleteContent deletes a content
func handleDeleteContent(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodDelete {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	id := strings.TrimPrefix(r.URL.Path, "/api/contents/")

	stmt, err := db.Prepare("DELETE FROM contents WHERE id_content=?")
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	result, err := stmt.Exec(id)
	if err != nil {
		jsonError(w, "Failed to delete content", http.StatusInternalServerError)
		return
	}

	rowsAffected, err := result.RowsAffected()
	if err != nil || rowsAffected == 0 {
		jsonError(w, "Content not found", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"Message": "Content deleted successfully"})
}
