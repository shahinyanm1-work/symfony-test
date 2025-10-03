<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251003061455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE orders (id BIGINT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, hash VARCHAR(64) NOT NULL, user_id BIGINT DEFAULT NULL, token VARCHAR(64) NOT NULL, number VARCHAR(50) DEFAULT NULL, status SMALLINT NOT NULL, email VARCHAR(150) DEFAULT NULL, vat_type SMALLINT NOT NULL, vat_number VARCHAR(64) DEFAULT NULL, discount SMALLINT DEFAULT NULL, delivery_price NUMERIC(12, 2) DEFAULT NULL, delivery_type SMALLINT NOT NULL, delivery_index VARCHAR(20) DEFAULT NULL, delivery_country INT DEFAULT NULL, delivery_region VARCHAR(100) DEFAULT NULL, delivery_city VARCHAR(200) DEFAULT NULL, delivery_address VARCHAR(300) DEFAULT NULL, delivery_phone VARCHAR(50) DEFAULT NULL, client_name VARCHAR(150) DEFAULT NULL, client_surname VARCHAR(150) DEFAULT NULL, company_name VARCHAR(255) DEFAULT NULL, pay_type SMALLINT NOT NULL, pay_date_execution DATETIME DEFAULT NULL, proposed_date DATETIME DEFAULT NULL, ship_date DATETIME DEFAULT NULL, tracking_number VARCHAR(100) DEFAULT NULL, manager_name VARCHAR(100) DEFAULT NULL, manager_email VARCHAR(100) DEFAULT NULL, locale VARCHAR(10) NOT NULL, cur_rate NUMERIC(12, 6) NOT NULL, currency VARCHAR(3) NOT NULL, measure VARCHAR(5) NOT NULL, name VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, warehouse_data JSON DEFAULT NULL, address_equal TINYINT(1) NOT NULL, accept_pay TINYINT(1) NOT NULL, weight_gross NUMERIC(12, 3) DEFAULT NULL, payment_euro TINYINT(1) NOT NULL, spec_price TINYINT(1) NOT NULL, delivery_apartment_office VARCHAR(30) DEFAULT NULL, INDEX IDX_created_at (created_at), INDEX IDX_status (status), INDEX IDX_email (email), INDEX IDX_currency (currency), INDEX IDX_client_name (client_name, client_surname), INDEX IDX_company_name (company_name), INDEX IDX_number (number), UNIQUE INDEX uniq_hash (hash), UNIQUE INDEX uniq_uuid (uuid), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders_article (id BIGINT AUTO_INCREMENT NOT NULL, article_id INT NOT NULL, article_code VARCHAR(100) DEFAULT NULL, article_name VARCHAR(255) DEFAULT NULL, amount NUMERIC(12, 4) NOT NULL, price NUMERIC(12, 2) NOT NULL, price_eur NUMERIC(12, 2) DEFAULT NULL, currency VARCHAR(3) DEFAULT NULL, measure VARCHAR(5) DEFAULT NULL, delivery_time_min DATE DEFAULT NULL, delivery_time_max DATE DEFAULT NULL, weight NUMERIC(12, 3) DEFAULT NULL, packaging_count NUMERIC(12, 4) DEFAULT NULL, pallet NUMERIC(12, 4) DEFAULT NULL, packaging NUMERIC(12, 4) DEFAULT NULL, swimming_pool TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, order_id BIGINT NOT NULL, INDEX IDX_article_id (article_id), INDEX IDX_article_code (article_code), INDEX IDX_order_id (order_id), INDEX IDX_delivery_time (delivery_time_min, delivery_time_max), INDEX IDX_currency (currency), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE orders_article ADD CONSTRAINT FK_F34F7C1D8D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders_article DROP FOREIGN KEY FK_F34F7C1D8D9F6D38');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE orders_article');
    }
}
