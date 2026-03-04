package main

import "time"

// USERS
type User struct {
	ID            string    `json:"id_user"`
	Email         string    `json:"email"`
	Password      string    `json:"-"`
	Role          string    `json:"role"`
	LastName      string    `json:"last_name"`
	FirstName     string    `json:"first_name"`
	Phone         string    `json:"phone,omitempty"`
	Address       string    `json:"address,omitempty"`
	City          string    `json:"city,omitempty"`
	PostalCode    string    `json:"postal_code,omitempty"`
	BirthDate     string    `json:"birth_date,omitempty"`
	Active        bool      `json:"active"`
	VerifiedEmail bool      `json:"verified_email"`
	TutorialSeen  bool      `json:"tutorial_seen"`
	CreatedAt     time.Time `json:"created_at"`
}

// SENIORS
type Senior struct {
	ID                    string `json:"id_senior"`
	MembershipNumber      string `json:"membership_number"`
	SubscriptionDate      string `json:"subscription_date"`
	EmergencyContactName  string `json:"emergency_contact_name,omitempty"`
	EmergencyContactPhone string `json:"emergency_contact_phone,omitempty"`
	Mobility              string `json:"mobility,omitempty"`
}

// PROVIDERS
type Provider struct {
	ID               string  `json:"id_provider"`
	SiretNumber      string  `json:"siret_number"`
	CompanyName      string  `json:"company_name"`
	ValidationStatus string  `json:"validation_status"`
	AverageRating    float32 `json:"average_rating"`
	CommissionRate   float32 `json:"commission_rate"`
}

// SUBSCRIPTIONS
type SubscriptionType struct {
	ID           string  `json:"id_subscription_type"`
	Name         string  `json:"name"`
	UserType     string  `json:"user_type"`
	MonthlyPrice float64 `json:"monthly_price,omitempty"`
	YearlyPrice  float64 `json:"yearly_price,omitempty"`
}

// CONTRACTS
type Contract struct {
	ID            string  `json:"id_contract"`
	StartDate     string  `json:"start_date"`
	EndDate       string  `json:"end_date"`
	Amount        float64 `json:"amount"`
	PaymentMethod string  `json:"payment_method"`
	Status        string  `json:"status"`
	AutoRenew     bool    `json:"auto_renew"`
	ProviderID    string  `json:"id_provider"`
}

// SERVICE CATEGORIES & TYPES
type ServiceCategory struct {
	ID          string `json:"id_service_category"`
	Name        string `json:"name"`
	Description string `json:"description,omitempty"`
}

type ServiceType struct {
	ID                    string  `json:"id_service_type"`
	Name                  string  `json:"name"`
	Description           string  `json:"description,omitempty"`
	HourlyRate            float64 `json:"hourly_rate"`
	CertificationRequired bool    `json:"certification_required"`
	ServiceCategoryID     string  `json:"id_service_category"`
}

// SERVICE REQUESTS & QUOTES
type ServiceRequest struct {
	ID                  string    `json:"id_request"`
	DesiredDate         string    `json:"desired_date"`
	StartTime           string    `json:"start_time"`
	EstimatedDuration   int       `json:"estimated_duration"`
	InterventionAddress string    `json:"intervention_address"`
	Status              string    `json:"status"`
	CreatedAt           time.Time `json:"created_at"`
	UserID              string    `json:"id_user"`
	ServiceCategoryID   string    `json:"id_service_category"`
}

type Quote struct {
	ID            string    `json:"id_quote"`
	QuoteNumber   string    `json:"quote_number"`
	AmountExclTax float64   `json:"amount_excl_tax"`
	TaxRate       float64   `json:"tax_rate"`
	AmountInclTax float64   `json:"amount_incl_tax"`
	Status        string    `json:"status"`
	CreatedAt     time.Time `json:"created_at"`
	RequestID     string    `json:"id_request"`
}

// COMPLETED SERVICES
type CompletedService struct {
	ID                 string  `json:"id_completed_service"`
	ServiceDate        string  `json:"service_date"`
	StartTime          string  `json:"start_time"`
	EndTime            string  `json:"end_time"`
	SeniorAmount       float64 `json:"senior_amount"`
	PlatformCommission float64 `json:"platform_commission"`
	Status             string  `json:"status"`
	RequestID          string  `json:"id_request"`
}

// REVIEWS
type Review struct {
	ID         string    `json:"id_review"`
	Rating     float32   `json:"rating"`
	Comment    string    `json:"comment,omitempty"`
	ReviewDate time.Time `json:"review_date"`
	Visible    bool      `json:"visible"`
	ProviderID string    `json:"id_provider"`
}

// EVENTS
type Event struct {
	ID        string  `json:"id_event"`
	Title     string  `json:"title"`
	EventType string  `json:"event_type"`
	StartDate string  `json:"start_date"`
	EndDate   string  `json:"end_date"`
	MaxPlaces int     `json:"max_places"`
	Price     float64 `json:"price"`
}

type EventRegistration struct {
	ID               string    `json:"id_registration"`
	RegistrationDate time.Time `json:"registration_date"`
	Status           string    `json:"status"`
	Paid             bool      `json:"paid"`
	UserID           string    `json:"id_user"`
	EventID          string    `json:"id_event"`
}

// INVOICES
type Invoice struct {
	ID            string  `json:"id_invoice"`
	InvoiceNumber string  `json:"invoice_number"`
	InvoiceType   string  `json:"invoice_type"`
	AmountExclTax float64 `json:"amount_excl_tax"`
	TaxRate       float64 `json:"tax_rate"`
	AmountInclTax float64 `json:"amount_incl_tax"`
	IssueDate     string  `json:"issue_date"`
	DueDate       string  `json:"due_date"`
	Status        string  `json:"status"`
	QuoteID       string  `json:"id_quote"`
}

// MESSAGES
type Message struct {
	ID       string    `json:"id_message"`
	Content  string    `json:"content"`
	SentAt   time.Time `json:"sent_at"`
	Receiver string    `json:"receiver"`
	Sender   string    `json:"sender"`
}

// AVAILABILITY
type Availability struct {
	ID          string `json:"id"`
	TimeSlot    string `json:"time_slot"`
	IsAvailable bool   `json:"is_available"`
	UserID      string `json:"id_user"`
}

// AUTH PAYLOADS
type SignupPayload struct {
	FirstName string `json:"first_name"`
	LastName  string `json:"last_name"`
	BirthDate string `json:"birth_date"`
	Email     string `json:"email"`
	Password  string `json:"password"`
	Role      string `json:"role"`
}

type LoginPayload struct {
	Email    string `json:"email"`
	Password string `json:"password"`
}

type ErrorResponse struct {
	Error string `json:"error"`
}

type TokenResponse struct {
	Token string `json:"token"`
	User  User   `json:"user"`
}
