CREATE DATABASE IF NOT EXISTS silverhappy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE silverhappy;

CREATE TABLE users (
    id_user VARCHAR(255) PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(10),
    birth_date DATE,
    active BOOLEAN DEFAULT TRUE,
    verified_email BOOLEAN DEFAULT FALSE,
    tutorial_seen BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL
);

CREATE TABLE seniors (
    id_senior VARCHAR(255) PRIMARY KEY,
    membership_number VARCHAR(20) NOT NULL UNIQUE,
    subscription_date DATE NOT NULL,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    mobility VARCHAR(30)
);

CREATE TABLE providers (
    id_provider VARCHAR(255) PRIMARY KEY,
    siret_number VARCHAR(14) NOT NULL UNIQUE,
    company_name VARCHAR(100) NOT NULL UNIQUE,
    validation_status VARCHAR(20) NOT NULL,
    average_rating DECIMAL(2,1),
    commission_rate DECIMAL(5,2)
);

CREATE TABLE subscription_types (
    id_subscription_type VARCHAR(255) PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    user_type VARCHAR(20) NOT NULL,
    monthly_price DECIMAL(10,2),
    yearly_price DECIMAL(10,2)
);

CREATE TABLE contracts (
    id_contract VARCHAR(255) PRIMARY KEY,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    auto_renew BOOLEAN DEFAULT FALSE,
    id_provider VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_provider) REFERENCES providers(id_provider)
);

CREATE TABLE service_categories (
    id_service_category VARCHAR(255) PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE service_types (
    id_service_type VARCHAR(255) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    hourly_rate DECIMAL(10,2) NOT NULL,
    certification_required BOOLEAN NOT NULL,
    id_service_category VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_service_category) 
        REFERENCES service_categories(id_service_category)
);

CREATE TABLE service_requests (
    id_request VARCHAR(255) PRIMARY KEY,
    desired_date DATE NOT NULL,
    start_time TIME NOT NULL,
    estimated_duration INT NOT NULL,
    intervention_address VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL,
    id_user VARCHAR(255) NOT NULL,
    id_service_category VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_service_category) REFERENCES service_categories(id_service_category)
);

CREATE TABLE quotes (
    id_quote VARCHAR(255) PRIMARY KEY,
    quote_number VARCHAR(20) NOT NULL UNIQUE,
    amount_excl_tax DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL,
    amount_incl_tax DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL,
    id_request VARCHAR(255) NOT NULL UNIQUE,
    FOREIGN KEY (id_request) REFERENCES service_requests(id_request)
);

CREATE TABLE completed_services (
    id_completed_service VARCHAR(255) PRIMARY KEY,
    service_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    senior_amount DECIMAL(10,2) NOT NULL,
    platform_commission DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) NOT NULL,
    id_request VARCHAR(255) NOT NULL UNIQUE,
    FOREIGN KEY (id_request) REFERENCES service_requests(id_request)
);

CREATE TABLE reviews (
    id_review VARCHAR(255) PRIMARY KEY,
    rating DECIMAL(2,1) NOT NULL,
    comment TEXT,
    review_date DATETIME NOT NULL,
    visible BOOLEAN DEFAULT TRUE,
    id_provider VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_provider) REFERENCES providers(id_provider)
);

CREATE TABLE events (
    id_event VARCHAR(255) PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    event_type VARCHAR(20) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    max_places INT NOT NULL,
    price DECIMAL(10,2) NOT NULL
);

CREATE TABLE event_registrations (
    id_registration VARCHAR(255) PRIMARY KEY,
    registration_date DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL,
    paid BOOLEAN DEFAULT FALSE,
    id_user VARCHAR(255) NOT NULL,
    id_event VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_event) REFERENCES events(id_event)
);

CREATE TABLE invoices (
    id_invoice VARCHAR(255) PRIMARY KEY,
    invoice_number VARCHAR(20) NOT NULL UNIQUE,
    invoice_type VARCHAR(20) NOT NULL,
    amount_excl_tax DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL,
    amount_incl_tax DECIMAL(10,2) NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL,
    id_quote VARCHAR(255) NOT NULL UNIQUE,
    FOREIGN KEY (id_quote) REFERENCES quotes(id_quote)
);

CREATE TABLE messages (
    id_message VARCHAR(50) PRIMARY KEY,
    content TEXT,
    sent_at DATETIME,
    receiver VARCHAR(255),
    sender VARCHAR(255)
);

CREATE TABLE availability (
    id VARCHAR(50) PRIMARY KEY,
    time_slot DATETIME,
    is_available BOOLEAN,
    id_user VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE is_senior (
    id_user VARCHAR(255),
    id_senior VARCHAR(255),
    PRIMARY KEY (id_user, id_senior),
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_senior) REFERENCES seniors(id_senior)
);

CREATE TABLE is_provider (
    id_user VARCHAR(255),
    id_provider VARCHAR(255),
    PRIMARY KEY (id_user, id_provider),
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_provider) REFERENCES providers(id_provider)
);

CREATE TABLE show_type (
    id_service_type VARCHAR(255),
    id_request VARCHAR(255),
    PRIMARY KEY (id_service_type, id_request),
    FOREIGN KEY (id_service_type) REFERENCES service_types(id_service_type),
    FOREIGN KEY (id_request) REFERENCES service_requests(id_request)
);

CREATE TABLE subscribed (
    id_user VARCHAR(255),
    id_subscription_type VARCHAR(255),
    PRIMARY KEY (id_user, id_subscription_type),
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_subscription_type) REFERENCES subscription_types(id_subscription_type)
);

INSERT INTO users (id_user,email,password,role,last_name,first_name,phone,address,city,postal_code,birth_date,active,verified_email,tutorial_seen,created_at) VALUES ('usr_admin_default','admin@silverhappy.fr','Admin123!','admin','Administrateur','Super',NULL,NULL,NULL,NULL,NULL,TRUE,TRUE,TRUE,NOW())ON DUPLICATE KEY UPDATE id_user = id_user;