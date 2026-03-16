CREATE DATABASE IF NOT EXISTS silverhappy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE silverhappy;

CREATE TABLE users (
    id_user VARCHAR(255) PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('senior','prestataire','admin') NOT NULL,
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
    created_at DATETIME NOT NULL,
    membership_number VARCHAR(20) UNIQUE,
    subscription_date DATE,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    mobility VARCHAR(30),
    siret_number VARCHAR(14) UNIQUE,
    company_name VARCHAR(100) UNIQUE,
    validation_status VARCHAR(20),
    average_rating DECIMAL(2,1),
    commission_rate DECIMAL(5,2),
    zone VARCHAR(255),
    iban VARCHAR(64),
    provider_description TEXT,
    skills_text TEXT,
    provider_updated_at DATETIME,
    INDEX idx_users_role (role),
    INDEX idx_users_validation_status (validation_status)
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
    id_user VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    auto_renew BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    INDEX idx_user (id_user),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date)
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
    id_user VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user)
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

CREATE TABLE products (
    id_product VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    sales INT NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'En stock'
);

CREATE TABLE orders (
    id_order VARCHAR(255) PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    id_user VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    order_date DATETIME NOT NULL,
    delivery_method VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'En attente',
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE order_items (
    id_order VARCHAR(255) NOT NULL,
    id_product VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id_order, id_product),
    FOREIGN KEY (id_order) REFERENCES orders(id_order),
    FOREIGN KEY (id_product) REFERENCES products(id_product)
);

CREATE TABLE contents (
    id_content VARCHAR(255) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    content_body TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'Brouillon',
    created_at DATETIME NOT NULL,
    views INT NOT NULL DEFAULT 0,
    author_id VARCHAR(255),
    FOREIGN KEY (author_id) REFERENCES users(id_user)
);

CREATE TABLE notifications (
    id_notification VARCHAR(255) PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255),
    message TEXT,
    created_at DATETIME NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    id_user VARCHAR(255),
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE provider_availabilities (
    id_availability INT AUTO_INCREMENT PRIMARY KEY,
    id_user VARCHAR(255) NOT NULL,
    available_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE provider_missions (
    id_mission VARCHAR(255) PRIMARY KEY,
    mission_title VARCHAR(255) NOT NULL,
    mission_description TEXT,
    mission_date DATE,
    status VARCHAR(50) NOT NULL DEFAULT 'Proposee',
    id_user VARCHAR(255),
    accepted_at DATETIME,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE provider_invoices (
    id_invoice VARCHAR(255) PRIMARY KEY,
    id_user VARCHAR(255) NOT NULL,
    month_label VARCHAR(7) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(50) NOT NULL DEFAULT 'Brouillon',
    generated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_provider_month (id_user, month_label),
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE provider_payments (
    id_payment VARCHAR(255) PRIMARY KEY,
    id_invoice VARCHAR(255) NOT NULL,
    id_user VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid_at DATETIME,
    status VARCHAR(50) NOT NULL DEFAULT 'En attente',
    FOREIGN KEY (id_invoice) REFERENCES provider_invoices(id_invoice),
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE IF NOT EXISTS medical_appointments (
    id_appointment VARCHAR(255) PRIMARY KEY,
    id_user VARCHAR(255) NOT NULL,
    appointment_date DATETIME NOT NULL,
    appointment_type VARCHAR(100),
    doctor_name VARCHAR(100),
    medical_reason_anonymized VARCHAR(255) DEFAULT 'Visite médicale',
    notes_internal TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'Programmé',
    created_at DATETIME NOT NULL,
    updated_at DATETIME,
    created_by VARCHAR(255),
    INDEX idx_user (id_user),
    INDEX idx_status (status),
    INDEX idx_appointment_date (appointment_date),
    CONSTRAINT fk_medical_appointments_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_medical_appointments_creator FOREIGN KEY (created_by) REFERENCES users(id_user) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS support_tickets (
    id_ticket VARCHAR(255) PRIMARY KEY,
    ticket_number VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100),
    priority VARCHAR(50) NOT NULL DEFAULT 'Moyen',
    status VARCHAR(50) NOT NULL DEFAULT 'Ouvert',
    id_user VARCHAR(255) NOT NULL,
    assigned_to VARCHAR(255),
    created_at DATETIME NOT NULL,
    updated_at DATETIME,
    resolved_at DATETIME,
    resolution_notes TEXT,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_assigned (assigned_to),
    INDEX idx_created_at (created_at),
    INDEX idx_user (id_user),
    CONSTRAINT fk_support_tickets_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    CONSTRAINT fk_support_tickets_assigned FOREIGN KEY (assigned_to) REFERENCES users(id_user) ON DELETE SET NULL
);

INSERT INTO users (id_user,email,password,role,last_name,first_name,phone,address,city,postal_code,birth_date,active,verified_email,tutorial_seen,created_at) VALUES ('usr_admin_default','admin@silverhappy.fr','Admin123!','admin','Administrateur','Super',NULL,NULL,NULL,NULL,NULL,TRUE,TRUE,TRUE,NOW())ON DUPLICATE KEY UPDATE id_user = id_user;

INSERT INTO users (
    id_user,email,password,role,last_name,first_name,phone,address,city,postal_code,birth_date,
    active,verified_email,tutorial_seen,created_at,
    siret_number,company_name,validation_status,average_rating,commission_rate,zone,iban,provider_description,skills_text,provider_updated_at
) VALUES (
    'usr_presta_default','prestataire@silverhappy.fr','Azerty123!','prestataire','Martin','Sophie','0600000001',NULL,NULL,NULL,NULL,
    TRUE,TRUE,TRUE,NOW(),
    '12345678901234','Aide & Compagnie','Valide',4.8,12.50,'Lyon et environs','FR7630006000011234567890189',
    'Prestataire polyvalente pour accompagnement, courses et aide quotidienne.',
    'Aide a domicile, courses, compagnie, accompagnement rendez-vous',
    NOW()
) ON DUPLICATE KEY UPDATE id_user = id_user;