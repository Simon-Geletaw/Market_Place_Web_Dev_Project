-- Secure Local Service Marketplace schema for PostgreSQL 13+
-- Includes database setup, tables, constraints, functions, and views.

-- Create application role and database.
CREATE ROLE admin WITH LOGIN PASSWORD 'admin';
CREATE DATABASE market_place OWNER admin;

-- Connect to the database before running the remaining statements.
-- In psql, run: \connect market_place

-- Create a tablespace (update LOCATION to a valid directory on your server).
CREATE TABLESPACE market_place OWNER admin LOCATION '/var/lib/postgresql/market_place';
SET default_tablespace = market_place;

-- Ensure UUID generation extension is installed
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Enum types for status and role fields.
CREATE TYPE user_role AS ENUM ('Customer', 'Provider', 'Admin');
CREATE TYPE request_status AS ENUM ('Requested', 'Negotiating', 'Assigned', 'Completed', 'Reviewed');
CREATE TYPE offer_status AS ENUM ('Pending', 'Accepted', 'Rejected', 'Countered');

-- Drop existing tables (for clean setup).
DROP TABLE IF EXISTS marketplace.STATUS_HISTORY CASCADE;
DROP TABLE IF EXISTS marketplace.AUDIT_LOGS CASCADE;
DROP TABLE IF EXISTS marketplace.REVIEWS CASCADE;
DROP TABLE IF EXISTS marketplace.OFFERS CASCADE;
DROP TABLE IF EXISTS marketplace.SERVICE_REQUESTS CASCADE;
DROP TABLE IF EXISTS marketplace.SERVICE_CATEGORIES CASCADE;
DROP TABLE IF EXISTS marketplace.USERS CASCADE;

