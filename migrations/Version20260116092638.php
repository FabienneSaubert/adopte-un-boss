<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116092638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE candidat_competence (candidat_id INT NOT NULL, competence_id INT NOT NULL, INDEX IDX_CF607D68D0EB82 (candidat_id), INDEX IDX_CF607D615761DAB (competence_id), PRIMARY KEY (candidat_id, competence_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE candidat_competence ADD CONSTRAINT FK_CF607D68D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidat_competence ADD CONSTRAINT FK_CF607D615761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidat ADD utilisateur_id INT NOT NULL, ADD departement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE candidat ADD CONSTRAINT FK_6AB5B471FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE candidat ADD CONSTRAINT FK_6AB5B471CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6AB5B471FB88E14F ON candidat (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_6AB5B471CCF9E01E ON candidat (departement_id)');
        $this->addSql('ALTER TABLE candidature ADD candidat_id INT NOT NULL, ADD offre_id INT NOT NULL, CHANGE date_envoi date_envoi DATETIME NOT NULL');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B88D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B84CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id)');
        $this->addSql('CREATE INDEX IDX_E33BD3B88D0EB82 ON candidature (candidat_id)');
        $this->addSql('CREATE INDEX IDX_E33BD3B84CC8505A ON candidature (offre_id)');
        $this->addSql('ALTER TABLE demande_contact CHANGE date_envoi date_envoi DATETIME NOT NULL');
        $this->addSql('ALTER TABLE offre ADD recruteur_id INT NOT NULL, ADD departement_id INT NOT NULL, CHANGE date_de_publication date_de_publication DATETIME NOT NULL');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866FBB0859F1 FOREIGN KEY (recruteur_id) REFERENCES recruteur (id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866FCCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('CREATE INDEX IDX_AF86866FBB0859F1 ON offre (recruteur_id)');
        $this->addSql('CREATE INDEX IDX_AF86866FCCF9E01E ON offre (departement_id)');
        $this->addSql('ALTER TABLE recruteur ADD utilisateur_id INT NOT NULL, ADD entreprise_id INT NOT NULL');
        $this->addSql('ALTER TABLE recruteur ADD CONSTRAINT FK_2BD3678CFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE recruteur ADD CONSTRAINT FK_2BD3678CA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2BD3678CFB88E14F ON recruteur (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_2BD3678CA4AEAFEA ON recruteur (entreprise_id)');
        $this->addSql('ALTER TABLE selection_competence ADD offre_id INT NOT NULL, ADD competence_id INT NOT NULL');
        $this->addSql('ALTER TABLE selection_competence ADD CONSTRAINT FK_8C1BFF644CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id)');
        $this->addSql('ALTER TABLE selection_competence ADD CONSTRAINT FK_8C1BFF6415761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)');
        $this->addSql('CREATE INDEX IDX_8C1BFF644CC8505A ON selection_competence (offre_id)');
        $this->addSql('CREATE INDEX IDX_8C1BFF6415761DAB ON selection_competence (competence_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidat_competence DROP FOREIGN KEY FK_CF607D68D0EB82');
        $this->addSql('ALTER TABLE candidat_competence DROP FOREIGN KEY FK_CF607D615761DAB');
        $this->addSql('DROP TABLE candidat_competence');
        $this->addSql('ALTER TABLE candidat DROP FOREIGN KEY FK_6AB5B471FB88E14F');
        $this->addSql('ALTER TABLE candidat DROP FOREIGN KEY FK_6AB5B471CCF9E01E');
        $this->addSql('DROP INDEX UNIQ_6AB5B471FB88E14F ON candidat');
        $this->addSql('DROP INDEX IDX_6AB5B471CCF9E01E ON candidat');
        $this->addSql('ALTER TABLE candidat DROP utilisateur_id, DROP departement_id');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B88D0EB82');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B84CC8505A');
        $this->addSql('DROP INDEX IDX_E33BD3B88D0EB82 ON candidature');
        $this->addSql('DROP INDEX IDX_E33BD3B84CC8505A ON candidature');
        $this->addSql('ALTER TABLE candidature DROP candidat_id, DROP offre_id, CHANGE date_envoi date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE demande_contact CHANGE date_envoi date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866FBB0859F1');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866FCCF9E01E');
        $this->addSql('DROP INDEX IDX_AF86866FBB0859F1 ON offre');
        $this->addSql('DROP INDEX IDX_AF86866FCCF9E01E ON offre');
        $this->addSql('ALTER TABLE offre DROP recruteur_id, DROP departement_id, CHANGE date_de_publication date_de_publication DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE recruteur DROP FOREIGN KEY FK_2BD3678CFB88E14F');
        $this->addSql('ALTER TABLE recruteur DROP FOREIGN KEY FK_2BD3678CA4AEAFEA');
        $this->addSql('DROP INDEX UNIQ_2BD3678CFB88E14F ON recruteur');
        $this->addSql('DROP INDEX IDX_2BD3678CA4AEAFEA ON recruteur');
        $this->addSql('ALTER TABLE recruteur DROP utilisateur_id, DROP entreprise_id');
        $this->addSql('ALTER TABLE selection_competence DROP FOREIGN KEY FK_8C1BFF644CC8505A');
        $this->addSql('ALTER TABLE selection_competence DROP FOREIGN KEY FK_8C1BFF6415761DAB');
        $this->addSql('DROP INDEX IDX_8C1BFF644CC8505A ON selection_competence');
        $this->addSql('DROP INDEX IDX_8C1BFF6415761DAB ON selection_competence');
        $this->addSql('ALTER TABLE selection_competence DROP offre_id, DROP competence_id');
    }
}
