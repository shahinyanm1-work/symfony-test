-- Improved Orders Database Schema
-- Created: 2025-10-03
-- Description: Enhanced schema for orders management with proper data types, indexes and constraints

-- Drop existing tables if they exist
DROP TABLE IF EXISTS orders_article;
DROP TABLE IF EXISTS orders;

-- Create orders table with improved structure
CREATE TABLE orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL COMMENT 'UUID v4 for external references',
  hash VARCHAR(64) NOT NULL COMMENT 'Unique hash for public access',
  user_id BIGINT UNSIGNED NULL COMMENT 'Reference to users table (if exists)',
  token VARCHAR(64) NOT NULL COMMENT 'Security token for order access',
  number VARCHAR(50) NULL COMMENT 'Human-readable order number',
  status SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Order status: 1=pending, 2=confirmed, 3=shipped, 4=delivered, 5=cancelled',
  email VARCHAR(150) NULL COMMENT 'Customer email',
  vat_type TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'VAT type: 0=none, 1=individual, 2=company',
  vat_number VARCHAR(64) NULL COMMENT 'VAT registration number',
  discount SMALLINT NULL COMMENT 'Discount percentage (0-100)',
  delivery_price DECIMAL(12,2) NULL COMMENT 'Delivery cost in order currency',
  delivery_type TINYINT UNSIGNED DEFAULT 0 COMMENT 'Delivery type: 0=standard, 1=express, 2=pickup',
  delivery_index VARCHAR(20) NULL COMMENT 'Postal code',
  delivery_country INT NULL COMMENT 'Country code (ISO)',
  delivery_region VARCHAR(100) NULL COMMENT 'State/Region',
  delivery_city VARCHAR(200) NULL COMMENT 'City',
  delivery_address VARCHAR(300) NULL COMMENT 'Street address',
  delivery_phone VARCHAR(50) NULL COMMENT 'Contact phone',
  client_name VARCHAR(150) NULL COMMENT 'Customer first name',
  client_surname VARCHAR(150) NULL COMMENT 'Customer last name',
  company_name VARCHAR(255) NULL COMMENT 'Company name (for B2B)',
  pay_type TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Payment type: 0=card, 1=bank_transfer, 2=cash, 3=paypal',
  pay_date_execution DATETIME NULL COMMENT 'Payment execution date',
  proposed_date DATETIME NULL COMMENT 'Proposed delivery date',
  ship_date DATETIME NULL COMMENT 'Actual shipping date',
  tracking_number VARCHAR(100) NULL COMMENT 'Shipping tracking number',
  manager_name VARCHAR(100) NULL COMMENT 'Assigned manager name',
  manager_email VARCHAR(100) NULL COMMENT 'Manager email',
  locale VARCHAR(10) NOT NULL DEFAULT 'en' COMMENT 'Customer locale',
  cur_rate DECIMAL(12,6) DEFAULT 1.000000 COMMENT 'Currency exchange rate',
  currency CHAR(3) NOT NULL DEFAULT 'EUR' COMMENT 'Order currency (ISO 4217)',
  measure VARCHAR(5) NOT NULL DEFAULT 'm' COMMENT 'Measurement unit: m, cm, mm',
  name VARCHAR(200) NOT NULL COMMENT 'Order name/title',
  description TEXT NULL COMMENT 'Order description/notes',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  warehouse_data JSON NULL COMMENT 'Warehouse-specific data',
  address_equal BOOLEAN DEFAULT TRUE COMMENT 'Delivery address equals billing address',
  accept_pay BOOLEAN DEFAULT FALSE COMMENT 'Payment accepted flag',
  weight_gross DECIMAL(12,3) NULL COMMENT 'Total weight in kg',
  payment_euro BOOLEAN DEFAULT FALSE COMMENT 'Payment in EUR flag',
  spec_price BOOLEAN DEFAULT FALSE COMMENT 'Special pricing flag',
  delivery_apartment_office VARCHAR(30) NULL COMMENT 'Apartment/office number',
  
  -- Indexes
  UNIQUE KEY uniq_hash (hash),
  UNIQUE KEY uniq_uuid (uuid),
  INDEX IDX_user_id (user_id),
  INDEX IDX_created_at (created_at),
  INDEX IDX_status (status),
  INDEX IDX_email (email),
  INDEX IDX_delivery_country (delivery_country),
  INDEX IDX_currency (currency),
  INDEX IDX_pay_type (pay_type),
  INDEX IDX_client_name (client_name, client_surname),
  INDEX IDX_company_name (company_name),
  INDEX IDX_number (number),
  INDEX IDX_tracking_number (tracking_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Orders table with improved structure';

-- Create orders_article table for order items
CREATE TABLE orders_article (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to orders table',
  article_id INT NOT NULL COMMENT 'Article/product ID',
  article_code VARCHAR(100) NULL COMMENT 'Article code/SKU',
  article_name VARCHAR(255) NULL COMMENT 'Article name',
  amount DECIMAL(12,4) NOT NULL COMMENT 'Quantity ordered',
  price DECIMAL(12,2) NOT NULL COMMENT 'Unit price in order currency',
  price_eur DECIMAL(12,2) NULL COMMENT 'Unit price in EUR',
  currency CHAR(3) NULL COMMENT 'Price currency',
  measure VARCHAR(5) NULL COMMENT 'Measurement unit for this item',
  delivery_time_min DATE NULL COMMENT 'Earliest delivery date',
  delivery_time_max DATE NULL COMMENT 'Latest delivery date',
  weight DECIMAL(12,3) NULL COMMENT 'Item weight in kg',
  packaging_count DECIMAL(12,4) NULL COMMENT 'Packaging units',
  pallet DECIMAL(12,4) NULL COMMENT 'Pallets needed',
  packaging DECIMAL(12,4) NULL COMMENT 'Packaging units',
  swimming_pool BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Swimming pool related item',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  
  -- Indexes and constraints
  INDEX IDX_article_id (article_id),
  INDEX IDX_article_code (article_code),
  INDEX IDX_order_id (order_id),
  INDEX IDX_delivery_time (delivery_time_min, delivery_time_max),
  INDEX IDX_currency (currency),
  
  -- Foreign key constraint
  CONSTRAINT FK_orders_article_order 
    FOREIGN KEY (order_id) 
    REFERENCES orders (id) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Order items/articles table';

-- Create indexes for better performance on common queries
CREATE INDEX IDX_orders_composite_search ON orders (client_name, client_surname, email, company_name, number);
CREATE INDEX IDX_orders_date_range ON orders (created_at, status);
CREATE INDEX IDX_orders_aggregation ON orders (created_at, status, currency);

-- Insert sample data for testing
INSERT INTO orders (
  uuid, hash, token, number, status, email, client_name, client_surname, 
  company_name, currency, name, description, created_at
) VALUES 
(
  UUID(), SHA2(CONCAT('order_', UNIX_TIMESTAMP(), '_1'), 256), 
  SUBSTRING(MD5(RAND()), 1, 32), 'ORD-2025-001', 1, 'john.doe@example.com', 
  'John', 'Doe', 'Example Corp', 'EUR', 'Sample Order 1', 'Test order for development', 
  '2025-10-01 10:00:00'
),
(
  UUID(), SHA2(CONCAT('order_', UNIX_TIMESTAMP(), '_2'), 256), 
  SUBSTRING(MD5(RAND()), 1, 32), 'ORD-2025-002', 2, 'jane.smith@example.com', 
  'Jane', 'Smith', 'Test Company', 'EUR', 'Sample Order 2', 'Another test order', 
  '2025-10-02 14:30:00'
),
(
  UUID(), SHA2(CONCAT('order_', UNIX_TIMESTAMP(), '_3'), 256), 
  SUBSTRING(MD5(RAND()), 1, 32), 'ORD-2025-003', 3, 'bob.wilson@example.com', 
  'Bob', 'Wilson', NULL, 'USD', 'Sample Order 3', 'Individual customer order', 
  '2025-09-15 09:15:00'
);

-- Insert sample order articles
INSERT INTO orders_article (
  order_id, article_id, article_code, article_name, amount, price, price_eur, 
  currency, measure, delivery_time_min, delivery_time_max, weight
) VALUES 
(1, 1001, 'TILE-001', 'Premium Ceramic Tile', 50.0000, 25.99, 25.99, 'EUR', 'm2', '2025-10-10', '2025-10-15', 125.500),
(1, 1002, 'TILE-002', 'Bathroom Mosaic', 10.0000, 45.50, 45.50, 'EUR', 'm2', '2025-10-12', '2025-10-18', 25.000),
(2, 1003, 'TILE-003', 'Kitchen Backsplash', 20.0000, 32.75, 32.75, 'EUR', 'm2', '2025-10-08', '2025-10-12', 60.000),
(3, 1004, 'TILE-004', 'Outdoor Pavement', 100.0000, 18.99, 17.25, 'USD', 'm2', '2025-09-20', '2025-09-25', 300.000);
