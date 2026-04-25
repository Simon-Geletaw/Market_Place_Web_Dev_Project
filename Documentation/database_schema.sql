-- =====================================================
-- SECURE LOCAL SERVICE MARKETPLACE - DATABASE SCHEMA
-- Project: Handy-Thumbtack Model
-- =====================================================

-- Drop existing tables (for clean setup)
DROP TABLE IF EXISTS status_history;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS offers;
DROP TABLE IF EXISTS service_requests;
DROP TABLE IF EXISTS service_categories;
DROP TABLE IF EXISTS users;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'provider', 'admin') NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255) COMMENT 'Sub-city in Addis Ababa',
    rating_average DECIMAL(3,2) DEFAULT 0.00 COMMENT 'For providers only',
    total_reviews INT DEFAULT 0 COMMENT 'For providers only',
    is_verified BOOLEAN DEFAULT FALSE COMMENT 'For provider verification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_role (role),
    INDEX idx_location (location),
    INDEX idx_rating (rating_average)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: service_categories
-- =====================================================
CREATE TABLE service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) COMMENT 'Icon identifier for UI',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: service_requests
-- =====================================================
CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    category_id INT NOT NULL,
    description TEXT NOT NULL,
    preferred_date DATE,
    status ENUM('Requested', 'Negotiating', 'Assigned', 'Completed', 'Reviewed') 
        NOT NULL DEFAULT 'Requested',
    location VARCHAR(255) NOT NULL,
    accepted_offer_id INT DEFAULT NULL COMMENT 'Links to the accepted offer',
    completion_photo VARCHAR(500) COMMENT 'Path to uploaded photo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE RESTRICT,
    
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_category (category_id),
    INDEX idx_preferred_date (preferred_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: offers
-- =====================================================
CREATE TABLE offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    provider_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    message TEXT COMMENT 'Optional message from provider',
    status ENUM('pending', 'accepted', 'rejected', 'countered') 
        NOT NULL DEFAULT 'pending',
    counter_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Customer counter-offer',
    counter_message TEXT COMMENT 'Customer counter-offer message',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_request (request_id),
    INDEX idx_provider (provider_id),
    INDEX idx_status (status),
    
    -- Prevent duplicate offers from same provider to same request
    UNIQUE KEY unique_provider_request (request_id, provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: reviews
-- =====================================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL UNIQUE COMMENT 'One review per completed job',
    customer_id INT NOT NULL,
    provider_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_provider (provider_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: audit_logs
-- =====================================================
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL COMMENT 'e.g., login, offer_accepted, status_changed',
    entity_type VARCHAR(50) COMMENT 'e.g., service_request, offer, review',
    entity_id INT COMMENT 'ID of the affected entity',
    details TEXT COMMENT 'JSON or text with additional context',
    ip_address VARCHAR(45) COMMENT 'User IP address',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: status_history
-- =====================================================
CREATE TABLE status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    duration_seconds INT COMMENT 'Time spent in old_status',
    changed_by INT COMMENT 'User who triggered the change',
    notes TEXT COMMENT 'Optional context about the change',
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_request (request_id),
    INDEX idx_new_status (new_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA FOR TESTING
-- =====================================================

-- Insert service categories
INSERT INTO service_categories (name, description, icon) VALUES
('Electrician', 'Electrical repairs and installations', 'electric'),
('Plumber', 'Plumbing repairs and installations', 'plumbing'),
('Carpenter', 'Carpentry and furniture work', 'hammer'),
('Painter', 'Interior and exterior painting', 'paint'),
('Cleaner', 'House cleaning services', 'clean');

-- Insert test users (passwords are hashed with password_hash('password123', PASSWORD_DEFAULT))
INSERT INTO users (email, password_hash, role, name, phone, location) VALUES
('customer@test.com', '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'customer', 'Test Customer', '0911234567', 'Bole'),
('provider@test.com', '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'provider', 'Test Provider', '0912345678', 'Kirkos'),
('admin@test.com', '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'admin', 'Admin User', '0913456789', 'Addis Ketema');

-- =====================================================
-- STORED PROCEDURES FOR STATE MACHINE
-- =====================================================

DELIMITER $$

-- Procedure: Log state changes automatically
CREATE PROCEDURE log_status_change(
    IN p_request_id INT,
    IN p_old_status VARCHAR(50),
    IN p_new_status VARCHAR(50),
    IN p_changed_by INT
)
BEGIN
    DECLARE v_duration INT;
    
    -- Calculate duration in old status
    SELECT TIMESTAMPDIFF(SECOND, updated_at, NOW())
    INTO v_duration
    FROM service_requests
    WHERE id = p_request_id;
    
    -- Insert into status_history
    INSERT INTO status_history (request_id, old_status, new_status, duration_seconds, changed_by)
    VALUES (p_request_id, p_old_status, p_new_status, v_duration, p_changed_by);
END$$

-- Procedure: Accept an offer (trigger state change)
CREATE PROCEDURE accept_offer(
    IN p_offer_id INT,
    IN p_customer_id INT
)
BEGIN
    DECLARE v_request_id INT;
    DECLARE v_old_status VARCHAR(50);
    
    -- Get request details
    SELECT request_id INTO v_request_id
    FROM offers WHERE id = p_offer_id;
    
    SELECT status INTO v_old_status
    FROM service_requests WHERE id = v_request_id;
    
    -- Update offer status
    UPDATE offers SET status = 'accepted' WHERE id = p_offer_id;
    
    -- Update request status to Assigned
    UPDATE service_requests 
    SET status = 'Assigned', accepted_offer_id = p_offer_id 
    WHERE id = v_request_id;
    
    -- Log the change
    CALL log_status_change(v_request_id, v_old_status, 'Assigned', p_customer_id);
    
    -- Log audit
    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (p_customer_id, 'offer_accepted', 'offer', p_offer_id, 
            CONCAT('Request #', v_request_id, ' moved to Assigned'));
END$$

DELIMITER ;

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- View: Active service requests with category info
CREATE VIEW v_active_requests AS
SELECT 
    sr.id,
    sr.description,
    sr.preferred_date,
    sr.status,
    sr.location,
    sc.name AS category_name,
    u.name AS customer_name,
    u.phone AS customer_phone,
    sr.created_at
FROM service_requests sr
JOIN service_categories sc ON sr.category_id = sc.id
JOIN users u ON sr.customer_id = u.id
WHERE sr.status IN ('Requested', 'Negotiating');

-- View: Provider ratings summary
CREATE VIEW v_provider_ratings AS
SELECT 
    u.id AS provider_id,
    u.name AS provider_name,
    u.location,
    COUNT(r.id) AS total_reviews,
    AVG(r.rating) AS average_rating,
    SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) AS five_star_count,
    SUM(CASE WHEN r.rating >= 4 THEN 1 ELSE 0 END) AS four_star_plus_count
FROM users u
LEFT JOIN reviews r ON u.id = r.provider_id
WHERE u.role = 'provider'
GROUP BY u.id;
