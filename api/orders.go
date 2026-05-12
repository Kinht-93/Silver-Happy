package main

import (
	"encoding/json"
	"fmt"
	"net/http"
	"os"
	"strconv"
	"strings"
	"time"

	"github.com/google/uuid"
	"github.com/stripe/stripe-go/v76"
	"github.com/stripe/stripe-go/v76/checkout/session"
	"github.com/stripe/stripe-go/v76/refund"
)

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

// Get order user
func handleGetUserOrders(w http.ResponseWriter, r *http.Request) {
	userID := r.PathValue("id")
	if userID == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	query := `
		SELECT o.id_order, o.order_number, o.amount, o.order_date, o.delivery_method, o.status,
		       u.first_name, u.last_name,
		       GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ' + ') as items
		FROM orders o
		JOIN users u ON o.id_user = u.id_user
		LEFT JOIN order_items oi ON o.id_order = oi.id_order
		LEFT JOIN products p ON oi.id_product = p.id_product
		WHERE o.id_user = ?
		GROUP BY o.id_order
		ORDER BY o.order_date DESC
	`

	rows, err := db.Query(query, userID)
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

func handleCreateOrderCheckout(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var body struct {
		IDUser string `json:"id_user"`
		Items  []struct {
			IDProduct string `json:"id_product"`
			Quantity  int    `json:"quantity"`
		} `json:"items"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil || body.IDUser == "" || len(body.Items) == 0 {
		jsonError(w, "JSON invalide ou champs manquants", http.StatusBadRequest)
		return
	}

	lineItems := []*stripe.CheckoutSessionLineItemParams{}
	itemsMeta := []string{}
	for _, item := range body.Items {
		if item.IDProduct == "" || item.Quantity <= 0 {
			continue
		}

		var productName string
		var price float64
		var stock int
		err := db.QueryRow("SELECT name, price, stock FROM products WHERE id_product = ?", item.IDProduct).Scan(&productName, &price, &stock)
		if err != nil {
			jsonError(w, "Produit introuvable : "+item.IDProduct, http.StatusBadRequest)
			return
		}
		if stock < item.Quantity {
			jsonError(w, "Stock insuffisant pour le produit : "+productName, http.StatusBadRequest)
			return
		}

		lineItems = append(lineItems, &stripe.CheckoutSessionLineItemParams{
			PriceData: &stripe.CheckoutSessionLineItemPriceDataParams{
				Currency: stripe.String("eur"),
				ProductData: &stripe.CheckoutSessionLineItemPriceDataProductDataParams{
					Name: stripe.String(productName),
				},
				UnitAmount: stripe.Int64(int64(price * 100)),
			},
			Quantity: stripe.Int64(int64(item.Quantity)),
		})
		itemsMeta = append(itemsMeta, fmt.Sprintf("%s:%d", item.IDProduct, item.Quantity))
	}

	if len(lineItems) == 0 {
		jsonError(w, "Aucun article valide pour le paiement", http.StatusBadRequest)
		return
	}

	orderID := "ord_" + uuid.New().String()
	metadata := map[string]string{
		"id_user":  body.IDUser,
		"order_id": orderID,
		"items":    strings.Join(itemsMeta, ";"),
	}

	baseURL := os.Getenv("APP_BASE_URL")
	if baseURL == "" {
		baseURL = "http://localhost/Silver-Happy"
	}

	params := &stripe.CheckoutSessionParams{
		Mode:       stripe.String(string(stripe.CheckoutSessionModePayment)),
		LineItems:  lineItems,
		Metadata:   metadata,
		SuccessURL: stripe.String(baseURL + "/senior/boutique.php?payment=success&session_id={CHECKOUT_SESSION_ID}"),
		CancelURL:  stripe.String(baseURL + "/senior/boutique.php?payment=cancelled"),
	}

	s, err := session.New(params)
	if err != nil {
		jsonError(w, "Erreur Stripe : "+err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"checkout_url": s.URL})
}

func handleConfirmOrderCheckout(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	sessionID := r.URL.Query().Get("session_id")
	if sessionID == "" {
		jsonError(w, "session_id manquant", http.StatusBadRequest)
		return
	}

	s, err := session.Get(sessionID, nil)
	if err != nil {
		jsonError(w, "Session Stripe introuvable : "+err.Error(), http.StatusBadRequest)
		return
	}

	if s.PaymentStatus != stripe.CheckoutSessionPaymentStatusPaid {
		jsonError(w, "Paiement Stripe non confirmé", http.StatusConflict)
		return
	}

	paymentIntentID := ""
	if s.PaymentIntent != nil {
		paymentIntentID = s.PaymentIntent.ID
	}

	orderID := s.Metadata["order_id"]
	userID := s.Metadata["id_user"]
	itemsMeta := s.Metadata["items"]
	if orderID == "" || userID == "" || itemsMeta == "" || paymentIntentID == "" {
		jsonError(w, "Metadata Stripe manquante", http.StatusBadRequest)
		return
	}

	var existing int
	err = db.QueryRow("SELECT COUNT(*) FROM orders WHERE id_order = ?", orderID).Scan(&existing)
	if err == nil && existing > 0 {
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]string{"message": "Commande déjà enregistrée"})
		return
	}

	parts := strings.Split(itemsMeta, ";")
	if len(parts) == 0 {
		jsonError(w, "Aucune information d'article valide", http.StatusBadRequest)
		return
	}

	tx, err := db.Begin()
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer tx.Rollback()

	totalAmount := 0.0
	type orderItemData struct {
		ProductID string
		Quantity  int
		UnitPrice float64
	}
	items := []orderItemData{}

	for _, part := range parts {
		if part == "" {
			continue
		}
		itemParts := strings.Split(part, ":")
		if len(itemParts) != 2 {
			continue
		}
		qty, err := strconv.Atoi(itemParts[1])
		if err != nil || qty <= 0 {
			continue
		}
		var price float64
		var stock int
		err = tx.QueryRow("SELECT price, stock FROM products WHERE id_product = ?", itemParts[0]).Scan(&price, &stock)
		if err != nil {
			jsonError(w, "Produit introuvable : "+itemParts[0], http.StatusBadRequest)
			return
		}
		if stock < qty {
			jsonError(w, "Stock insuffisant pour le produit : "+itemParts[0], http.StatusBadRequest)
			return
		}
		items = append(items, orderItemData{ProductID: itemParts[0], Quantity: qty, UnitPrice: price})
		totalAmount += price * float64(qty)
	}

	if len(items) == 0 {
		jsonError(w, "Aucun article valide à enregistrer", http.StatusBadRequest)
		return
	}

	orderNumber := fmt.Sprintf("ORD-%s-%s", time.Now().Format("20060102-150405"), strings.ToUpper(uuid.New().String()[:8]))
	stmt, err := tx.Prepare("INSERT INTO orders (id_order, order_number, id_user, amount, delivery_method, status, order_date, stripe_payment_intent_id) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)")
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	if _, err := stmt.Exec(orderID, orderNumber, userID, totalAmount, "pickup", "En attente", paymentIntentID); err != nil {
		jsonError(w, "Failed to create order", http.StatusInternalServerError)
		return
	}

	for _, item := range items {
		stmt, err := tx.Prepare("INSERT INTO order_items (id_order, id_product, quantity, unit_price) VALUES (?, ?, ?, ?)")
		if err != nil {
			jsonError(w, "Database error", http.StatusInternalServerError)
			return
		}
		if _, err := stmt.Exec(orderID, item.ProductID, item.Quantity, item.UnitPrice); err != nil {
			jsonError(w, "Failed to add order items", http.StatusInternalServerError)
			return
		}
		stmt.Close()

		stmt, err = tx.Prepare("UPDATE products SET stock = stock - ? WHERE id_product = ?")
		if err != nil {
			jsonError(w, "Database error", http.StatusInternalServerError)
			return
		}
		if _, err := stmt.Exec(item.Quantity, item.ProductID); err != nil {
			jsonError(w, "Failed to update stock", http.StatusInternalServerError)
			return
		}
		stmt.Close()
	}

	if err := tx.Commit(); err != nil {
		jsonError(w, "Failed to commit transaction", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "Commande enregistrée avec succès", "order_id": orderID})
}

func handleRefundOrder(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	orderID := r.PathValue("id")
	if orderID == "" {
		jsonError(w, "ID de commande manquant", http.StatusBadRequest)
		return
	}

	var orderStripeID, orderUserID, orderStatus string
	err := db.QueryRow("SELECT stripe_payment_intent_id, id_user, status FROM orders WHERE id_order = ?", orderID).Scan(&orderStripeID, &orderUserID, &orderStatus)
	if err != nil {
		jsonError(w, "Commande introuvable", http.StatusNotFound)
		return
	}

	if orderStripeID == "" {
		jsonError(w, "Impossible de rembourser cette commande", http.StatusBadRequest)
		return
	}

	if strings.ToLower(orderStatus) != "en attente" {
		jsonError(w, "Seules les commandes en attente peuvent être annulées", http.StatusConflict)
		return
	}

	refundParams := &stripe.RefundParams{
		PaymentIntent: stripe.String(orderStripeID),
	}
	if _, err := refund.New(refundParams); err != nil {
		jsonError(w, "Erreur de remboursement Stripe : "+err.Error(), http.StatusInternalServerError)
		return
	}

	tx, err := db.Begin()
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer tx.Rollback()

	rows, err := tx.Query("SELECT id_product, quantity FROM order_items WHERE id_order = ?", orderID)
	if err != nil {
		jsonError(w, "Erreur lors de la lecture des articles de commande", http.StatusInternalServerError)
		return
	}

	items := []struct {
		productID string
		quantity  int
	}{}
	for rows.Next() {
		var productID string
		var quantity int
		if err := rows.Scan(&productID, &quantity); err != nil {
			rows.Close()
			jsonError(w, "Erreur interne", http.StatusInternalServerError)
			return
		}
		items = append(items, struct {
			productID string
			quantity  int
		}{productID: productID, quantity: quantity})
	}

	if err := rows.Close(); err != nil {
		jsonError(w, "Erreur interne", http.StatusInternalServerError)
		return
	}
	if err := rows.Err(); err != nil {
		jsonError(w, "Erreur interne", http.StatusInternalServerError)
		return
	}

	stmt, err := tx.Prepare("UPDATE products SET stock = stock + ? WHERE id_product = ?")
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	for _, item := range items {
		if _, err := stmt.Exec(item.quantity, item.productID); err != nil {
			jsonError(w, "Erreur lors de la mise à jour du stock", http.StatusInternalServerError)
			return
		}
	}

	if _, err := tx.Exec("DELETE FROM order_items WHERE id_order = ?", orderID); err != nil {
		jsonError(w, "Erreur lors de la suppression des éléments de commande", http.StatusInternalServerError)
		return
	}

	if _, err := tx.Exec("DELETE FROM orders WHERE id_order = ?", orderID); err != nil {
		jsonError(w, "Erreur lors de la suppression de la commande", http.StatusInternalServerError)
		return
	}

	if err := tx.Commit(); err != nil {
		jsonError(w, "Erreur lors de la validation de la transaction", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "Commande annulée et remboursée avec succès"})
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
		createLog("système", "Mise à jour de commande", "UPDATE", "Erreur lors de la mise à jour du statut de la commande "+id+": "+err.Error(), false)
		jsonError(w, "Failed to update order", http.StatusInternalServerError)
		return
	}

	createLog("système", "Mise à jour de commande", "UPDATE", "Statut de la commande "+id+" changé en: "+req.Status, true)
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"Message": "Order updated successfully"})
}

// order +
func handleCreateOrder(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		jsonError(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req struct {
		IDUser         string                   `json:"id_user"`
		Amount         float64                  `json:"amount"`
		DeliveryMethod string                   `json:"delivery_method"`
		Status         string                   `json:"status"`
		Items          []map[string]interface{} `json:"items"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonError(w, "Invalid request", http.StatusBadRequest)
		return
	}

	if req.IDUser == "" || req.Amount <= 0 || len(req.Items) == 0 {
		jsonError(w, "Missing or invalid required fields", http.StatusBadRequest)
		return
	}

	tx, err := db.Begin()
	if err != nil {
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}

	orderID := "ord_" + uuid.New().String()

	stmt, err := tx.Prepare(`
		INSERT INTO orders (id_order, order_number, id_user, amount, delivery_method, status, order_date)
		VALUES (?, CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(LAST_INSERT_ID() + 1, 4, '0')), ?, ?, ?, ?, NOW())
	`)
	if err != nil {
		tx.Rollback()
		jsonError(w, "Database error", http.StatusInternalServerError)
		return
	}
	defer stmt.Close()

	_, err = stmt.Exec(orderID, req.IDUser, req.Amount, req.DeliveryMethod, req.Status)
	if err != nil {
		tx.Rollback()
		createLog("système", "Création de commande", "CREATE", "Erreur lors de la création de la commande: "+err.Error(), false)
		jsonError(w, "Failed to create order", http.StatusInternalServerError)
		return
	}

	for _, item := range req.Items {
		productID := item["id_product"].(string)
		quantity := int(item["quantity"].(float64))

		stmt, err := tx.Prepare(`
			INSERT INTO order_items (id_order, id_product, quantity)
			VALUES (?, ?, ?)
		`)
		if err != nil {
			tx.Rollback()
			jsonError(w, "Database error", http.StatusInternalServerError)
			return
		}
		defer stmt.Close()

		if _, err := stmt.Exec(orderID, productID, quantity); err != nil {
			tx.Rollback()
			jsonError(w, "Failed to add order items", http.StatusInternalServerError)
			return
		}

		stmt, err = tx.Prepare("UPDATE products SET stock = stock - ? WHERE id_product = ?")
		if err != nil {
			tx.Rollback()
			jsonError(w, "Database error", http.StatusInternalServerError)
			return
		}
		defer stmt.Close()

		if _, err := stmt.Exec(quantity, productID); err != nil {
			tx.Rollback()
			jsonError(w, "Failed to update stock", http.StatusInternalServerError)
			return
		}
	}

	if err := tx.Commit(); err != nil {
		jsonError(w, "Failed to commit transaction", http.StatusInternalServerError)
		return
	}

	createLog("système", "Création de commande", "CREATE", "Commande créée: "+orderID+" - Montant: "+fmt.Sprintf("%.2f", req.Amount)+"€", true)
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"Message":  "Order created successfully",
		"order_id": orderID,
	})
}
