package main

import (
	"encoding/json"
	"net/http"
	"strings"
)

type Product struct {
	IDProduct string  `json:"id_product"`
	Name      string  `json:"name"`
	Category  string  `json:"category"`
	Price     float64 `json:"price"`
	Stock     int     `json:"stock"`
	Sales     int     `json:"sales"`
	Status    string  `json:"status"`
}

type ProductCategory struct {
	Name     string `json:"name"`
	Articles int    `json:"articles"`
}

// produit tous
func handleGetAllProducts(w http.ResponseWriter, r *http.Request) {
	query := `
		SELECT id_product, name, category, price, stock, sales, status 
		FROM products 
		ORDER BY name ASC
	`

	rows, err := db.Query(query)
	if err != nil {
		jsonError(w, "Failed to fetch products", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	products := []*Product{}
	for rows.Next() {
		var p Product
		if err := rows.Scan(&p.IDProduct, &p.Name, &p.Category, &p.Price, &p.Stock, &p.Sales, &p.Status); err != nil {
			continue
		}
		products = append(products, &p)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(products)
}

// produit catégorie count
func handleGetProductCategories(w http.ResponseWriter, r *http.Request) {
	query := `
		SELECT category as name, COUNT(*) as articles 
		FROM products 
		WHERE category IS NOT NULL AND category != ''
		GROUP BY category
		ORDER BY category
	`

	rows, err := db.Query(query)
	if err != nil {
		jsonError(w, "Failed to fetch categories", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	categories := []*ProductCategory{}
	for rows.Next() {
		var pc ProductCategory
		if err := rows.Scan(&pc.Name, &pc.Articles); err != nil {
			continue
		}
		categories = append(categories, &pc)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(categories)
}

// Produit +
func handleCreateProduct(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req struct {
		Name     string  `json:"name"`
		Category string  `json:"category"`
		Price    float64 `json:"price"`
		Stock    int     `json:"stock"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonError(w, "Invalid request", http.StatusBadRequest)
		return
	}

	if req.Name == "" || req.Category == "" || req.Price <= 0 || req.Stock < 0 {
		jsonError(w, "Missing or invalid required fields", http.StatusBadRequest)
		return
	}

	stmt, err := db.Prepare(`
		INSERT INTO products (id_product, name, category, price, stock, sales, status)
		VALUES (CONCAT('prd_', UUID()), ?, ?, ?, ?, 0, 'En stock')
	`)
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	if _, err := stmt.Exec(req.Name, req.Category, req.Price, req.Stock); err != nil {
		jsonError(w, "Failed to create product", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]string{"Message": "Product created successfully"})
}

// produit change
func handleUpdateProduct(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPatch {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	id := strings.TrimPrefix(r.URL.Path, "/api/products/")

	var req struct {
		Name  string  `json:"name"`
		Price float64 `json:"price"`
		Stock int     `json:"stock"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonError(w, "Invalid request", http.StatusBadRequest)
		return
	}

	stmt, err := db.Prepare(`
		UPDATE products SET name=?, price=?, stock=? WHERE id_product=?
	`)
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	if _, err := stmt.Exec(req.Name, req.Price, req.Stock, id); err != nil {
		jsonError(w, "Failed to update product", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"Message": "Product updated successfully"})
}

// produit -
func handleDeleteProduct(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodDelete {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	id := strings.TrimPrefix(r.URL.Path, "/api/products/")

	stmt, err := db.Prepare("DELETE FROM products WHERE id_product=?")
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	result, err := stmt.Exec(id)
	if err != nil {
		jsonError(w, "Failed to delete product", http.StatusInternalServerError)
		return
	}

	rowsAffected, err := result.RowsAffected()
	if err != nil || rowsAffected == 0 {
		jsonError(w, "Product not found", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"Message": "Product deleted successfully"})
}
