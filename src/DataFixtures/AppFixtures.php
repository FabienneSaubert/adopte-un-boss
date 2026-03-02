<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Competence;
use App\Entity\Departement;
use App\Entity\Entreprise;
use App\Entity\Offre;
use App\Entity\Recruteur;
use App\Entity\SelectionCompetence;
use App\Entity\Utilisateur;
use App\Enum\DomaineActivite;
use App\Enum\NiveauEtude;
use App\Enum\RoleUtilisateur;
use App\Enum\StatutInscription;
use App\Enum\StatutOffre;
use App\Enum\TypeCompetence;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixture principale du projet (générée par IA).
 *
 * Cette fixture crée tout le minimum nécessaire pour pouvoir afficher 20 annonces :
 * - des départements ;
 * - des compétences ;
 * - des entreprises ;
 * - des recruteurs (avec leur utilisateur associé) ;
 * - 20 offres cohérentes avec l'entité Offre et le contrôleur OffreController.
 *
 * Important : le dépôt ne contient pas encore doctrine/doctrine-fixtures-bundle.
 * Il faudra donc l'installer avant de charger cette fixture.
 */
final class AppFixtures extends Fixture
{
    /**
     * Hash bcrypt commun utilisé pour tous les comptes recruteurs.
     * Mot de passe en clair : recruteur123
     */
    private const DEFAULT_PASSWORD_HASH = '$2y$10$HGI4A60fu7VVfmf1fTuGc.cBfcE56c5yt0FGbJ.HQKTgYDN8zsX3i';

    public function load(ObjectManager $manager): void
    {
        $departements = $this->createDepartements($manager);
        $competences = $this->createCompetences($manager);
        $recruteurs = $this->createRecruteurs($manager);

        $this->createOffres($manager, $departements, $competences, $recruteurs);

        // Un seul flush final pour enregistrer proprement l'ensemble du jeu de données.
        $manager->flush();
    }

    /**
     * Création d'un petit référentiel de départements utilisés par les offres.
     *
     * @return array<string, Departement>
     */
    private function createDepartements(ObjectManager $manager): array
    {
        $definitions = [
            ['numero' => '13', 'nom' => 'Bouches-du-Rhône'],
            ['numero' => '31', 'nom' => 'Haute-Garonne'],
            ['numero' => '33', 'nom' => 'Gironde'],
            ['numero' => '34', 'nom' => 'Hérault'],
            ['numero' => '44', 'nom' => 'Loire-Atlantique'],
            ['numero' => '59', 'nom' => 'Nord'],
            ['numero' => '67', 'nom' => 'Bas-Rhin'],
            ['numero' => '69', 'nom' => 'Rhône'],
            ['numero' => '75', 'nom' => 'Paris'],
            ['numero' => '06', 'nom' => 'Alpes-Maritimes'],
        ];

        $departements = [];

        foreach ($definitions as $definition) {
            $departement = (new Departement())
                ->setNumero($definition['numero'])
                ->setNom($definition['nom']);

            $manager->persist($departement);
            $departements[$definition['numero']] = $departement;
        }

        return $departements;
    }

