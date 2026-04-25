-- Seed data for production-like initialization.
-- Run after database_schema.sql.

USE market_place;

-- Default service categories
INSERT INTO service_categories (name, description, icon) VALUES
('Electrician', 'Electrical repairs and installations', 'electric'),
('Plumber', 'Plumbing repairs and installations', 'plumbing'),
('Carpenter', 'Carpentry and furniture work', 'hammer'),
('Painter', 'Interior and exterior painting', 'paint'),
('Cleaner', 'House cleaning services', 'clean');
