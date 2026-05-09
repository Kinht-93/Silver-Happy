package main

import (
	"encoding/json"
	"net/http"
	"os"

	"github.com/stripe/stripe-go/v76"
	"github.com/stripe/stripe-go/v76/checkout/session"
)

// POST /api/subscriptions/checkout
// Crée une session Stripe Checkout pour payer un abonnement senior.
// Body JSON : { "id_user": "...", "id_subscription_type": "...", "period": "monthly" | "yearly" }
func handleCreateSubscriptionCheckout(w http.ResponseWriter, r *http.Request) {
	var body struct {
		UserID             string `json:"id_user"`
		SubscriptionTypeID string `json:"id_subscription_type"`
		Period             string `json:"period"` // "monthly" ou "yearly"
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil || body.UserID == "" || body.SubscriptionTypeID == "" {
		jsonError(w, "JSON invalide ou champs manquants", http.StatusBadRequest)
		return
	}
	if body.Period != "monthly" && body.Period != "yearly" {
		body.Period = "monthly"
	}

	// On récupère le plan depuis la BDD
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

	// On choisit le prix selon la période
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

	// Création de la session Stripe Checkout
	// On stocke les infos dans Metadata pour les retrouver au retour (success URL)
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

// GET /api/subscriptions/confirm?session_id=xxx
// Vérifie que le paiement Stripe est bien passé et active l'abonnement en BDD.
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

	// On vérifie que le paiement est bien confirmé côté Stripe
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

	// On vérifie qu'on n'a pas déjà activé cet abonnement (double-clic ou refresh)
	var existing int
	db.QueryRow("SELECT COUNT(*) FROM subscribed WHERE id_user = ? AND id_subscription_type = ? AND status = 'Actif'", idUser, idSub).Scan(&existing)
	if existing > 0 {
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]string{"message": "Abonnement déjà actif"})
		return
	}

	// On résilie l'abonnement actif précédent s'il existe
	db.Exec("UPDATE subscribed SET status = 'Résilié', cancelled_at = NOW() WHERE id_user = ? AND status = 'Actif'", idUser)

	// On active le nouvel abonnement
	_, err = db.Exec(
		"INSERT INTO subscribed (id_user, id_subscription_type, status, subscribed_at) VALUES (?, ?, 'Actif', NOW()) ON DUPLICATE KEY UPDATE status='Actif', subscribed_at=NOW(), cancelled_at=NULL",
		idUser, idSub,
	)
	if err != nil {
		jsonError(w, "Erreur activation abonnement : "+err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "Abonnement activé avec succès"})
}
