CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    role ENUM('senior','prestataire','admin') NOT NULL,
    birthdate DATE,
    address VARCHAR(255),
    phone VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('normal','renouvellement') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    price DECIMAL(6,2) NOT NULL,
    status ENUM('active','expired','pending') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE prestations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    price DECIMAL(6,2),
    prestataire_id INT,
    validated BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prestataire_id) REFERENCES users(id)
);

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    date DATETIME NOT NULL,
    location VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(6,2) NOT NULL,
    stock INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    prestation_id INT,
    amount DECIMAL(8,2) NOT NULL,
    date DATETIME NOT NULL,
    pdf_path VARCHAR(255),
    status ENUM('paid','pending','cancelled') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (prestation_id) REFERENCES prestations(id)
);

CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    prestataire_id INT,
    content TEXT,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (prestataire_id) REFERENCES users(id)
);

CREATE TABLE advices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);

-- Planning personnel (cours, services, événements)
CREATE TABLE planning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    prestation_id INT,
    event_id INT,
    date DATETIME NOT NULL,
    status ENUM('scheduled','completed','cancelled') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (prestation_id) REFERENCES prestations(id),
    FOREIGN KEY (event_id) REFERENCES events(id)
);

-- Table pour les abonnements prestataires
CREATE TABLE prestataire_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestataire_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    price DECIMAL(6,2) NOT NULL,
    status ENUM('active','expired','pending') NOT NULL,
    FOREIGN KEY (prestataire_id) REFERENCES users(id)
);

-- Table pour les documents numériques (module complémentaire)
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table pour les évaluations des prestations
CREATE TABLE prestation_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestation_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prestation_id) REFERENCES prestations(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table pour les disponibilités des prestataires
CREATE TABLE prestataire_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestataire_id INT NOT NULL,
    available_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (prestataire_id) REFERENCES users(id)
);