    /**
     * Création d'un référentiel de compétences réutilisable dans les sélections d'offres.
     *
     * @return array<string, Competence>
     */
    private function createCompetences(ObjectManager $manager): array
    {
        $definitions = [
            // Informatique / Numérique
            ['nom' => 'PHP', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::INFORMATIQUE],
            ['nom' => 'Symfony', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::INFORMATIQUE],
            ['nom' => 'React', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::INFORMATIQUE],
            ['nom' => 'Docker', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::INFORMATIQUE],
            ['nom' => 'SQL', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::INFORMATIQUE],
            ['nom' => 'Support utilisateurs', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::INFORMATIQUE],
            ['nom' => 'Power BI', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::INFORMATIQUE],

            // Commerce / Vente
            ['nom' => 'Négociation commerciale', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::COMMERCE],
            ['nom' => 'Vente conseil', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::COMMERCE],
            ['nom' => 'Merchandising', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::COMMERCE],
            ['nom' => 'Gestion de portefeuille clients', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::COMMERCE],

            // Communication / Marketing
            ['nom' => 'SEO', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::COMMUNICATION],
            ['nom' => 'Community management', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::COMMUNICATION],
            ['nom' => 'Google Ads', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::COMMUNICATION],
            ['nom' => 'Rédaction web', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::COMMUNICATION],

            // RH
            ['nom' => 'Sourcing', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::RH],
            ['nom' => 'Conduite d’entretien', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::RH],
            ['nom' => 'Gestion administrative RH', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::RH],
            ['nom' => 'Droit social', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::RH],

            // Finance
            ['nom' => 'Comptabilité générale', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::FINANCE],
            ['nom' => 'Excel avancé', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::FINANCE],
            ['nom' => 'Analyse financière', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::FINANCE],

            // Logistique / Transport
            ['nom' => 'Gestion de stock', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::LOGISTIQUE],
            ['nom' => 'Préparation de commandes', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::LOGISTIQUE],
            ['nom' => 'Planification transport', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::LOGISTIQUE],
            ['nom' => 'Maîtrise d’un WMS', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::LOGISTIQUE],

            // Hôtellerie / Restauration / Tourisme
            ['nom' => 'Service client', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::HOTELLERIE],
            ['nom' => 'HACCP', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::HOTELLERIE],
            ['nom' => 'Gestion hôtelière', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::HOTELLERIE],
            ['nom' => 'Cuisine de production', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::HOTELLERIE],

            // Santé / Social
            ['nom' => 'Soins infirmiers', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::SANTE],
            ['nom' => 'Coordination de parcours', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::SANTE],
            ['nom' => 'Relation patient', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::SANTE],

            // Bâtiment
            ['nom' => 'Lecture de plans', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::BATIMENT],
            ['nom' => 'Maintenance multiservice', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::BATIMENT],
            ['nom' => 'Sécurité chantier', 'type' => TypeCompetence::SAVOIR_FAIRE, 'domaine' => DomaineActivite::BATIMENT],

            // Compétences transverses
            ['nom' => 'Autonomie', 'type' => TypeCompetence::SAVOIR_ETRE, 'domaine' => null],
            ['nom' => 'Rigueur', 'type' => TypeCompetence::SAVOIR_ETRE, 'domaine' => null],
            ['nom' => 'Travail en équipe', 'type' => TypeCompetence::SAVOIR_ETRE, 'domaine' => null],
            ['nom' => 'Communication', 'type' => TypeCompetence::SAVOIR_ETRE, 'domaine' => null],
            ['nom' => 'Anglais professionnel', 'type' => TypeCompetence::LANGUES, 'domaine' => null],
        ];

        $competences = [];

        foreach ($definitions as $definition) {
            $competence = (new Competence())
                ->setNom($definition['nom'])
                ->setType($definition['type'])
                ->setDomaine($definition['domaine']);

            $manager->persist($competence);
            $competences[$definition['nom']] = $competence;
        }

        return $competences;
    }