-- users: all platform identities (customer, provider, admin).
CREATE TABLE marketplace.USERS (
    user_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role user_role NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    rating_average DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_users_role ON users (role);
CREATE INDEX idx_users_location ON users (location);
CREATE INDEX idx_users_rating ON users (rating_average);

-- service_categories: taxonomy for requests and browsing.
CREATE TABLE marketplace.SERVICE_CATEGORIES (
    category_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- service_requests: customer jobs and workflow state machine.
CREATE TABLE marketplace.SERVICE_REQUESTS (
    request_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id UUID NOT NULL,
    category_id UUID NOT NULL,
    description TEXT NOT NULL,
    preferred_date DATE,
    status request_status NOT NULL DEFAULT 'Requested',
    location VARCHAR(255) NOT NULL,
    accepted_offer_id UUID,
    completion_photo VARCHAR(500),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),

    CONSTRAINT fk_service_requests_customer
        FOREIGN KEY (customer_id) REFERENCES marketplace.USERS(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_service_requests_category
        FOREIGN KEY (category_id) REFERENCES service_categories(category_id) ON DELETE RESTRICT
);

CREATE INDEX idx_service_requests_customer ON service_requests (customer_id);
CREATE INDEX idx_service_requests_status ON service_requests (status);
CREATE INDEX idx_service_requests_category ON service_requests (category_id);
CREATE INDEX idx_service_requests_preferred_date ON service_requests (preferred_date);
CREATE INDEX idx_service_requests_accepted_offer ON service_requests (accepted_offer_id);

-- offers: provider quotations and negotiation.
CREATE TABLE offers (
    offer_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    request_id UUID NOT NULL,
    provider_id UUID NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    message TEXT,
    status offer_status NOT NULL DEFAULT 'Pending',
    counter_price DECIMAL(10,2),
    counter_message TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),

    CONSTRAINT fk_offers_request
        FOREIGN KEY (request_id) REFERENCES service_requests(request_id) ON DELETE CASCADE,
    CONSTRAINT fk_offers_provider
        FOREIGN KEY (provider_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT unique_provider_request UNIQUE (request_id, provider_id)
);

CREATE INDEX idx_offers_request ON offers (request_id);
CREATE INDEX idx_offers_provider ON offers (provider_id);
CREATE INDEX idx_offers_status ON offers (status);

-- Add accepted offer FK after offers table exists to avoid circular reference.
ALTER TABLE service_requests
    ADD CONSTRAINT fk_service_requests_accepted_offer
    FOREIGN KEY (accepted_offer_id) REFERENCES offers(offer_id)
    ON DELETE SET NULL;

-- reviews: post-completion rating and feedback.
CREATE TABLE reviews (
    review_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    request_id UUID NOT NULL UNIQUE,
    customer_id UUID NOT NULL,
    provider_id UUID NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),

    CONSTRAINT fk_reviews_request
        FOREIGN KEY (request_id) REFERENCES service_requests(request_id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_customer
        FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_provider
        FOREIGN KEY (provider_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_reviews_provider ON reviews (provider_id);
CREATE INDEX idx_reviews_rating ON reviews (rating);

-- audit_logs: security and business action tracking.
CREATE TABLE audit_logs (
    audit_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id UUID,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMPTZ DEFAULT NOW(),

    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE INDEX idx_audit_logs_user ON audit_logs (user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs (action);
CREATE INDEX idx_audit_logs_created ON audit_logs (created_at);

-- status_history: request state transitions and timing analytics.
CREATE TABLE status_history (
    status_history_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    request_id UUID NOT NULL,
    old_status request_status,
    new_status request_status NOT NULL,
    duration_seconds INT,
    changed_by UUID,
    notes TEXT,
    changed_at TIMESTAMPTZ DEFAULT NOW(),

    CONSTRAINT fk_status_history_request
        FOREIGN KEY (request_id) REFERENCES service_requests(request_id) ON DELETE CASCADE,
    CONSTRAINT fk_status_history_changed_by
        FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE INDEX idx_status_history_request ON status_history (request_id);
CREATE INDEX idx_status_history_new_status ON status_history (new_status);

-- Sample data has been moved to:
-- Documentation/seed.sql
-- Documentation/sampledata.sql

-- Maintain updated_at automatically.
CREATE OR REPLACE FUNCTION marketplace.set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_users_updated_at
BEFORE UPDATE ON marketplace.USERS
FOR EACH ROW EXECUTE FUNCTION marketplace.set_updated_at();

CREATE TRIGGER trg_service_requests_updated_at
BEFORE UPDATE ON marketplace.SERVICE_REQUESTS
FOR EACH ROW EXECUTE FUNCTION marketplace.set_updated_at();

CREATE TRIGGER trg_offers_updated_at
BEFORE UPDATE ON offers
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Functions for workflow transitions and logging.
CREATE OR REPLACE FUNCTION log_status_change(
    p_request_id UUID,
    p_old_status request_status,
    p_new_status request_status,
    p_changed_by UUID
)
RETURNS void AS $$
DECLARE
    v_duration INT;
BEGIN
    SELECT EXTRACT(EPOCH FROM (NOW() - updated_at))::INT
    INTO v_duration
    FROM service_requests
    WHERE request_id = p_request_id;

    INSERT INTO status_history (request_id, old_status, new_status, duration_seconds, changed_by)
    VALUES (p_request_id, p_old_status, p_new_status, v_duration, p_changed_by);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION accept_offer(
    p_offer_id UUID,
    p_customer_id UUID
)
RETURNS void AS $$
DECLARE
    v_request_id UUID;
    v_old_status request_status;
BEGIN
    SELECT request_id INTO v_request_id
    FROM offers WHERE offer_id = p_offer_id;

    SELECT status INTO v_old_status
    FROM service_requests WHERE request_id = v_request_id;

    UPDATE offers SET status = 'Accepted' WHERE offer_id = p_offer_id;

    UPDATE service_requests
    SET status = 'Assigned', accepted_offer_id = p_offer_id
    WHERE request_id = v_request_id;

    PERFORM log_status_change(v_request_id, v_old_status, 'Assigned', p_customer_id);

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (p_customer_id, 'offer_accepted', 'offer', p_offer_id,
            'Request #' || v_request_id || ' moved to Assigned');
END;
$$ LANGUAGE plpgsql;

-- Read-optimized views for dashboards and browsing.
CREATE VIEW v_active_requests AS
SELECT
    sr.request_id,
    sr.description,
    sr.preferred_date,
    sr.status,
    sr.location,
    sc.name AS category_name,
    u.name AS customer_name,
    u.phone AS customer_phone,
    sr.created_at
FROM service_requests sr
JOIN service_categories sc ON sr.category_id = sc.category_id
JOIN users u ON sr.customer_id = u.user_id
WHERE sr.status IN ('Requested', 'Negotiating');

CREATE VIEW v_provider_ratings AS
SELECT
    u.user_id AS provider_id,
    u.name AS provider_name,
    u.location,
    COUNT(r.review_id) AS total_reviews,
    AVG(r.rating) AS average_rating,
    SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) AS five_star_count,
    SUM(CASE WHEN r.rating >= 4 THEN 1 ELSE 0 END) AS four_star_plus_count
FROM users u
LEFT JOIN reviews r ON u.user_id = r.provider_id
WHERE u.role = 'Provider'
GROUP BY u.user_id;
