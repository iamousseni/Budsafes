<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200206224819 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, budget_id INT NOT NULL, type VARCHAR(1) NOT NULL, name VARCHAR(255) NOT NULL, status INT NOT NULL, INDEX IDX_64C19C136ABA6B8 (budget_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE business_traffic (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, currency_id INT NOT NULL, budget_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, description LONGTEXT DEFAULT NULL, added DATETIME NOT NULL, INDEX IDX_8539D7D12469DE2 (category_id), INDEX IDX_8539D7D38248176 (currency_id), INDEX IDX_8539D7D36ABA6B8 (budget_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, surname VARCHAR(255) NOT NULL, image_profile VARCHAR(500) DEFAULT NULL, work VARCHAR(255) DEFAULT NULL, birthday DATE NOT NULL, status INT NOT NULL, gender VARCHAR(255) NOT NULL, created DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE schedule (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, currency_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, first_invoice DATE NOT NULL, cycle INT NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_5A3811FB12469DE2 (category_id), INDEX IDX_5A3811FB38248176 (currency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE currency (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(45) NOT NULL, symbol VARCHAR(10) NOT NULL, value DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE budget (id INT AUTO_INCREMENT NOT NULL, schedule_id INT DEFAULT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, active INT NOT NULL, INDEX IDX_73F2F77BA40BC2D5 (schedule_id), INDEX IDX_73F2F77BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C136ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id)');
        $this->addSql('ALTER TABLE business_traffic ADD CONSTRAINT FK_8539D7D12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE business_traffic ADD CONSTRAINT FK_8539D7D38248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE business_traffic ADD CONSTRAINT FK_8539D7D36ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id)');
        $this->addSql('ALTER TABLE schedule ADD CONSTRAINT FK_5A3811FB12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE schedule ADD CONSTRAINT FK_5A3811FB38248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE budget ADD CONSTRAINT FK_73F2F77BA40BC2D5 FOREIGN KEY (schedule_id) REFERENCES schedule (id)');
        $this->addSql('ALTER TABLE budget ADD CONSTRAINT FK_73F2F77BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE business_traffic DROP FOREIGN KEY FK_8539D7D12469DE2');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FB12469DE2');
        $this->addSql('ALTER TABLE budget DROP FOREIGN KEY FK_73F2F77BA76ED395');
        $this->addSql('ALTER TABLE budget DROP FOREIGN KEY FK_73F2F77BA40BC2D5');
        $this->addSql('ALTER TABLE business_traffic DROP FOREIGN KEY FK_8539D7D38248176');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FB38248176');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C136ABA6B8');
        $this->addSql('ALTER TABLE business_traffic DROP FOREIGN KEY FK_8539D7D36ABA6B8');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE business_traffic');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE schedule');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE budget');
    }
}
