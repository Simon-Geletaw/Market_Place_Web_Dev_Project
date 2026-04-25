-- Sample data for local testing only.
-- Run after database_schema.sql (and optionally seed.sql).

USE market_place;

-- Test users (passwords are hashed with password_hash('password123', PASSWORD_DEFAULT))
INSERT INTO users (email, password_hash, role, name, phone, location) VALUES
('customer@test.com', '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'customer', 'Test Customer', '0911234567', 'Bole'),
('provider@test.com', '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'provider', 'Test Provider', '0912345678', 'Kirkos'),
('admin@test.com', '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'admin', 'Admin User', '0913456789', 'Addis Ketema');
