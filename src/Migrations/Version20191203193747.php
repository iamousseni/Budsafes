<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191203193747 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE business_traffic ADD budget_id INT NOT NULL');
        $this->addSql('ALTER TABLE business_traffic ADD CONSTRAINT FK_8539D7D36ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id)');
        $this->addSql('CREATE INDEX IDX_8539D7D36ABA6B8 ON business_traffic (budget_id)');
        $this->addSql('ALTER TABLE budget CHANGE active active INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE budget CHANGE active active INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE business_traffic DROP FOREIGN KEY FK_8539D7D36ABA6B8');
        $this->addSql('DROP INDEX IDX_8539D7D36ABA6B8 ON business_traffic');
        $this->addSql('ALTER TABLE business_traffic DROP budget_id');
    }
}
