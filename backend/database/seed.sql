-- =============================================================================
-- ServiceLink — Database Seed Data
-- Run AFTER schema.sql to populate required reference data.
-- =============================================================================

USE market_place;

-- ------------------------------------------------------------------
-- Service Categories (matches frontend dropdowns)
-- ------------------------------------------------------------------
INSERT INTO SERVICE_CATEGORIES (CATEGORY_ID, NAME, DESCRIPTION, ICON, IS_ACTIVE) VALUES
  (UUID(), 'Electrician',      'Electrical installation, repair, and maintenance',    '⚡', TRUE),
  (UUID(), 'Plumber',          'Plumbing, pipe repair, water heater installation',    '🔧', TRUE),
  (UUID(), 'Carpenter',        'Furniture, woodwork, door and window repair',         '🪚', TRUE),
  (UUID(), 'Painter',          'Interior and exterior painting services',             '🖌️', TRUE),
  (UUID(), 'Cleaner',          'Home and office deep cleaning services',              '🧹', TRUE),
  (UUID(), 'General Handyman', 'General repairs, assembly, and odd jobs',             '🛠️', TRUE);

-- ------------------------------------------------------------------
-- Default Admin Account
-- Password: Admin@1234  (bcrypt hash — change before production)
-- ------------------------------------------------------------------
INSERT INTO USERS (USER_ID, EMAIL, PASSWORD_HASH, ROLE, NAME, PHONE, LOCATION, IS_VERIFIED)
VALUES (
  UUID(),
  'admin@servicelink.local',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt of 'Admin@1234'
  'Admin',
  'System Admin',
  '+251911000000',
  'bole',
  TRUE
)
ON DUPLICATE KEY UPDATE EMAIL = EMAIL;
