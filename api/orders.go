package main

import (
	"encoding/json"
	"net/http"
	"strings"
)

// Order structure
type Order struct {
	IDOrder        string  `json:"id_order"`
	OrderNumber    string  `json:"order_number"`
	Amount         float64 `json:"amount"`
	OrderDate      string  `json:"order_date"`
	DeliveryMethod string  `json:"delivery_method"`
	Status         string  `json:"status"`
	FirstName      string  `json:"first_name"`
	LastName       string  `json:"last_name"`
	Items          *string `json:"items"`
}

// OrderStats structure
type OrderStats struct {
	TotalCommandes int `json:"total_commandes"`
	EnAttente      int `json:"en_attente"`
	Livrees        int `json:"livrees"`
	Retours        int `json:"retours"`
}

// Get orders tout
func handleGetAllOrders(w http.ResponseWriter, r *http.Request) {
	query := `
		SELECT o.id_order, o.order_number, o.amount, o.order_date, o.delivery_method, o.status,
		       u.first_name, u.last_name,
		       GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ' + ') as items
		FROM orders o
		JOIN users u ON o.id_user = u.id_user
		LEFT JOIN order_items oi ON o.id_order = oi.id_order
		LEFT JOIN products p ON oi.id_product = p.id_product
		GROUP BY o.id_order
		ORDER BY o.order_date DESC
	`

	rows, err := db.Query(query)
	if err != nil {
		jsonError(w, "Failed to fetch orders", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	orders := []*Order{}
	for rows.Next() {
		var ord Order
		if err := rows.Scan(&ord.IDOrder, &ord.OrderNumber, &ord.Amount, &ord.OrderDate, &ord.DeliveryMethod,
			&ord.Status, &ord.FirstName, &ord.LastName, &ord.Items); err != nil {
			continue
		}
		orders = append(orders, &ord)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(orders)
}

// Get order stats
func handleGetOrderStats(w http.ResponseWriter, r *http.Request) {
	stats := OrderStats{}

	db.QueryRow("SELECT COUNT(*) FROM orders").Scan(&stats.TotalCommandes)
	db.QueryRow("SELECT COUNT(*) FROM orders WHERE status = 'En attente'").Scan(&stats.EnAttente)
	db.QueryRow("SELECT COUNT(*) FROM orders WHERE status = 'Livrée'").Scan(&stats.Livrees)
	db.QueryRow("SELECT COUNT(*) FROM orders WHERE status = 'Retour demandé'").Scan(&stats.Retours)

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(stats)
}

// order change
func handleUpdateOrderStatus(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPatch {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	id := strings.TrimPrefix(r.URL.Path, "/api/orders/")

	var req struct {
		Status string `json:"status"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonError(w, "Invalid request", http.StatusBadRequest)
		return
	}

	if req.Status == "" {
		jsonError(w, "Status is required", http.StatusBadRequest)
		return
	}

	stmt, err := db.Prepare("UPDATE orders SET status=? WHERE id_order=?")
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	if _, err := stmt.Exec(req.Status, id); err != nil {
		jsonError(w, "Failed to update order", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"Message": "Order updated successfully"})
}
