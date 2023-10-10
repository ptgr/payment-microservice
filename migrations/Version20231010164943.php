<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231010164943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE item_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE method_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE payment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE token_item_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE item (id INT NOT NULL, external_id INT NOT NULL, name VARCHAR(100) NOT NULL, quantity SMALLINT NOT NULL, price INT NOT NULL, vat SMALLINT NOT NULL, shipping INT NOT NULL, discount SMALLINT NOT NULL, total_price INT NOT NULL, currency_code VARCHAR(3) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1F1B251E9F75D7B0 ON item (external_id)');
        $this->addSql('COMMENT ON COLUMN item.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE method (id INT NOT NULL, internal_key VARCHAR(20) NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE payment (id INT NOT NULL, token_id VARCHAR(50) NOT NULL, amount INT NOT NULL, transaction_number VARCHAR(50) NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6D28840D41DEE7B9 ON payment (token_id)');
        $this->addSql('COMMENT ON COLUMN payment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN payment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE token (id VARCHAR(50) NOT NULL, method_id INT NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5F37A13B19883967 ON token (method_id)');
        $this->addSql('COMMENT ON COLUMN token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE token_item (id INT NOT NULL, token_id VARCHAR(50) NOT NULL, item_id INT NOT NULL, transaction_name VARCHAR(60) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7D1F107C41DEE7B9 ON token_item (token_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7D1F107C126F525E ON token_item (item_id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D41DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE token ADD CONSTRAINT FK_5F37A13B19883967 FOREIGN KEY (method_id) REFERENCES method (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE token_item ADD CONSTRAINT FK_7D1F107C41DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE token_item ADD CONSTRAINT FK_7D1F107C126F525E FOREIGN KEY (item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE item_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE method_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE payment_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE token_item_id_seq CASCADE');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840D41DEE7B9');
        $this->addSql('ALTER TABLE token DROP CONSTRAINT FK_5F37A13B19883967');
        $this->addSql('ALTER TABLE token_item DROP CONSTRAINT FK_7D1F107C41DEE7B9');
        $this->addSql('ALTER TABLE token_item DROP CONSTRAINT FK_7D1F107C126F525E');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE method');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE token');
        $this->addSql('DROP TABLE token_item');
    }
}
