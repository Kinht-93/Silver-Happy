package main

import (
	"log"
	"net/http"
	"os"

	"github.com/joho/godotenv"
	"github.com/stripe/stripe-go/v76"
)

func main() {
	initDB()

	err := godotenv.Load()
	if err != nil {
		log.Fatal("Erreur lors du chargement du fichier .env")
	}

	stripe.Key = os.Getenv("STRIPE_SECRET_KEY")

	// AUTH
	http.HandleFunc("POST /api/signup", handleSignup)
	http.HandleFunc("POST /api/login", handleLogin)

	// USERS
	http.HandleFunc("GET /api/users", authMiddleware(handleGetUsers))
	http.HandleFunc("GET /api/users-summary", authMiddleware(handleGetUsersSummary))
	http.HandleFunc("GET /api/active-users", authMiddleware(handleGetActiveUsers))
	http.HandleFunc("GET /api/users/active-count", authMiddleware(handleGetActiveUsersCount))
	http.HandleFunc("GET /api/users/{id}", authMiddleware(handleGetUser))
	http.HandleFunc("POST /api/users/{id}/activity", authMiddleware(handleTouchUserActivity))
	http.HandleFunc("GET /api/users/{id}/provider-dashboard", authMiddleware(handleGetProviderDashboard))
	http.HandleFunc("GET /api/users/{id}/provider-availabilities", authMiddleware(handleGetProviderOwnAvailabilities))
	http.HandleFunc("POST /api/users/{id}/provider-availabilities", authMiddleware(handleCreateProviderAvailability))
	http.HandleFunc("DELETE /api/users/{id}/provider-availabilities/{availabilityId}", authMiddleware(handleDeleteProviderAvailability))
	http.HandleFunc("GET /api/users/{id}/provider-missions", authMiddleware(handleGetProviderMissions))
	http.HandleFunc("GET /api/users/{id}/provider-billing", authMiddleware(handleGetProviderBilling))
	http.HandleFunc("POST /api/users/{id}/provider-invoices/generate", authMiddleware(handleGenerateProviderInvoice))
	http.HandleFunc("POST /api/users", authMiddleware(handleCreateUser))
	http.HandleFunc("PATCH /api/users/{id}", authMiddleware(handleUpdateUser))
	http.HandleFunc("GET /api/users/{id}/senior-settings", authMiddleware(handleGetSeniorSettings))
	http.HandleFunc("PATCH /api/users/{id}/senior-settings", authMiddleware(handleUpdateSeniorSettings))
	http.HandleFunc("DELETE /api/users/{id}", authMiddleware(handleDeleteUser))
	http.HandleFunc("GET /api/employees", authMiddleware(handleGetEmployees))

	// CONTRACTS
	http.HandleFunc("GET /api/contracts", authMiddleware(handleGetContracts))
	http.HandleFunc("GET /api/contracts/{id}", authMiddleware(handleGetContract))
	http.HandleFunc("POST /api/contracts", authMiddleware(handleCreateContract))
	http.HandleFunc("PATCH /api/contracts/{id}", authMiddleware(handleUpdateContract))
	http.HandleFunc("DELETE /api/contracts/{id}", authMiddleware(handleDeleteContract))
	http.HandleFunc("GET /api/users-without-contract", authMiddleware(handleGetUsersWithoutActiveContract))

	// MEDICAL APPOINTMENTS
	http.HandleFunc("GET /api/medical-appointments", authMiddleware(handleGetMedicalAppointments))
	http.HandleFunc("GET /api/medical-appointments/{id}", authMiddleware(handleGetMedicalAppointment))
	http.HandleFunc("POST /api/medical-appointments", authMiddleware(handleCreateMedicalAppointment))
	http.HandleFunc("PATCH /api/medical-appointments/{id}", authMiddleware(handleUpdateMedicalAppointment))
	http.HandleFunc("DELETE /api/medical-appointments/{id}", authMiddleware(handleDeleteMedicalAppointment))
	http.HandleFunc("GET /api/users-for-appointments", authMiddleware(handleGetUsersForAppointments))

	// COMPLETED SERVICES
	http.HandleFunc("GET /api/service-completed/count", authMiddleware(handleGetCompletedServiceCount))
	http.HandleFunc("GET /api/completed-services-admin", authMiddleware(handleGetCompletedServicesAdmin))

	// SERVICE CATEGORIES
	http.HandleFunc("GET /api/service-categories", handleGetServiceCategories)
	http.HandleFunc("GET /api/service-categories-admin", authMiddleware(handleGetAllServiceCategories))
	http.HandleFunc("GET /api/service-categories/{id}", handleGetServiceCategory)
	http.HandleFunc("POST /api/service-categories", authMiddleware(handleCreateServiceCategory))
	http.HandleFunc("PATCH /api/service-categories/{id}", authMiddleware(handleUpdateServiceCategory))
	http.HandleFunc("DELETE /api/service-categories/{id}", authMiddleware(handleDeleteServiceCategory))

	// SERVICE TYPES
	http.HandleFunc("GET /api/service-types", handleGetServiceTypes)
	http.HandleFunc("GET /api/service-types-admin", authMiddleware(handleGetAllServiceTypes))
	http.HandleFunc("GET /api/service-types/{id}", handleGetServiceType)
	http.HandleFunc("POST /api/service-types", authMiddleware(handleCreateServiceType))
	http.HandleFunc("PATCH /api/service-types/{id}", authMiddleware(handleUpdateServiceType))
	http.HandleFunc("DELETE /api/service-types/{id}", authMiddleware(handleDeleteServiceType))

	// SERVICE REQUESTS
	http.HandleFunc("GET /api/service-requests", authMiddleware(handleGetServiceRequests))
	http.HandleFunc("GET /api/users/{id}/service-requests", authMiddleware(handleGetUserServiceRequests))
	http.HandleFunc("GET /api/service-requests/{id}", authMiddleware(handleGetServiceRequest))
	http.HandleFunc("POST /api/service-requests", authMiddleware(handleCreateServiceRequest))
	http.HandleFunc("GET /api/provider-availabilities", authMiddleware(handleGetProviderAvailabilities))
	http.HandleFunc("POST /api/provider-availabilities/{id}/reserve", authMiddleware(handleReserveProviderAvailability))
	http.HandleFunc("GET /api/users/{id}/completed-services", authMiddleware(handleGetUserCompletedServices))

	// QUOTES
	http.HandleFunc("GET /api/quotes", authMiddleware(handleGetQuotes))
	http.HandleFunc("GET /api/users/{id}/quotes", authMiddleware(handleGetUserQuotes))
	http.HandleFunc("GET /api/quotes/{id}", authMiddleware(handleGetQuote))
	http.HandleFunc("GET /api/quotes/count", authMiddleware(handleGetQuoteCount))
	http.HandleFunc("POST /api/quotes", authMiddleware(handleCreateQuote))
	http.HandleFunc("GET /api/admin-quotes", authMiddleware(handleGetAdminQuotes))
	http.HandleFunc("POST /api/admin-quotes", authMiddleware(handleCreateAdminQuote))
	http.HandleFunc("PATCH /api/admin-quotes/{id}", authMiddleware(handleUpdateAdminQuote))
	http.HandleFunc("DELETE /api/admin-quotes/{id}", authMiddleware(handleDeleteAdminQuote))

	// NOTIFICATIONS
	http.HandleFunc("POST /api/notifications/probleme/count", authMiddleware(handleGetProblemeCount))

	// EVENTS
	http.HandleFunc("GET /api/events", handleGetEvents)
	http.HandleFunc("GET /api/events/{id}", handleGetEvent)
	http.HandleFunc("POST /api/events", authMiddleware(handleCreateEvent))
	http.HandleFunc("GET /api/admin-events", authMiddleware(handleGetAdminEvents))
	http.HandleFunc("POST /api/admin-events", authMiddleware(handleCreateAdminEvent))
	http.HandleFunc("PATCH /api/admin-events/{id}", authMiddleware(handleUpdateAdminEvent))
	http.HandleFunc("DELETE /api/admin-events/{id}", authMiddleware(handleDeleteAdminEvent))

	// EVENT REGISTRATIONS
	http.HandleFunc("GET /api/event-registrations", authMiddleware(handleGetEventRegistrations))
	http.HandleFunc("GET /api/users/{id}/event-registrations", authMiddleware(handleGetUserEventRegistrations))
	http.HandleFunc("POST /api/event-registrations", authMiddleware(handleCreateEventRegistration))
	http.HandleFunc("DELETE /api/event-registrations/{id}", authMiddleware(handleDeleteEventRegistration))

	// TRANSACTIONS
	http.HandleFunc("GET /api/transactions/last", authMiddleware(handleGetLastTransactions))
	http.HandleFunc("GET /api/transactions/pending-providers", authMiddleware(handleGetPendingProvidersCount))
	http.HandleFunc("GET /api/service-requests/pending", authMiddleware(handleGetPendingServiceRequestsCount))
	http.HandleFunc("POST /api/provider-missions/{id}/accept", authMiddleware(handleAcceptProviderMission))

	// MESSAGES
	http.HandleFunc("GET /api/messages", authMiddleware(handleGetMessages))
	http.HandleFunc("POST /api/messages", authMiddleware(handleSendMessage))

	// INVOICES
	http.HandleFunc("GET /api/invoices", authMiddleware(handleGetInvoices))
	http.HandleFunc("GET /api/users/{id}/invoices", authMiddleware(handleGetUserInvoices))
	http.HandleFunc("GET /api/invoices/{id}", authMiddleware(handleGetInvoice))
	http.HandleFunc("POST /api/invoices", authMiddleware(handleCreateInvoice))
	http.HandleFunc("GET /api/admin-invoices", authMiddleware(handleGetAdminInvoices))
	http.HandleFunc("GET /api/admin-invoices/stats", authMiddleware(handleGetInvoiceStats))
	http.HandleFunc("PATCH /api/admin-invoices/{id}/status", authMiddleware(handleUpdateInvoiceStatus))

	// SUBSCRIPTIONS
	http.HandleFunc("GET /api/subscription-types-admin", authMiddleware(handleGetSubscriptionTypesAdmin))
	http.HandleFunc("GET /api/subscription-types-admin/stats", authMiddleware(handleGetSubscriptionStats))
	http.HandleFunc("POST /api/subscription-types-admin", authMiddleware(handleCreateSubscriptionType))
	http.HandleFunc("PATCH /api/subscription-types-admin/{id}", authMiddleware(handleUpdateSubscriptionType))
	http.HandleFunc("DELETE /api/subscription-types-admin/{id}", authMiddleware(handleDeleteSubscriptionType))

	// SUPPORT TICKETS
	http.HandleFunc("GET /api/support-tickets", authMiddleware(handleGetSupportTickets))
	http.HandleFunc("POST /api/support-tickets", authMiddleware(handleCreateSupportTicket))
	http.HandleFunc("PATCH /api/support-tickets/{id}", authMiddleware(handleUpdateSupportTicket))
	http.HandleFunc("PATCH /api/support-tickets/{id}/resolve", authMiddleware(handleResolveSupportTicket))
	http.HandleFunc("DELETE /api/support-tickets/{id}", authMiddleware(handleDeleteSupportTicket))

	// CONTENTS
	http.HandleFunc("GET /api/contents", authMiddleware(handleGetAllContents))
	http.HandleFunc("POST /api/contents", authMiddleware(handleCreateContent))
	http.HandleFunc("PATCH /api/contents/{id}", authMiddleware(handleUpdateContent))
	http.HandleFunc("DELETE /api/contents/{id}", authMiddleware(handleDeleteContent))

	// PRODUCTS
	http.HandleFunc("GET /api/products", authMiddleware(handleGetAllProducts))
	http.HandleFunc("POST /api/products", authMiddleware(handleCreateProduct))
	http.HandleFunc("PATCH /api/products/{id}", authMiddleware(handleUpdateProduct))
	http.HandleFunc("DELETE /api/products/{id}", authMiddleware(handleDeleteProduct))

	// PRODUCT CATEGORIES
	http.HandleFunc("GET /api/product-categories", authMiddleware(handleGetProductCategories))

	// ORDERS
	http.HandleFunc("GET /api/orders", authMiddleware(handleGetAllOrders))
	http.HandleFunc("GET /api/orders/stats", authMiddleware(handleGetOrderStats))
	http.HandleFunc("PATCH /api/orders/{id}", authMiddleware(handleUpdateOrderStatus))

	//STRIPE
	http.HandleFunc("POST /api/events/{id}/checkout", authMiddleware(handleCreateEventCheckout))
	http.HandleFunc("GET /api/events/checkout-confirm", authMiddleware(handleConfirmEventCheckout))
	http.HandleFunc("POST /api/stripe/webhook", handleStripeWebhook)

	if err := http.ListenAndServe(":8080", nil); err != nil {
		log.Printf("Erreur démarrage serveur sur port 8080, essai sur port 5555")
		if err := http.ListenAndServe(":5555", nil); err != nil {
			log.Fatal("Erreur démarrage serveur sur port 5555:", err)
		}
	}

}