    /**
     * Création de quelques entreprises et de leurs recruteurs associés.
     *
     * @return array<int, Recruteur>
     */
    private function createRecruteurs(ObjectManager $manager): array
    {
        $definitions = [
            [
                'entreprise' => ['nom' => 'NovaTech Solutions', 'siret' => '80123456789011', 'adresse' => '12 avenue de l\'Innovation, 34000 Montpellier'],
                'utilisateur' => ['nom' => 'Martin', 'prenom' => 'Claire', 'email' => 'claire.martin@novatech.test', 'telephone' => '0610101010'],
                'recruteur' => ['poste' => 'Responsable recrutement', 'email_pro' => 'recrutement@novatech.test', 'telephone_pro' => '0467000001'],
            ],
            [
                'entreprise' => ['nom' => 'HexaCommerce', 'siret' => '80123456789012', 'adresse' => '8 rue des Halles, 75002 Paris'],
                'utilisateur' => ['nom' => 'Durand', 'prenom' => 'Sophie', 'email' => 'sophie.durand@hexacommerce.test', 'telephone' => '0610101011'],
                'recruteur' => ['poste' => 'Chargée RH', 'email_pro' => 'rh@hexacommerce.test', 'telephone_pro' => '0140000002'],
            ],
            [
                'entreprise' => ['nom' => 'BlueLog Transport', 'siret' => '80123456789013', 'adresse' => '21 boulevard du Port, 44000 Nantes'],
                'utilisateur' => ['nom' => 'Petit', 'prenom' => 'Nicolas', 'email' => 'nicolas.petit@bluelog.test', 'telephone' => '0610101012'],
                'recruteur' => ['poste' => 'Talent acquisition manager', 'email_pro' => 'jobs@bluelog.test', 'telephone_pro' => '0240000003'],
            ],
            [
                'entreprise' => ['nom' => 'ComptaVision', 'siret' => '80123456789014', 'adresse' => '55 rue du Centre, 69003 Lyon'],
                'utilisateur' => ['nom' => 'Bernard', 'prenom' => 'Julie', 'email' => 'julie.bernard@comptavision.test', 'telephone' => '0610101013'],
                'recruteur' => ['poste' => 'Responsable de pôle', 'email_pro' => 'recrutement@comptavision.test', 'telephone_pro' => '0472000004'],
            ],
            [
                'entreprise' => ['nom' => 'Azur Hospitality Group', 'siret' => '80123456789015', 'adresse' => '4 promenade des Anglais, 06000 Nice'],
                'utilisateur' => ['nom' => 'Garcia', 'prenom' => 'Elena', 'email' => 'elena.garcia@azurhospitality.test', 'telephone' => '0610101014'],
                'recruteur' => ['poste' => 'HR Business Partner', 'email_pro' => 'careers@azurhospitality.test', 'telephone_pro' => '0493000005'],
            ],
            [
                'entreprise' => ['nom' => 'MediCare Réseau', 'siret' => '80123456789016', 'adresse' => '18 place de la République, 31000 Toulouse'],
                'utilisateur' => ['nom' => 'Roux', 'prenom' => 'Thomas', 'email' => 'thomas.roux@medicare.test', 'telephone' => '0610101015'],
                'recruteur' => ['poste' => 'Coordinateur recrutement', 'email_pro' => 'talents@medicare.test', 'telephone_pro' => '0534000006'],
            ],
        ];

        $recruteurs = [];

        foreach ($definitions as $definition) {
            $entreprise = (new Entreprise())
                ->setNom($definition['entreprise']['nom'])
                ->setSiret($definition['entreprise']['siret'])
                ->setAdresse($definition['entreprise']['adresse']);

            $utilisateur = (new Utilisateur())
                ->setRole(RoleUtilisateur::RECRUTEUR)
                ->setNom($definition['utilisateur']['nom'])
                ->setPrenom($definition['utilisateur']['prenom'])
                ->setEmail($definition['utilisateur']['email'])
                ->setTelephone($definition['utilisateur']['telephone'])
                ->setDateDeNaissance(new \DateTimeImmutable('1988-01-01'))
                ->setMdpHash(self::DEFAULT_PASSWORD_HASH)
                ->setStatutInscription(StatutInscription::VALIDE);

            $recruteur = (new Recruteur())
                ->setPoste($definition['recruteur']['poste'])
                ->setEmailPro($definition['recruteur']['email_pro'])
                ->setTelephonePro($definition['recruteur']['telephone_pro'])
                ->setEntreprise($entreprise)
                ->setUtilisateur($utilisateur);

            $manager->persist($entreprise);
            $manager->persist($utilisateur);
            $manager->persist($recruteur);

            $recruteurs[] = $recruteur;
        }

        return $recruteurs;
    }

