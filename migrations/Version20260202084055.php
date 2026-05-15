<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202084055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competence ADD domaine VARCHAR(255) DEFAULT NULL, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE categorie type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE selection_competence DROP FOREIGN KEY `FK_8C1BFF6415761DAB`');
        $this->addSql('DROP INDEX IDX_8C1BFF6415761DAB ON selection_competence');
        $this->addSql('ALTER TABLE selection_competence CHANGE competence_id domaine_id INT NOT NULL');
        $this->addSql('ALTER TABLE selection_competence ADD CONSTRAINT FK_8C1BFF644272FC9F FOREIGN KEY (domaine_id) REFERENCES competence (id)');
        $this->addSql('CREATE INDEX IDX_8C1BFF644272FC9F ON selection_competence (domaine_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competence DROP domaine, CHANGE nom nom VARCHAR(45) NOT NULL, CHANGE type categorie VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE selection_competence DROP FOREIGN KEY FK_8C1BFF644272FC9F');
        $this->addSql('DROP INDEX IDX_8C1BFF644272FC9F ON selection_competence');
        $this->addSql('ALTER TABLE selection_competence CHANGE domaine_id competence_id INT NOT NULL');
        $this->addSql('ALTER TABLE selection_competence ADD CONSTRAINT `FK_8C1BFF6415761DAB` FOREIGN KEY (competence_id) REFERENCES competence (id)');
        $this->addSql('CREATE INDEX IDX_8C1BFF6415761DAB ON selection_competence (competence_id)');
    }
}
