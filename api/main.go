package main

import (
	"log"
	"net/http"
)

func main() {
	initDB()

	// AUTH
	http.HandleFunc("POST /api/signup", handleSignup)
	http.HandleFunc("POST /api/login", handleLogin)

	// USERS
	http.HandleFunc("GET /api/users", authMiddleware(handleGetUsers))
	http.HandleFunc("GET /api/users/active-count", authMiddleware(handleGetActiveUsersCount))
	http.HandleFunc("GET /api/users/{id}", authMiddleware(handleGetUser))
	http.HandleFunc("POST /api/users", authMiddleware(handleCreateUser))
	http.HandleFunc("PATCH /api/users/{id}", authMiddleware(handleUpdateUser))
	http.HandleFunc("DELETE /api/users/{id}", authMiddleware(handleDeleteUser))

	// SENIORS
	http.HandleFunc("GET /api/seniors", authMiddleware(handleGetSeniors))
	http.HandleFunc("GET /api/seniors/{id}", authMiddleware(handleGetSenior))
	http.HandleFunc("POST /api/seniors", authMiddleware(handleCreateSenior))

	// SERVICE CATEGORIES
	http.HandleFunc("GET /api/service-categories", handleGetServiceCategories)
	http.HandleFunc("GET /api/service-categories/{id}", handleGetServiceCategory)
	http.HandleFunc("POST /api/service-categories", authMiddleware(handleCreateServiceCategory))

	// SERVICE TYPES
	http.HandleFunc("GET /api/service-types", handleGetServiceTypes)
	http.HandleFunc("GET /api/service-types/{id}", handleGetServiceType)
	http.HandleFunc("POST /api/service-types", authMiddleware(handleCreateServiceType))

	// SERVICE REQUESTS
	http.HandleFunc("GET /api/service-requests", authMiddleware(handleGetServiceRequests))
	http.HandleFunc("GET /api/service-requests/{id}", authMiddleware(handleGetServiceRequest))
	http.HandleFunc("POST /api/service-requests", authMiddleware(handleCreateServiceRequest))

	// QUOTES
	http.HandleFunc("GET /api/quotes", authMiddleware(handleGetQuotes))
	http.HandleFunc("GET /api/quotes/{id}", authMiddleware(handleGetQuote))
	http.HandleFunc("GET /api/quotes/count", authMiddleware(handleGetQuoteCount))
	http.HandleFunc("POST /api/quotes", authMiddleware(handleCreateQuote))

	// EVENTS
	http.HandleFunc("GET /api/events", handleGetEvents)
	http.HandleFunc("GET /api/events/{id}", handleGetEvent)
	http.HandleFunc("POST /api/events", authMiddleware(handleCreateEvent))

	// EVENT REGISTRATIONS
	http.HandleFunc("GET /api/event-registrations", authMiddleware(handleGetEventRegistrations))
	http.HandleFunc("POST /api/event-registrations", authMiddleware(handleCreateEventRegistration))

	// MESSAGES
	http.HandleFunc("GET /api/messages", authMiddleware(handleGetMessages))
	http.HandleFunc("POST /api/messages", authMiddleware(handleSendMessage))

	// INVOICES
	http.HandleFunc("GET /api/invoices", authMiddleware(handleGetInvoices))
	http.HandleFunc("GET /api/invoices/{id}", authMiddleware(handleGetInvoice))
	http.HandleFunc("POST /api/invoices", authMiddleware(handleCreateInvoice))

	if err := http.ListenAndServe(":8080", nil); err != nil {
		log.Fatal("Erreur démarrage serveur :", err)
	}
}