    /**
     * Création des 20 offres d'emploi.
     *
     * @param array<string, Departement> $departements
     * @param array<string, Competence>  $competences
     * @param array<int, Recruteur>      $recruteurs
     */
    private function createOffres(
        ObjectManager $manager,
        array $departements,
        array $competences,
        array $recruteurs,
    ): void {
        $definitions = [
            [
                'categorie' => DomaineActivite::INFORMATIQUE,
                'intitule' => 'Développeur PHP / Symfony',
                'description' => 'Vous intervenez sur la maintenance évolutive et le développement de nouvelles API métier dans un environnement Symfony.',
                'salaire' => 38000,
                'niveau' => NiveauEtude::BAC_3,
                'coeff_niveau' => 7,
                'teletravail' => true,
                'departement' => '34',
                'coeff_departement' => 8,
                'recruteur' => 0,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 84,
                'date' => '2026-01-10',
                'competences' => [
                    ['nom' => 'PHP', 'coeff' => 9],
                    ['nom' => 'Symfony', 'coeff' => 10],
                    ['nom' => 'SQL', 'coeff' => 7],
                    ['nom' => 'Rigueur', 'coeff' => 6],
                ],
            ],
            [
                'categorie' => DomaineActivite::INFORMATIQUE,
                'intitule' => 'Développeur Front React',
                'description' => 'Vous participez à la création d’interfaces dynamiques, accessibles et performantes pour nos applications web internes.',
                'salaire' => 36000,
                'niveau' => NiveauEtude::BAC_3,
                'coeff_niveau' => 7,
                'teletravail' => true,
                'departement' => '75',
                'coeff_departement' => 7,
                'recruteur' => 0,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 61,
                'date' => '2026-01-12',
                'competences' => [
                    ['nom' => 'React', 'coeff' => 10],
                    ['nom' => 'Communication', 'coeff' => 5],
                    ['nom' => 'Travail en équipe', 'coeff' => 6],
                    ['nom' => 'Anglais professionnel', 'coeff' => 4],
                ],
            ],
            [
                'categorie' => DomaineActivite::INFORMATIQUE,
                'intitule' => 'Administrateur systèmes et conteneurs',
                'description' => 'Vous assurez la stabilité de l’infrastructure, l’industrialisation des environnements et le support aux équipes de développement.',
                'salaire' => 42000,
                'niveau' => NiveauEtude::BAC_2,
                'coeff_niveau' => 6,
                'teletravail' => true,
                'departement' => '69',
                'coeff_departement' => 6,
                'recruteur' => 0,
                'statut' => StatutOffre::EN_ATTENTE,
                'vues' => 43,
                'date' => '2026-01-14',
                'competences' => [
                    ['nom' => 'Docker', 'coeff' => 10],
                    ['nom' => 'SQL', 'coeff' => 5],
                    ['nom' => 'Support utilisateurs', 'coeff' => 6],
                    ['nom' => 'Autonomie', 'coeff' => 6],
                ],
            ],
            [
                'categorie' => DomaineActivite::INFORMATIQUE,
                'intitule' => 'Technicien support informatique',
                'description' => 'Vous prenez en charge le support de niveau 1 et 2, le suivi du parc et l’accompagnement des utilisateurs au quotidien.',
                'salaire' => 27000,
                'niveau' => NiveauEtude::BAC_2,
                'coeff_niveau' => 5,
                'teletravail' => false,
                'departement' => '59',
                'coeff_departement' => 5,
                'recruteur' => 0,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 29,
                'date' => '2026-01-16',
                'competences' => [
                    ['nom' => 'Support utilisateurs', 'coeff' => 10],
                    ['nom' => 'Communication', 'coeff' => 7],
                    ['nom' => 'Rigueur', 'coeff' => 6],
                ],
            ],
            [
                'categorie' => DomaineActivite::INFORMATIQUE,
                'intitule' => 'Data analyst junior',
                'description' => 'Vous exploitez et mettez en forme les données commerciales afin d’aider les managers à prendre de meilleures décisions.',
                'salaire' => 34000,
                'niveau' => NiveauEtude::BAC_3,
                'coeff_niveau' => 7,
                'teletravail' => true,
                'departement' => '33',
                'coeff_departement' => 6,
                'recruteur' => 3,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 72,
                'date' => '2026-01-18',
                'competences' => [
                    ['nom' => 'Power BI', 'coeff' => 9],
                    ['nom' => 'Excel avancé', 'coeff' => 8],
                    ['nom' => 'Analyse financière', 'coeff' => 4],
                    ['nom' => 'Rigueur', 'coeff' => 6],
                ],
            ],
            [
                'categorie' => DomaineActivite::COMMERCE,
                'intitule' => 'Commercial terrain GMS',
                'description' => 'Vous développez le chiffre d’affaires d’un portefeuille de magasins, pilotez les opérations commerciales et optimisez la présence produit.',
                'salaire' => 32000,
                'niveau' => NiveauEtude::BAC,
                'coeff_niveau' => 4,
                'teletravail' => false,
                'departement' => '13',
                'coeff_departement' => 8,
                'recruteur' => 1,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 95,
                'date' => '2026-01-20',
                'competences' => [
                    ['nom' => 'Négociation commerciale', 'coeff' => 10],
                    ['nom' => 'Merchandising', 'coeff' => 7],
                    ['nom' => 'Gestion de portefeuille clients', 'coeff' => 8],
                    ['nom' => 'Autonomie', 'coeff' => 7],
                ],
            ],
            [
                'categorie' => DomaineActivite::COMMERCE,
                'intitule' => 'Responsable de rayon',
                'description' => 'Vous animez l’activité d’un rayon, pilotez l’atteinte des objectifs et garantissez la qualité de l’expérience client.',
                'salaire' => 30000,
                'niveau' => NiveauEtude::BAC_2,
                'coeff_niveau' => 5,
                'teletravail' => false,
                'departement' => '34',
                'coeff_departement' => 9,
                'recruteur' => 1,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 57,
                'date' => '2026-01-21',
                'competences' => [
                    ['nom' => 'Vente conseil', 'coeff' => 8],
                    ['nom' => 'Merchandising', 'coeff' => 7],
                    ['nom' => 'Communication', 'coeff' => 6],
                    ['nom' => 'Travail en équipe', 'coeff' => 6],
                ],
            ],
            [
                'categorie' => DomaineActivite::COMMERCE,
                'intitule' => 'Chargé de clientèle B2B',
                'description' => 'Vous gérez un portefeuille clients professionnels, identifiez les besoins et proposez des solutions adaptées.',
                'salaire' => 33000,
                'niveau' => NiveauEtude::BAC_2,
                'coeff_niveau' => 5,
                'teletravail' => true,
                'departement' => '67',
                'coeff_departement' => 5,
                'recruteur' => 1,
                'statut' => StatutOffre::EN_ATTENTE,
                'vues' => 33,
                'date' => '2026-01-22',
                'competences' => [
                    ['nom' => 'Gestion de portefeuille clients', 'coeff' => 9],
                    ['nom' => 'Négociation commerciale', 'coeff' => 8],
                    ['nom' => 'Communication', 'coeff' => 7],
                ],
            ],
            [
                'categorie' => DomaineActivite::COMMUNICATION,
                'intitule' => 'Assistant marketing digital',
                'description' => 'Vous participez à la mise en œuvre de campagnes webmarketing, au suivi de la performance et à l’animation éditoriale.',
                'salaire' => 28000,
                'niveau' => NiveauEtude::BAC_3,
                'coeff_niveau' => 6,
                'teletravail' => true,
                'departement' => '75',
                'coeff_departement' => 7,
                'recruteur' => 1,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 64,
                'date' => '2026-01-23',
                'competences' => [
                    ['nom' => 'SEO', 'coeff' => 8],
                    ['nom' => 'Google Ads', 'coeff' => 6],
                    ['nom' => 'Rédaction web', 'coeff' => 8],
                    ['nom' => 'Rigueur', 'coeff' => 5],
                ],
            ],
            [
                'categorie' => DomaineActivite::COMMUNICATION,
                'intitule' => 'Community manager',
                'description' => 'Vous produisez les contenus, animez les communautés et mesurez les retombées des prises de parole digitales.',
                'salaire' => 29000,
                'niveau' => NiveauEtude::BAC_3,
                'coeff_niveau' => 6,
                'teletravail' => true,
                'departement' => '06',
                'coeff_departement' => 6,
                'recruteur' => 4,
                'statut' => StatutOffre::REFUSE,
                'vues' => 18,
                'date' => '2026-01-24',
                'competences' => [
                    ['nom' => 'Community management', 'coeff' => 10],
                    ['nom' => 'Rédaction web', 'coeff' => 7],
                    ['nom' => 'Communication', 'coeff' => 8],
                    ['nom' => 'Anglais professionnel', 'coeff' => 5],
                ],
            ],
            [
                'categorie' => DomaineActivite::RH,
                'intitule' => 'Gestionnaire paie et administration RH',
                'description' => 'Vous prenez en charge les variables de paie, le suivi administratif des salariés et le contrôle des données RH.',
                'salaire' => 32000,
                'niveau' => NiveauEtude::BAC_2,
                'coeff_niveau' => 6,
                'teletravail' => true,
                'departement' => '69',
                'coeff_departement' => 7,
                'recruteur' => 3,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 45,
                'date' => '2026-01-25',
                'competences' => [
                    ['nom' => 'Gestion administrative RH', 'coeff' => 10],
                    ['nom' => 'Droit social', 'coeff' => 8],
                    ['nom' => 'Excel avancé', 'coeff' => 6],
                    ['nom' => 'Rigueur', 'coeff' => 7],
                ],
            ],
            [
                'categorie' => DomaineActivite::RH,
                'intitule' => 'Chargé de recrutement',
                'description' => 'Vous gérez les besoins de recrutement, le sourcing et les entretiens de préqualification pour plusieurs services.',
                'salaire' => 31000,
                'niveau' => NiveauEtude::BAC_3,
                'coeff_niveau' => 6,
                'teletravail' => true,
                'departement' => '31',
                'coeff_departement' => 8,
                'recruteur' => 5,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 53,
                'date' => '2026-01-26',
                'competences' => [
                    ['nom' => 'Sourcing', 'coeff' => 9],
                    ['nom' => 'Conduite d’entretien', 'coeff' => 9],
                    ['nom' => 'Communication', 'coeff' => 6],
                    ['nom' => 'Travail en équipe', 'coeff' => 5],
                ],
            ],
            [
                'categorie' => DomaineActivite::FINANCE,
                'intitule' => 'Assistant comptable',
                'description' => 'Vous assurez la saisie comptable, les rapprochements bancaires et la préparation des pièces de clôture.',
                'salaire' => 28000,
                'niveau' => NiveauEtude::BAC_2,
                'coeff_niveau' => 5,
                'teletravail' => false,
                'departement' => '33',
                'coeff_departement' => 6,
                'recruteur' => 3,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 47,
                'date' => '2026-01-27',
                'competences' => [
                    ['nom' => 'Comptabilité générale', 'coeff' => 10],
                    ['nom' => 'Excel avancé', 'coeff' => 7],
                    ['nom' => 'Rigueur', 'coeff' => 7],
                ],
            ],
            [
                'categorie' => DomaineActivite::FINANCE,
                'intitule' => 'Contrôleur de gestion junior',
                'description' => 'Vous produisez les tableaux de bord, analysez les écarts et accompagnez les responsables dans le suivi budgétaire.',
                'salaire' => 38000,
                'niveau' => NiveauEtude::BAC_5,
                'coeff_niveau' => 8,
                'teletravail' => true,
                'departement' => '69',
                'coeff_departement' => 7,
                'recruteur' => 3,
                'statut' => StatutOffre::EN_ATTENTE,
                'vues' => 39,
                'date' => '2026-01-28',
                'competences' => [
                    ['nom' => 'Analyse financière', 'coeff' => 10],
                    ['nom' => 'Excel avancé', 'coeff' => 9],
                    ['nom' => 'Communication', 'coeff' => 4],
                ],
            ],
            [
                'categorie' => DomaineActivite::LOGISTIQUE,
                'intitule' => 'Préparateur de commandes',
                'description' => 'Vous préparez les commandes, contrôlez les flux et garantissez la qualité de préparation dans l’entrepôt.',
                'salaire' => 25000,
                'niveau' => NiveauEtude::BEP_CAP,
                'coeff_niveau' => 3,
                'teletravail' => false,
                'departement' => '44',
                'coeff_departement' => 8,
                'recruteur' => 2,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 66,
                'date' => '2026-01-29',
                'competences' => [
                    ['nom' => 'Préparation de commandes', 'coeff' => 10],
                    ['nom' => 'Gestion de stock', 'coeff' => 7],
                    ['nom' => 'Rigueur', 'coeff' => 6],
                ],
            ],
            [
                'categorie' => DomaineActivite::LOGISTIQUE,
                'intitule' => 'Exploitant transport',
                'description' => 'Vous planifiez les tournées, optimisez les moyens de transport et suivez la qualité de service.',
                'salaire' => 31000,
                'niveau' => NiveauEtude::BAC_2,
                'coeff_niveau' => 5,
                'teletravail' => false,
                'departement' => '44',
                'coeff_departement' => 8,
                'recruteur' => 2,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 52,
                'date' => '2026-01-30',
                'competences' => [
                    ['nom' => 'Planification transport', 'coeff' => 10],
                    ['nom' => 'Maîtrise d’un WMS', 'coeff' => 6],
                    ['nom' => 'Communication', 'coeff' => 5],
                ],
            ],
            [
                'categorie' => DomaineActivite::LOGISTIQUE,
                'intitule' => 'Gestionnaire de stock',
                'description' => 'Vous garantissez la fiabilité des inventaires, le suivi des entrées/sorties et la bonne tenue des emplacements.',
                'salaire' => 29000,
                'niveau' => NiveauEtude::BAC,
                'coeff_niveau' => 4,
                'teletravail' => false,
                'departement' => '59',
                'coeff_departement' => 6,
                'recruteur' => 2,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 41,
                'date' => '2026-01-31',
                'competences' => [
                    ['nom' => 'Gestion de stock', 'coeff' => 10],
                    ['nom' => 'Maîtrise d’un WMS', 'coeff' => 7],
                    ['nom' => 'Rigueur', 'coeff' => 7],
                ],
            ],
            [
                'categorie' => DomaineActivite::HOTELLERIE,
                'intitule' => 'Réceptionniste hôtel',
                'description' => 'Vous assurez l’accueil physique et téléphonique, les arrivées/départs et la satisfaction des clients.',
                'salaire' => 26000,
                'niveau' => NiveauEtude::BAC,
                'coeff_niveau' => 4,
                'teletravail' => false,
                'departement' => '06',
                'coeff_departement' => 9,
                'recruteur' => 4,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 58,
                'date' => '2026-02-01',
                'competences' => [
                    ['nom' => 'Service client', 'coeff' => 10],
                    ['nom' => 'Gestion hôtelière', 'coeff' => 6],
                    ['nom' => 'Anglais professionnel', 'coeff' => 8],
                    ['nom' => 'Communication', 'coeff' => 7],
                ],
            ],
            [
                'categorie' => DomaineActivite::HOTELLERIE,
                'intitule' => 'Chef de partie',
                'description' => 'Vous participez à la production culinaire, au respect des normes d’hygiène et à l’organisation du service.',
                'salaire' => 30000,
                'niveau' => NiveauEtude::BEP_CAP,
                'coeff_niveau' => 4,
                'teletravail' => false,
                'departement' => '13',
                'coeff_departement' => 8,
                'recruteur' => 4,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 37,
                'date' => '2026-02-02',
                'competences' => [
                    ['nom' => 'Cuisine de production', 'coeff' => 10],
                    ['nom' => 'HACCP', 'coeff' => 9],
                    ['nom' => 'Travail en équipe', 'coeff' => 6],
                ],
            ],
            [
                'categorie' => DomaineActivite::SANTE,
                'intitule' => 'Infirmier coordinateur',
                'description' => 'Vous coordonnez les interventions, accompagnez les équipes et assurez la continuité du parcours de soins.',
                'salaire' => 41000,
                'niveau' => NiveauEtude::BAC_3,
                'coeff_niveau' => 8,
                'teletravail' => false,
                'departement' => '31',
                'coeff_departement' => 8,
                'recruteur' => 5,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 49,
                'date' => '2026-02-03',
                'competences' => [
                    ['nom' => 'Soins infirmiers', 'coeff' => 10],
                    ['nom' => 'Coordination de parcours', 'coeff' => 9],
                    ['nom' => 'Relation patient', 'coeff' => 8],
                ],
            ],
            [
                'categorie' => DomaineActivite::BATIMENT,
                'intitule' => 'Technicien maintenance bâtiment',
                'description' => 'Vous réalisez des interventions de maintenance préventive et corrective sur plusieurs sites tertiaires.',
                'salaire' => 32000,
                'niveau' => NiveauEtude::BEP_CAP,
                'coeff_niveau' => 4,
                'teletravail' => false,
                'departement' => '34',
                'coeff_departement' => 7,
                'recruteur' => 2,
                'statut' => StatutOffre::ACCEPTE,
                'vues' => 35,
                'date' => '2026-02-04',
                'competences' => [
                    ['nom' => 'Maintenance multiservice', 'coeff' => 10],
                    ['nom' => 'Lecture de plans', 'coeff' => 6],
                    ['nom' => 'Sécurité chantier', 'coeff' => 8],
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $offre = (new Offre())
                ->setCategorieOffre($definition['categorie'])
                ->setIntitule($definition['intitule'])
                ->setDescription($definition['description'])
                ->setSalaire($definition['salaire'])
                ->setNiveauEtudes($definition['niveau'])
                ->setCoeffNiveauEtudes($definition['coeff_niveau'])
                ->setTeletravailPossible($definition['teletravail'])
                ->setCoeffDepartement($definition['coeff_departement'])
                ->setDateDePublication(new \DateTimeImmutable($definition['date']))
                ->setStatutOffre($definition['statut'])
                ->setNombreDeVues($definition['vues'])
                ->setRecruteur($recruteurs[$definition['recruteur']])
                ->setDepartement($departements[$definition['departement']]);

            $manager->persist($offre);

            // Chaque offre reçoit ses compétences pondérées,
            // exactement comme attendu dans la structure métier du projet.
            foreach ($definition['competences'] as $competenceDefinition) {
                $selection = (new SelectionCompetence())
                    ->setOffre($offre)
                    ->setCompetence($competences[$competenceDefinition['nom']])
                    ->setCoeffCompetence($competenceDefinition['coeff']);

                $offre->addSelectionCompetence($selection);
                $manager->persist($selection);
            }
        }
    }
}