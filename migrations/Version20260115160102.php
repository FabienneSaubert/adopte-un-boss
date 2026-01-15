<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115160102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE candidat (id INT AUTO_INCREMENT NOT NULL, profil_visible TINYINT NOT NULL, infos_visibles TINYINT NOT NULL, uuid VARCHAR(30) NOT NULL, cv VARCHAR(150) DEFAULT NULL, niveau_etude VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE candidature (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT DEFAULT NULL, date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE competence (id INT AUTO_INCREMENT NOT NULL, categorie VARCHAR(255) NOT NULL, nom VARCHAR(45) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE demande_contact (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(45) NOT NULL, prenom VARCHAR(45) NOT NULL, email VARCHAR(100) NOT NULL, sujet VARCHAR(100) NOT NULL, message LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, statut_demande VARCHAR(255) DEFAULT \'En attente\' NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE departement (id INT AUTO_INCREMENT NOT NULL, numero SMALLINT NOT NULL, nom VARCHAR(60) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entreprise (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(60) NOT NULL, siret VARCHAR(14) NOT NULL, adresse VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offre (id INT AUTO_INCREMENT NOT NULL, categorie_offre VARCHAR(255) NOT NULL, intitule VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, salaire INT DEFAULT NULL, niveau_etudes VARCHAR(255) NOT NULL, coeff_niveau_etudes SMALLINT NOT NULL, teletravail_possible TINYINT NOT NULL, coeff_departement SMALLINT NOT NULL, date_de_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, statut_offre VARCHAR(255) DEFAULT \'En attente\' NOT NULL, nombre_de_vues INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recruteur (id INT AUTO_INCREMENT NOT NULL, poste VARCHAR(100) NOT NULL, email_pro VARCHAR(100) DEFAULT NULL, telephone_pro VARCHAR(12) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE selection_competence (id INT AUTO_INCREMENT NOT NULL, coeff_competence SMALLINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) NOT NULL, nom VARCHAR(45) NOT NULL, prenom VARCHAR(45) NOT NULL, email VARCHAR(100) NOT NULL, date_de_naissance DATE DEFAULT NULL, telephone VARCHAR(12) DEFAULT NULL, mdp_hash VARCHAR(60) NOT NULL, statut_inscription VARCHAR(255) DEFAULT \'En attente\' NOT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE candidat');
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
