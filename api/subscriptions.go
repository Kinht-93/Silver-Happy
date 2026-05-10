package main

import (
	"encoding/json"
	"net/http"
	"os"
	"time"

	"github.com/stripe/stripe-go/v76"
	"github.com/stripe/stripe-go/v76/checkout/session"
)

// GET /api/subscription-types
func handleGetSubscriptionTypes(w http.ResponseWriter, r *http.Request) {
	userType := r.URL.Query().Get("user_type")
	if userType == "" {
		userType = "senior"
	}

	rows, err := db.Query(`
		SELECT id_subscription_type, name, user_type, monthly_price, yearly_price, description
		FROM subscription_types
		WHERE LOWER(user_type) = LOWER(?)
		ORDER BY monthly_price ASC, name ASC
	`, userType)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des formules", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var types []SubscriptionType
	for rows.Next() {
		var t SubscriptionType
		var desc *string
		err := rows.Scan(&t.ID, &t.Name, &t.UserType, &t.MonthlyPrice, &t.YearlyPrice, &desc)
		if err != nil {
			continue
		}
		t.Description = desc
		types = append(types, t)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(types)
}

// GET /api/users/{id}/subscriptions
func handleGetUserSubscriptions(w http.ResponseWriter, r *http.Request) {
	userID := r.PathValue("id")
	if userID == "" {
		jsonError(w, "ID utilisateur manquant", http.StatusBadRequest)
		return
	}

	rows, err := db.Query(`
		SELECT s.id_subscription_type AS id_subscription, s.id_subscription_type, st.name, s.status, s.period, s.subscribed_at, s.cancelled_at
		FROM subscribed s
		INNER JOIN subscription_types st ON st.id_subscription_type = s.id_subscription_type
		WHERE s.id_user = ?
		ORDER BY s.subscribed_at DESC
	`, userID)
	if err != nil {
		jsonError(w, "Erreur lors de la récupération des abonnements", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var subs []UserSubscription
	for rows.Next() {
		var s UserSubscription
		var cancelledAt *string
		err := rows.Scan(&s.ID, &s.SubscriptionTypeID, &s.Name, &s.Status, &s.Period, &s.SubscribedAt, &cancelledAt)
		if err != nil {
			continue
		}
		s.CancelledAt = cancelledAt
		s.SubscriptionStart = s.SubscribedAt

		if s.SubscribedAt != "" {
			var parsedTime time.Time
			formats := []string{
				"2006-01-02 15:04:05",
				"2006-01-02T15:04:05Z",
				"2006-01-02T15:04:05",
			}

			for _, format := range formats {
				if t, err := time.Parse(format, s.SubscribedAt); err == nil {
					parsedTime = t
					break
				}
			}

			if !parsedTime.IsZero() {
				if s.Period == "yearly" {
					s.SubscriptionEnd = parsedTime.AddDate(1, 0, 0).Format("2006-01-02")
				} else {
					s.SubscriptionEnd = parsedTime.AddDate(0, 1, 0).Format("2006-01-02")
				}
			}
		}
		subs = append(subs, s)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(subs)
}

// DELETE /api/users/{id}/subscriptions/{subId}
func handleDeleteUserSubscription(w http.ResponseWriter, r *http.Request) {
	userID := r.PathValue("id")
	subID := r.PathValue("subId")
	if userID == "" || subID == "" {
		jsonError(w, "Paramètres manquants", http.StatusBadRequest)
		return
	}

	var count int
	err := db.QueryRow("SELECT COUNT(*) FROM subscribed WHERE id_user = ? AND id_subscription_type = ?", userID, subID).Scan(&count)
	if err != nil || count == 0 {
		jsonError(w, "Abonnement introuvable", http.StatusNotFound)
		return
	}

	_, err = db.Exec("DELETE FROM subscribed WHERE id_user = ? AND id_subscription_type = ?", userID, subID)
	if err != nil {
		jsonError(w, "Erreur lors de la suppression", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "Abonnement supprimé"})
}

// POST /api/subscriptions/checkout
func handleCreateSubscriptionCheckout(w http.ResponseWriter, r *http.Request) {
	var body struct {
		UserID             string `json:"id_user"`
		SubscriptionTypeID string `json:"id_subscription_type"`
		Period             string `json:"period"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil || body.UserID == "" || body.SubscriptionTypeID == "" {
		jsonError(w, "JSON invalide ou champs manquants", http.StatusBadRequest)
		return
	}
	if body.Period != "monthly" && body.Period != "yearly" {
		body.Period = "monthly"
	}

	var planName string
	var monthlyPrice, yearlyPrice float64
	err := db.QueryRow(
		"SELECT name, monthly_price, yearly_price FROM subscription_types WHERE id_subscription_type = ? AND LOWER(user_type) = 'senior'",
		body.SubscriptionTypeID,
	).Scan(&planName, &monthlyPrice, &yearlyPrice)
	if err != nil {
		jsonError(w, "Formule introuvable", http.StatusNotFound)
		return
	}

	price := monthlyPrice
	label := "mensuel"
	if body.Period == "yearly" {
		price = yearlyPrice
		label = "annuel"
	}

	baseURL := os.Getenv("APP_BASE_URL")
	if baseURL == "" {
		baseURL = "http://localhost/thib/Silver-Happy"
	}

	params := &stripe.CheckoutSessionParams{
		Mode: stripe.String(string(stripe.CheckoutSessionModePayment)),
		LineItems: []*stripe.CheckoutSessionLineItemParams{
			{
				PriceData: &stripe.CheckoutSessionLineItemPriceDataParams{
					Currency: stripe.String("eur"),
					ProductData: &stripe.CheckoutSessionLineItemPriceDataProductDataParams{
						Name: stripe.String("Abonnement " + planName + " (" + label + ")"),
					},
					UnitAmount: stripe.Int64(int64(price * 100)), // Stripe veut des centimes
				},
				Quantity: stripe.Int64(1),
			},
		},
		Metadata: map[string]string{
			"id_user":         body.UserID,
			"id_subscription": body.SubscriptionTypeID,
			"period":          body.Period,
		},
		SuccessURL: stripe.String(baseURL + "/senior/abonnements.php?payment=success&session_id={CHECKOUT_SESSION_ID}"),
		CancelURL:  stripe.String(baseURL + "/senior/abonnements.php?payment=cancelled"),
	}

	s, err := session.New(params)
	if err != nil {
		jsonError(w, "Erreur Stripe : "+err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"checkout_url": s.URL})
}

func handleConfirmSubscriptionCheckout(w http.ResponseWriter, r *http.Request) {
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
		jsonError(w, "Paiement non confirmé", http.StatusConflict)
		return
	}

	idUser := s.Metadata["id_user"]
	idSub := s.Metadata["id_subscription"]
	if idUser == "" || idSub == "" {
		jsonError(w, "Metadata Stripe manquante", http.StatusBadRequest)
		return
	}

	var existing int
	db.QueryRow("SELECT COUNT(*) FROM subscribed WHERE id_user = ? AND id_subscription_type = ?", idUser, idSub).Scan(&existing)
	if existing > 0 {
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]string{"message": "Abonnement déjà actif"})
		return
	}

	period := s.Metadata["period"]
	if period != "monthly" && period != "yearly" {
		period = "monthly"
	}

	db.Exec("DELETE FROM subscribed WHERE id_user = ?", idUser)

	_, err = db.Exec(
		"INSERT INTO subscribed (id_user, id_subscription_type, status, period, subscribed_at) VALUES (?, ?, 'Actif', ?, NOW())",
		idUser, idSub, period,
	)
	if err != nil {
		jsonError(w, "Erreur activation abonnement : "+err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "Abonnement activé avec succès"})
}
