<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202084520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
           // 9. INSERTION DES NOUVELLES DONNÉES
        
        // SAVOIR-FAIRE - INFORMATIQUE/NUMERIQUE
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Informatique / Numérique', 'Programmation (Python, Java, JavaScript, C++, etc.)'),
            ('Savoir-faire', 'Informatique / Numérique', 'Développement web (HTML, CSS, React, Angular, etc.)'),
            ('Savoir-faire', 'Informatique / Numérique', 'Bases de données (SQL, NoSQL, MongoDB, etc.)'),
            ('Savoir-faire', 'Informatique / Numérique', 'Administration systèmes (Linux, Windows Server)'),
            ('Savoir-faire', 'Informatique / Numérique', 'Réseaux et sécurité informatique'),
            ('Savoir-faire', 'Informatique / Numérique', 'Cloud computing (AWS, Azure, Google Cloud)'),
            ('Savoir-faire', 'Informatique / Numérique', 'DevOps et CI/CD'),
            ('Savoir-faire', 'Informatique / Numérique', 'Gestion de projet (Agile, Scrum)'),
            ('Savoir-faire', 'Informatique / Numérique', 'Cybersécurité'),
            ('Savoir-faire', 'Informatique / Numérique', 'Analyse de données et BI'),
            ('Savoir-faire', 'Informatique / Numérique', 'Support et maintenance informatique')
        ");

        // SAVOIR-FAIRE - BÂTIMENT
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Bâtiment', 'Lecture de plans et schémas techniques'),
            ('Savoir-faire', 'Bâtiment', 'Maçonnerie et gros œuvre'),
            ('Savoir-faire', 'Bâtiment', 'Plâtrerie et finitions'),
            ('Savoir-faire', 'Bâtiment', 'Menuiserie et charpente'),
            ('Savoir-faire', 'Bâtiment', 'Plomberie et sanitaire'),
            ('Savoir-faire', 'Bâtiment', 'Électricité bâtiment'),
            ('Savoir-faire', 'Bâtiment', 'Peinture et revêtements'),
            ('Savoir-faire', 'Bâtiment', 'Isolation thermique et phonique'),
            ('Savoir-faire', 'Bâtiment', 'Couverture et étanchéité'),
            ('Savoir-faire', 'Bâtiment', 'Connaissance des normes de construction'),
            ('Savoir-faire', 'Bâtiment', 'Utilisation d\\'outils et machines de chantier')
        ");

        // SAVOIR-FAIRE - COMMERCE/VENTE
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Commerce / Vente', 'Techniques de vente et négociation'),
            ('Savoir-faire', 'Commerce / Vente', 'Prospection commerciale'),
            ('Savoir-faire', 'Commerce / Vente', 'Gestion de la relation client (CRM)'),
            ('Savoir-faire', 'Commerce / Vente', 'Élaboration de propositions commerciales'),
            ('Savoir-faire', 'Commerce / Vente', 'Analyse du marché et de la concurrence'),
            ('Savoir-faire', 'Commerce / Vente', 'Merchandising et PLV'),
            ('Savoir-faire', 'Commerce / Vente', 'Gestion des stocks'),
            ('Savoir-faire', 'Commerce / Vente', 'E-commerce et marketing digital'),
            ('Savoir-faire', 'Commerce / Vente', 'Reporting et analyse des ventes'),
            ('Savoir-faire', 'Commerce / Vente', 'Connaissance des produits/services'),
            ('Savoir-faire', 'Commerce / Vente', 'Techniques de closing')
        ");

        // SAVOIR-FAIRE - ENSEIGNEMENT
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Enseignement', 'Maîtrise de la discipline enseignée'),
            ('Savoir-faire', 'Enseignement', 'Pédagogie et didactique'),
            ('Savoir-faire', 'Enseignement', 'Conception de programmes et cours'),
            ('Savoir-faire', 'Enseignement', 'Évaluation et notation'),
            ('Savoir-faire', 'Enseignement', 'Gestion de classe'),
            ('Savoir-faire', 'Enseignement', 'Utilisation d\\'outils numériques éducatifs'),
            ('Savoir-faire', 'Enseignement', 'Adaptation pédagogique (différenciation)'),
            ('Savoir-faire', 'Enseignement', 'Techniques d\\'animation de groupe'),
            ('Savoir-faire', 'Enseignement', 'Suivi et accompagnement des élèves'),
            ('Savoir-faire', 'Enseignement', 'Connaissance des programmes officiels'),
            ('Savoir-faire', 'Enseignement', 'Recherche documentaire')
        ");

        // SAVOIR-FAIRE - INDUSTRIE
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Industrie', 'Conduite de machines industrielles'),
            ('Savoir-faire', 'Industrie', 'Lecture de plans techniques'),
            ('Savoir-faire', 'Industrie', 'Contrôle qualité et métrologie'),
            ('Savoir-faire', 'Industrie', 'Maintenance préventive et curative'),
            ('Savoir-faire', 'Industrie', 'Programmation (automates, CNC)'),
            ('Savoir-faire', 'Industrie', 'Usinage et fabrication'),
            ('Savoir-faire', 'Industrie', 'Soudure et assemblage'),
            ('Savoir-faire', 'Industrie', 'Connaissance des normes ISO'),
            ('Savoir-faire', 'Industrie', 'Lean manufacturing et amélioration continue'),
            ('Savoir-faire', 'Industrie', 'Gestion de production'),
            ('Savoir-faire', 'Industrie', 'Respect des procédures de sécurité')
        ");

        // SAVOIR-FAIRE - HÔTELLERIE/RESTAURATION/TOURISME
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Techniques culinaires et de cuisson'),
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Préparation et dressage'),
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Connaissance des produits alimentaires'),
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Hygiène alimentaire (HACCP)'),
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Gestion des stocks et inventaires'),
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Service en salle'),
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Œnologie et accords mets-vins'),
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Pâtisserie et desserts'),
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Gestion des allergies alimentaires'),
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Création de menus'),
            ('Savoir-faire', 'Hôtellerie / Restauration / Tourisme', 'Utilisation des équipements de cuisine')
        ");

        // SAVOIR-FAIRE - SANTÉ/SOCIAL
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Santé / Social', 'Connaissance de l\\'anatomie et physiologie'),
            ('Savoir-faire', 'Santé / Social', 'Soins infirmiers et techniques médicales'),
            ('Savoir-faire', 'Santé / Social', 'Diagnostics et examens médicaux'),
            ('Savoir-faire', 'Santé / Social', 'Gestion des dossiers patients'),
            ('Savoir-faire', 'Santé / Social', 'Pharmacologie et prescriptions'),
            ('Savoir-faire', 'Santé / Social', 'Hygiène et asepsie'),
            ('Savoir-faire', 'Santé / Social', 'Premiers secours et urgences'),
            ('Savoir-faire', 'Santé / Social', 'Manipulation d\\'équipements médicaux'),
            ('Savoir-faire', 'Santé / Social', 'Protocoles de soins'),
            ('Savoir-faire', 'Santé / Social', 'Gestion administrative médicale'),
            ('Savoir-faire', 'Santé / Social', 'Connaissance de la réglementation sanitaire')
        ");

        // SAVOIR-FAIRE - LOGISTIQUE/TRANSPORT
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Logistique / Transport', 'Conduite de véhicules (permis adaptés)'),
            ('Savoir-faire', 'Logistique / Transport', 'Gestion des flux logistiques'),
            ('Savoir-faire', 'Logistique / Transport', 'Utilisation de logiciels de gestion (WMS, TMS)'),
            ('Savoir-faire', 'Logistique / Transport', 'Manutention et chargement'),
            ('Savoir-faire', 'Logistique / Transport', 'Gestion des stocks et entreposage'),
            ('Savoir-faire', 'Logistique / Transport', 'Préparation de commandes (picking)'),
            ('Savoir-faire', 'Logistique / Transport', 'Connaissance de la réglementation transport'),
            ('Savoir-faire', 'Logistique / Transport', 'Lecture de documents de transport'),
            ('Savoir-faire', 'Logistique / Transport', 'Techniques d\\'emballage'),
            ('Savoir-faire', 'Logistique / Transport', 'Conduite de chariots élévateurs (CACES)'),
            ('Savoir-faire', 'Logistique / Transport', 'Optimisation des tournées')
        ");

        // SAVOIR-FAIRE - RECHERCHE/SCIENCE
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Recherche / Science', 'Concevoir et formuler une problématique de recherche'),
            ('Savoir-faire', 'Recherche / Science', 'Réaliser des expériences ou études scientifiques'),
            ('Savoir-faire', 'Recherche / Science', 'Collecter, traiter et analyser des données'),
            ('Savoir-faire', 'Recherche / Science', 'Utiliser des outils statistiques et logiciels scientifiques'),
            ('Savoir-faire', 'Recherche / Science', 'Rédiger des rapports, articles ou publications'),
            ('Savoir-faire', 'Recherche / Science', 'Interpréter des résultats et en tirer des conclusions'),
            ('Savoir-faire', 'Recherche / Science', 'Mettre en place des protocoles et méthodologies'),
            ('Savoir-faire', 'Recherche / Science', 'Assurer une veille scientifique et technologique'),
            ('Savoir-faire', 'Recherche / Science', 'Présenter des résultats à l\\'oral (conférences, soutenances)'),
            ('Savoir-faire', 'Recherche / Science', 'Respecter des normes éthiques et scientifiques')
        ");

// SAVOIR-FAIRE - COMMUNICATION/MARKETING
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Communication / Marketing', 'Élaborer une stratégie de communication'),
            ('Savoir-faire', 'Communication / Marketing', 'Analyser un marché et définir des cibles'),
            ('Savoir-faire', 'Communication / Marketing', 'Créer des contenus (textes, visuels, vidéos)'),
            ('Savoir-faire', 'Communication / Marketing', 'Rédiger des messages publicitaires et éditoriaux'),
            ('Savoir-faire', 'Communication / Marketing', 'Gérer des réseaux sociaux et communautés en ligne'),
            ('Savoir-faire', 'Communication / Marketing', 'Concevoir et piloter des campagnes marketing'),
            ('Savoir-faire', 'Communication / Marketing', 'Analyser les performances (KPI, statistiques)'),
            ('Savoir-faire', 'Communication / Marketing', 'Mettre en œuvre des actions de marketing digital'),
            ('Savoir-faire', 'Communication / Marketing', 'Gérer l\\'image et la notoriété d\\'une marque'),
            ('Savoir-faire', 'Communication / Marketing', 'Organiser des événements ou actions promotionnelles')
        ");

        // SAVOIR-FAIRE - GESTION/FINANCE
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Gestion / Finance', 'Élaborer et suivre un budget'),
            ('Savoir-faire', 'Gestion / Finance', 'Analyser des données financières'),
            ('Savoir-faire', 'Gestion / Finance', 'Réaliser des prévisions et tableaux de bord'),
            ('Savoir-faire', 'Gestion / Finance', 'Gérer la trésorerie et les flux financiers'),
            ('Savoir-faire', 'Gestion / Finance', 'Contrôler les coûts et la rentabilité'),
            ('Savoir-faire', 'Gestion / Finance', 'Évaluer des investissements et projets'),
            ('Savoir-faire', 'Gestion / Finance', 'Utiliser des logiciels de gestion et comptabilité'),
            ('Savoir-faire', 'Gestion / Finance', 'Rédiger des rapports financiers'),
            ('Savoir-faire', 'Gestion / Finance', 'Assurer le suivi administratif et financier'),
            ('Savoir-faire', 'Gestion / Finance', 'Respecter les règles comptables et fiscales')
        ");

        // SAVOIR-FAIRE - RESSOURCES HUMAINES
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Ressources humaines', 'Recruter et sélectionner des candidats'),
            ('Savoir-faire', 'Ressources humaines', 'Rédiger des offres d\\'emploi et contrats'),
            ('Savoir-faire', 'Ressources humaines', 'Gérer les dossiers du personnel'),
            ('Savoir-faire', 'Ressources humaines', 'Organiser l\\'intégration des nouveaux salariés'),
            ('Savoir-faire', 'Ressources humaines', 'Mettre en place des plans de formation'),
            ('Savoir-faire', 'Ressources humaines', 'Évaluer les compétences et performances'),
            ('Savoir-faire', 'Ressources humaines', 'Gérer les relations sociales et le dialogue interne'),
            ('Savoir-faire', 'Ressources humaines', 'Appliquer le droit du travail'),
            ('Savoir-faire', 'Ressources humaines', 'Accompagner l\\'évolution professionnelle'),
            ('Savoir-faire', 'Ressources humaines', 'Mettre en œuvre des politiques RH')
        ");

        // SAVOIR-FAIRE - DROIT
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Droit', 'Analyser des textes juridiques et réglementaires'),
            ('Savoir-faire', 'Droit', 'Rédiger des contrats et actes juridiques'),
            ('Savoir-faire', 'Droit', 'Conseiller et informer juridiquement'),
            ('Savoir-faire', 'Droit', 'Assurer une veille juridique'),
            ('Savoir-faire', 'Droit', 'Constituer et suivre des dossiers juridiques'),
            ('Savoir-faire', 'Droit', 'Interpréter la jurisprudence'),
            ('Savoir-faire', 'Droit', 'Défendre des intérêts devant des instances'),
            ('Savoir-faire', 'Droit', 'Négocier des accords'),
            ('Savoir-faire', 'Droit', 'Appliquer et faire respecter le cadre légal'),
            ('Savoir-faire', 'Droit', 'Rédiger des notes et conclusions juridiques')
        ");

        // SAVOIR-FAIRE - DESIGN
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Design', 'Concevoir des concepts visuels'),
            ('Savoir-faire', 'Design', 'Réaliser des maquettes et prototypes'),
            ('Savoir-faire', 'Design', 'Utiliser des logiciels de création graphique'),
            ('Savoir-faire', 'Design', 'Créer des identités visuelles'),
            ('Savoir-faire', 'Design', 'Adapter un design aux besoins utilisateurs'),
            ('Savoir-faire', 'Design', 'Réaliser des supports print et digitaux'),
            ('Savoir-faire', 'Design', 'Tester et améliorer l\\'expérience utilisateur (UX)'),
            ('Savoir-faire', 'Design', 'Respecter une charte graphique'),
            ('Savoir-faire', 'Design', 'Collaborer avec des équipes techniques'),
            ('Savoir-faire', 'Design', 'Présenter et justifier des choix créatifs')
        ");

        // SAVOIR-FAIRE - AGRICULTURE
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Agriculture', 'Cultiver et entretenir des productions végétales'),
            ('Savoir-faire', 'Agriculture', 'Élever et soigner des animaux'),
            ('Savoir-faire', 'Agriculture', 'Planifier les cycles de production'),
            ('Savoir-faire', 'Agriculture', 'Utiliser et entretenir du matériel agricole'),
            ('Savoir-faire', 'Agriculture', 'Gérer les sols et les ressources naturelles'),
            ('Savoir-faire', 'Agriculture', 'Appliquer des techniques agricoles adaptées'),
            ('Savoir-faire', 'Agriculture', 'Respecter les normes sanitaires et environnementales'),
            ('Savoir-faire', 'Agriculture', 'Suivre les rendements et la qualité'),
            ('Savoir-faire', 'Agriculture', 'Gérer les stocks et récoltes'),
            ('Savoir-faire', 'Agriculture', 'Commercialiser les produits agricoles')
        ");

        // SAVOIR-FAIRE - ARTISANAT
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-faire', 'Artisanat', 'Fabriquer des produits artisanaux'),
            ('Savoir-faire', 'Artisanat', 'Maîtriser des techniques manuelles spécifiques'),
            ('Savoir-faire', 'Artisanat', 'Lire et interpréter des plans ou modèles'),
            ('Savoir-faire', 'Artisanat', 'Utiliser des outils et machines spécialisés'),
            ('Savoir-faire', 'Artisanat', 'Réaliser des finitions de qualité'),
            ('Savoir-faire', 'Artisanat', 'Adapter une création à la demande client'),
            ('Savoir-faire', 'Artisanat', 'Réparer, restaurer ou entretenir des objets'),
            ('Savoir-faire', 'Artisanat', 'Gérer un atelier ou espace de production'),
            ('Savoir-faire', 'Artisanat', 'Évaluer les coûts et délais de fabrication'),
            ('Savoir-faire', 'Artisanat', 'Assurer la vente et la relation client')
        ");



       // SAVOIR-ÊTRE (liste complète - 35 compétences)
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Savoir-être', NULL, 'Rigueur'),
            ('Savoir-être', NULL, 'Travail en équipe'),
            ('Savoir-être', NULL, 'Respect des consignes de sécurité'),
            ('Savoir-être', NULL, 'Ponctualité'),
            ('Savoir-être', NULL, 'Autonomie'),
            ('Savoir-être', NULL, 'Capacité d\\'adaptation'),
            ('Savoir-être', NULL, 'Sens de l\\'organisation'),
            ('Savoir-être', NULL, 'Résistance physique'),
            ('Savoir-être', NULL, 'Propreté et soin du lieu'),
            ('Savoir-être', NULL, 'Curiosité'),
            ('Savoir-être', NULL, 'Esprit analytique'),
            ('Savoir-être', NULL, 'Capacité à résoudre des problèmes'),
            ('Savoir-être', NULL, 'Autodidacte'),
            ('Savoir-être', NULL, 'Précision'),
            ('Savoir-être', NULL, 'Gestion du stress'),
            ('Savoir-être', NULL, 'Créativité'),
            ('Savoir-être', NULL, 'Communication'),
            ('Savoir-être', NULL, 'Sens du contact et de l\\'écoute'),
            ('Savoir-être', NULL, 'Persévérance'),
            ('Savoir-être', NULL, 'Dynamisme et énergie'),
            ('Savoir-être', NULL, 'Esprit de compétition'),
            ('Savoir-être', NULL, 'Empathie'),
            ('Savoir-être', NULL, 'Patience'),
            ('Savoir-être', NULL, 'Disponibilité'),
            ('Savoir-être', NULL, 'Sens de l\\'éthique'),
            ('Savoir-être', NULL, 'Organisation'),
            ('Savoir-être', NULL, 'Hygiène'),
            ('Savoir-être', NULL, 'Gestion du temps'),
            ('Savoir-être', NULL, 'Fiabilité'),
            ('Savoir-être', NULL, 'Respect des consignes'),
            ('Savoir-être', NULL, 'Autorité naturelle'),
            ('Savoir-être', NULL, 'Remise en question permanente'),
            ('Savoir-être', NULL, 'Sens des responsabilités'),
            ('Savoir-être', NULL, 'Vigilance'),
            ('Savoir-être', NULL, 'Proactivité')
        ");
        // LANGUES (17 langues)
        $this->addSql("INSERT INTO competence (type, domaine, nom) VALUES 
            ('Langues', NULL, 'Anglais'),
            ('Langues', NULL, 'Allemand'),
            ('Langues', NULL, 'Arabe'),
            ('Langues', NULL, 'Chinois'),
            ('Langues', NULL, 'Coréen'),
            ('Langues', NULL, 'Danois'),
            ('Langues', NULL, 'Espagnol'),
            ('Langues', NULL, 'Hébreu'),
            ('Langues', NULL, 'Italien'),
            ('Langues', NULL, 'Japonais'),
            ('Langues', NULL, 'Néerlandais'),
            ('Langues', NULL, 'Norvégien'),
            ('Langues', NULL, 'Polonais'),
            ('Langues', NULL, 'Portugais'),
            ('Langues', NULL, 'Russe'),
            ('Langues', NULL, 'Suédois'),
            ('Langues', NULL, 'Tchèque')
        ");
    }

    

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
         $this->addSql("TRUNCATE TABLE competence");
       
    }
}
