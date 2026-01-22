<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260122134724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE candidat (id INT AUTO_INCREMENT NOT NULL, profil_visible TINYINT NOT NULL, infos_visibles TINYINT NOT NULL, uuid VARCHAR(30) NOT NULL, cv VARCHAR(150) DEFAULT NULL, niveau_etude VARCHAR(255) NOT NULL, utilisateur_id INT NOT NULL, departement_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6AB5B471FB88E14F (utilisateur_id), INDEX IDX_6AB5B471CCF9E01E (departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE candidat_competence (candidat_id INT NOT NULL, competence_id INT NOT NULL, INDEX IDX_CF607D68D0EB82 (candidat_id), INDEX IDX_CF607D615761DAB (competence_id), PRIMARY KEY (candidat_id, competence_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE candidature (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT DEFAULT NULL, date_envoi DATETIME NOT NULL, candidat_id INT NOT NULL, offre_id INT NOT NULL, INDEX IDX_E33BD3B88D0EB82 (candidat_id), INDEX IDX_E33BD3B84CC8505A (offre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE competence (id INT AUTO_INCREMENT NOT NULL, categorie VARCHAR(255) NOT NULL, nom VARCHAR(45) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE demande_contact (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(45) NOT NULL, prenom VARCHAR(45) NOT NULL, email VARCHAR(100) NOT NULL, sujet VARCHAR(100) NOT NULL, message LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL, statut_demande VARCHAR(255) DEFAULT \'En attente\' NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE departement (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(3) NOT NULL, nom VARCHAR(60) NOT NULL, UNIQUE INDEX UNIQ_C1765B63F55AE19E (numero), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entreprise (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(60) NOT NULL, siret VARCHAR(14) NOT NULL, adresse VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offre (id INT AUTO_INCREMENT NOT NULL, categorie_offre VARCHAR(255) NOT NULL, intitule VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, salaire INT DEFAULT NULL, niveau_etudes VARCHAR(255) NOT NULL, coeff_niveau_etudes SMALLINT NOT NULL, teletravail_possible TINYINT NOT NULL, coeff_departement SMALLINT NOT NULL, date_de_publication DATETIME NOT NULL, statut_offre VARCHAR(255) DEFAULT \'En attente\' NOT NULL, nombre_de_vues INT NOT NULL, recruteur_id INT NOT NULL, departement_id INT NOT NULL, INDEX IDX_AF86866FBB0859F1 (recruteur_id), INDEX IDX_AF86866FCCF9E01E (departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recruteur (id INT AUTO_INCREMENT NOT NULL, poste VARCHAR(100) NOT NULL, email_pro VARCHAR(100) DEFAULT NULL, telephone_pro VARCHAR(12) DEFAULT NULL, utilisateur_id INT NOT NULL, entreprise_id INT NOT NULL, UNIQUE INDEX UNIQ_2BD3678CFB88E14F (utilisateur_id), INDEX IDX_2BD3678CA4AEAFEA (entreprise_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE selection_competence (id INT AUTO_INCREMENT NOT NULL, coeff_competence SMALLINT NOT NULL, offre_id INT NOT NULL, competence_id INT NOT NULL, INDEX IDX_8C1BFF644CC8505A (offre_id), INDEX IDX_8C1BFF6415761DAB (competence_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) NOT NULL, nom VARCHAR(45) NOT NULL, prenom VARCHAR(45) NOT NULL, email VARCHAR(100) NOT NULL, date_de_naissance DATE DEFAULT NULL, telephone VARCHAR(12) DEFAULT NULL, mdp_hash VARCHAR(60) NOT NULL, statut_inscription VARCHAR(255) DEFAULT \'En attente\' NOT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE candidat ADD CONSTRAINT FK_6AB5B471FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE candidat ADD CONSTRAINT FK_6AB5B471CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE candidat_competence ADD CONSTRAINT FK_CF607D68D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidat_competence ADD CONSTRAINT FK_CF607D615761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B88D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B84CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866FBB0859F1 FOREIGN KEY (recruteur_id) REFERENCES recruteur (id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866FCCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE recruteur ADD CONSTRAINT FK_2BD3678CFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE recruteur ADD CONSTRAINT FK_2BD3678CA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE selection_competence ADD CONSTRAINT FK_8C1BFF644CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id)');
        $this->addSql('ALTER TABLE selection_competence ADD CONSTRAINT FK_8C1BFF6415761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidat DROP FOREIGN KEY FK_6AB5B471FB88E14F');
        $this->addSql('ALTER TABLE candidat DROP FOREIGN KEY FK_6AB5B471CCF9E01E');
        $this->addSql('ALTER TABLE candidat_competence DROP FOREIGN KEY FK_CF607D68D0EB82');
        $this->addSql('ALTER TABLE candidat_competence DROP FOREIGN KEY FK_CF607D615761DAB');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B88D0EB82');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B84CC8505A');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866FBB0859F1');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866FCCF9E01E');
        $this->addSql('ALTER TABLE recruteur DROP FOREIGN KEY FK_2BD3678CFB88E14F');
        $this->addSql('ALTER TABLE recruteur DROP FOREIGN KEY FK_2BD3678CA4AEAFEA');
        $this->addSql('ALTER TABLE selection_competence DROP FOREIGN KEY FK_8C1BFF644CC8505A');
        $this->addSql('ALTER TABLE selection_competence DROP FOREIGN KEY FK_8C1BFF6415761DAB');
        $this->addSql('DROP TABLE candidat');
        $this->addSql('DROP TABLE candidat_competence');
        $this->addSql('DROP TABLE candidature');
        $this->addSql('DROP TABLE competence');
        $this->addSql('DROP TABLE demande_contact');
        $this->addSql('DROP TABLE departement');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE recruteur');
        $this->addSql('DROP TABLE selection_competence');
        $this->addSql('DROP TABLE utilisateur');
    }
}
