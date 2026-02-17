<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Entity\SelectionCompetence;
use App\Enum\DomaineActivite;
use App\Enum\NiveauEtude;
use App\Enum\StatutOffre;
use App\Repository\CompetenceRepository;
use App\Repository\DepartementRepository;
use App\Repository\OffreRepository;
use App\Repository\RecruteurRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/offre')]
final class OffreController extends AbstractController
{
    #[Route(name: 'api_offre_get_collection', methods: ['GET'])]
    public function index(OffreRepository $offreRepository): JsonResponse
    {
        $offre = array_map(
            fn(Offre $offre) => $this->serializeOffre($offre),
            $offreRepository->findAll()
        );

        return $this->json($offre);
    }

    #[Route(name: 'api_offre_post_item', methods: ['POST'])]
    public function new(
        Request $request,
        RecruteurRepository $recruteurRepository,
        DepartementRepository $departementRepository,
        CompetenceRepository $competenceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse("Données dans le JSON body invalides.", Response::HTTP_BAD_REQUEST);
        }

        $categorieOffreError = null;
        $categorieOffre = $this->parseDomaineActivite((string) ($data["categorie_offre"] ?? null), true, $categorieOffreError);
        if ($categorieOffre === null) {
            return $this->errorResponse($categorieOffreError ?? "Le domaine d'activité de l'offre n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        $intituleError = null;
        $intitule = $this->parseIntitule((string) ($data["intitule"] ?? null), true, $intituleError);
        if ($intitule === null) {
            return $this->errorResponse($intituleError ?? "L'intitulé n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        $descriptionError = null;
        $description = $this->parseDescription((string) ($data["description"] ?? null), true, $descriptionError);
        if ($description === null) {
            return $this->errorResponse($descriptionError ?? "La description n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        // Le salaire étant optionnel, on utilise la même vérification que lors de l'update
        $salaire = (string) ($data["salaire"] ?? null);
        if ($salaire !== null && $salaire !== '') {
            $salaireError = null;
            $salaire = $this->parseSalaire($salaire, false, $salaireError);
            if ($salaire === null) {
                return $this->errorResponse($salaireError ?? "Le salaire n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }
        else {
            $salaire = null;
        }

        $niveauEtudesError = null;
        $niveauEtudes = $this->parseNiveauEtudes((string) ($data["niveau_etudes"] ?? null), true, $niveauEtudesError);
        if ($niveauEtudes === null) {
            return $this->errorResponse($niveauEtudesError ?? "Le niveau d'études n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        $coeffNiveauEtudesError = null;
        $coeffNiveauEtudes = $this->parseCoeffNiveauEtudes((string) ($data["coeff_niveau_etudes"] ?? null), true, $coeffNiveauEtudesError);
        if ($coeffNiveauEtudes === null) {
            return $this->errorResponse($coeffNiveauEtudesError ?? "Le coefficient du niveau d'études n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        // Le télétravail possible étant seulement un booléen, la vérification est beaucoup plus simple.
        // On vérifie simplement si l'attribut a été défini dans la requête, et qu'il s'agit bien d'un booléen
        if (!isset($data["teletravail_possible"])) {
            return $this->errorResponse("Le champ teletravail_possible est requis.", Response::HTTP_BAD_REQUEST);
        }
        if (!is_bool($data["teletravail_possible"])) {
            return $this->errorResponse("Le champ teletravail_possible doit être un booléen.", Response::HTTP_BAD_REQUEST);
        }
        $teletravailPossible = $data["teletravail_possible"];

        $coeffdepartementError = null;
        $coeffdepartement = $this->parseCoeffDepartement((string) ($data["coeff_departement"] ?? null), true, $coeffdepartementError);
        if ($coeffdepartement === null) {
            return $this->errorResponse($coeffdepartementError ?? "Le coefficient du département n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        // On défini ici la date qui correspond à la date actuelle, venant du serveur
        // On prend une nouvelle instance de DateTimeImmutable qui renvoi une date inchangeable
        // correspondante au moment où est exécuté la commande.
        // On choisi le bon fuseau horaire correspondant à la France métropolitaine.
        $datePublication = new DateTimeImmutable('now',new \DateTimeZone('Europe/Paris'));

        // On met le nombre de vues à 0 par défaut
        $nombredevues = 0;

        // Récupération du N° de département, depuis un <select> du formulaire envoyé par le client
        $numeroDepartementError = null;
        $numeroDepartement = $this->parseNumeroDepartement((string) ($data["numero_departement"] ?? null), true, $numeroDepartementError);
        if ($numeroDepartement === null) {
            return $this->errorResponse($numeroDepartementError ?? "Le N° de département n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }
        $departement = $departementRepository->findOneBy(['numero' => $numeroDepartement]);
        if ($departement === null) {
            return $this->errorResponse("Le N° de département est introuvable en base de données.", Response::HTTP_BAD_REQUEST);
        }

        // Récuperation de l'ID du recruteur
        // Celui-ci est censé être recupéré depuis le serveur, via la session PHP ou un cookie du
        // recruteur connecté, on utilise une valeur attribuable depuis la requête pour le moment.
        if (!isset($data["recruteur_id"])) {
            return $this->errorResponse("L'ID du recruteur est requis.", Response::HTTP_BAD_REQUEST);
        }
        $recruteurID = (int) $data["recruteur_id"];
        $recruteur = $recruteurRepository->find($recruteurID);
        if ($recruteur === null) {
            return $this->errorResponse("Le recruteur est introuvable en base de données.", Response::HTTP_BAD_REQUEST);
        }

        $offre = (new Offre())
            ->setCategorieOffre($categorieOffre)
            ->setIntitule($intitule)
            ->setDescription($description)
            ->setSalaire($salaire)
            ->setNiveauEtudes($niveauEtudes)
            ->setCoeffNiveauEtudes($coeffNiveauEtudes)
            ->setTeletravailPossible($teletravailPossible)
            ->setCoeffDepartement($coeffdepartement)
            ->setDateDePublication($datePublication)
            ->setNombreDeVues($nombredevues)
            ->setRecruteur($recruteur)
            ->setDepartement($departement)
        ;
        // Le statut de l'offre est déjà défini par défaut comme étant "En attente"

        // Gestion de la sélection des compétences
        $selectionCompetencesError = null;
        $selectionCompetences = $this->parseSelectionCompetences((array) ($data["selection_competences"] ?? null), true, $selectionCompetencesError);
        if ($selectionCompetences === null) {
            return $this->errorResponse($selectionCompetencesError ?? "La sélection des compétences n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }
        // Une fois que les IDs et les coeffs sont valides, on peut appeler une méthode
        // privée qui va se charger de remplir la table selectionCompetence associée
        // On remet à null la variable qui va contenir l'éventuel message d'erreur
        $selectionCompetencesError = null;
        // Puis on appelle la méthode
        $this->ajouterSelectionCompetences(
            $offre,
            $selectionCompetences,
            $competenceRepository,
            $entityManager,
            $selectionCompetencesError
        );
        if ($selectionCompetencesError !== null) {
            return $this->errorResponse($selectionCompetencesError, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($offre);

        $entityManager->flush();

        return $this->json($this->serializeOffre($offre), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_offre_show', methods: ['GET'])]
    public function show(int $id, OffreRepository $offreRepository): JsonResponse
    {
        $offre = $offreRepository->find($id);

        if (!$offre) {
            return $this->errorResponse("Offre introuvable.", Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeOffre($offre));
    }

    #[Route('/{id}', name: 'api_offre_put_item', methods: ['PUT'])]
    public function edit(
        int $id,
        Request $request,
        OffreRepository $offreRepository,
        DepartementRepository $departementRepository,
        CompetenceRepository $competenceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $offre = $offreRepository->find($id);

        if (!$offre) {
            return $this->errorResponse("Offre introuvable.", Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse("Données dans le JSON body invalides.", Response::HTTP_BAD_REQUEST);
        }

        $categorieOffre = (string) ($data["categorie_offre"] ?? null);
        if ($categorieOffre !== null && $categorieOffre !== '') {
            $categorieOffreError = null;
            $categorieOffre = $this->parseDomaineActivite($categorieOffre, false, $categorieOffreError);
            if ($categorieOffre !== null) {
                $offre->setCategorieOffre($categorieOffre);
            }
            else {
                return $this->errorResponse($categorieOffreError ?? "Le domaine d'activité de l'offre n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $intitule = (string) ($data["intitule"] ?? null);
        if ($intitule !== null && $intitule !== '') {
            $intituleError = null;
            $intitule = $this->parseIntitule($intitule, false, $intituleError);
            if ($intitule !== null) {
                $offre->setIntitule($intitule);
            }
            else {
                return $this->errorResponse($intituleError ?? "L'intitulé n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $description = (string) ($data["description"] ?? null);
        if ($description !== null && $description !== '') {
            $descriptionError = null;
            $description = $this->parseDescription($description, false, $descriptionError);
            if ($description !== null) {
                $offre->setDescription($description);
            }
            else {
                return $this->errorResponse($descriptionError ?? "La description n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $salaire = (string) ($data["salaire"] ?? null);
        if ($salaire !== null && $salaire !== '') {
            $salaireError = null;
            $salaire = $this->parseSalaire($salaire, false, $salaireError);
            if ($salaire !== null) {
                $offre->setSalaire($salaire);
            }
            else {
                return $this->errorResponse($salaireError ?? "Le salaire n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $niveauEtudes = (string) ($data["niveau_etudes"] ?? null);
        if ($niveauEtudes !== null && $niveauEtudes !== '') {
            $niveauEtudesError = null;
            $niveauEtudes = $this->parseNiveauEtudes($niveauEtudes, false, $niveauEtudesError);
            if ($niveauEtudes !== null) {
                $offre->setNiveauEtudes($niveauEtudes);
            }
            else {
                return $this->errorResponse($niveauEtudesError ?? "Le niveau d'études n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $coeffNiveauEtudes = (string) ($data["coeff_niveau_etudes"] ?? null);
        if ($coeffNiveauEtudes !== null && $coeffNiveauEtudes !== '') {
            $coeffNiveauEtudesError = null;
            $coeffNiveauEtudes = $this->parseCoeffNiveauEtudes($coeffNiveauEtudes, false, $coeffNiveauEtudesError);
            if ($coeffNiveauEtudes !== null) {
                $offre->setCoeffNiveauEtude($coeffNiveauEtudes);
            }
            else {
                return $this->errorResponse($coeffNiveauEtudesError ?? "Le coefficient du niveau d'études n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        // Pour l'update, on vérifie simplement la présence de l'attribut dans la requête
        if (isset($data["teletravail_possible"])) {
            // Si c'est le cas, on valide de la même façon
            if (!is_bool($data["teletravail_possible"])) {
                return $this->errorResponse("Le champ teletravail_possible doit être un booléen.", Response::HTTP_BAD_REQUEST);
            }
            // Puis on met à jour
            $teletravailPossible = $data["teletravail_possible"];
            $offre->setTeletravailPossible($teletravailPossible);
        }

        $numeroDepartement = (string) ($data["numero_departement"] ?? null);
        if ($numeroDepartement && $numeroDepartement !== '') {
            $numeroDepartementError = null;
            $numeroDepartement = $this->parseNumeroDepartement($numeroDepartement, true, $numeroDepartementError);
            if ($numeroDepartement === null) {
                return $this->errorResponse($numeroDepartementError ?? "Le N° de département n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $departement = $departementRepository->findOneBy(['numero' => $numeroDepartement]);
            if ($departement === null) {
                return $this->errorResponse("Le N° de département est introuvable en base de données.", Response::HTTP_BAD_REQUEST);
            }
            $offre->setDepartement($departement);
        }

        $coeffdepartement = (string) ($data["coeff_departement"] ?? null);
        if ($coeffdepartement !== null && $coeffdepartement !== '') {
            $coeffdepartementError = null;
            $coeffdepartement = $this->parseCoeffDepartement($coeffdepartement, false, $coeffdepartementError);
            if ($coeffdepartement !== null) {
                $offre->setCoeffDepartement($coeffdepartement);
            }
            else {
                return $this->errorResponse($coeffdepartementError ?? "Le coefficient du département n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $selectionCompetences = (array) ($data["selection_competences"] ?? null);
        if ($selectionCompetences !== null && $selectionCompetences !== '') {
            $selectionCompetencesError = null;
            $selectionCompetences = $this->parseSelectionCompetences($selectionCompetences, true, $selectionCompetencesError);
            if ($selectionCompetences === null) {
                return $this->errorResponse($selectionCompetencesError ?? "La sélection des compétences n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }

            // Suppression de toutes les sélections de compétence
            $offre->getSelectionCompetences()->clear();
            $entityManager->flush();

            $selectionCompetencesError = null;
            $this->ajouterSelectionCompetences(
                $offre,
                $selectionCompetences,
                $competenceRepository,
                $entityManager,
                $selectionCompetencesError
            );
            if ($selectionCompetencesError !== null) {
                return $this->errorResponse($selectionCompetencesError, Response::HTTP_BAD_REQUEST);
            }        
        }

        // Pour l'augmentation du nombre de vues, on utilise pour le moment un attribut "nombre_de_vues" à envoyer à l'API
        // Il est donc possible d'ajouter autant de nombre de vues de la part du client -> à améliorer
        // L'implémentation est pour le moment simple.
        if (isset($data["nombre_de_vues"])) {
            // On récupère d'abord le nombre de vues depuis notre objet    
            $nombreDeVues = $offre->getNombreDeVues();
            // Puis on lui met une valeur incrémentée, tout simplement
            $offre->setNombreDeVues($nombreDeVues + 1);
        }

        $statutOffre = (string) ($data["statut_offre"] ?? null);
        if ($statutOffre !== null && $statutOffre !== '') {
            $statutError = null;
            $statutOffre = $this->parseStatutOffre($statutOffre, false, $statutError);
            if ($statutOffre !== null) {
                $offre->setStatutOffre($statutOffre);
            }
            else {
                return $this->errorResponse($statutError ?? "Le statut de l'offre n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }


        $entityManager->flush();

        return $this->json($this->serializeOffre($offre));
    }

    #[Route('/{id}', name: 'api_offre_delete_item', methods: ['DELETE'])]
    public function delete(int $id, OffreRepository $offreRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $offre = $offreRepository->find($id);

        if (!$offre) {
            return $this->errorResponse("Offre introuvable.", Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($offre);

        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeOffre(Offre $offre): array
    {
        return [
            "id" => $offre->getId(),
            "categorie_offre" => $offre->getCategorieOffre(),
            "intitule" => $offre->getIntitule(),
            "description" => $offre->getDescription(),
            "salaire" => $offre->getSalaire(),
            "niveau_etudes" => $offre->getNiveauEtudes(),
            "coeff_niveau_etudes" => $offre->getCoeffNiveauEtudes(),
            "teletravail_possible" => $offre->isTeletravailPossible(),
            "coeff_departement" => $offre->getCoeffDepartement(),
            "departement" => [
                "numero" => $offre->getDepartement()->getNumero(),
                "nom" => $offre->getDepartement()->getNom()
            ],
            "selection_competences" => array_map(
                fn(SelectionCompetence $s) => [
                    'id' => $s->getId(),
                    'competence_id' => $s->getCompetence()->getId(),
                    'competence_nom' => $s->getCompetence()->getNom(),
                    'coeff' => $s->getCoeffCompetence(),
                ],
                $offre->getSelectionCompetences()->toArray()
            ),
            "recruteur" => [
                "poste" => $offre->getRecruteur()->getPoste(),
                "email_pro" => $offre->getRecruteur()->getEmailPro(),
                "telephone_pro" => $offre->getRecruteur()->getTelephonePro(),
                "utilisateur" => [
                    "nom" => $offre->getRecruteur()->getUtilisateur()->getNom(),
                    "prenom" => $offre->getRecruteur()->getUtilisateur()->getPrenom(),
                ],
                "entreprise" => [
                    "nom" => $offre->getRecruteur()->getEntreprise()->getNom(),
                    "siret" => $offre->getRecruteur()->getEntreprise()->getSiret(),
                ],
            ],
            "date_de_publication" => $offre->getDateDePublication(),
            "statut_offre" => $offre->getStatutOffre(),
            "nombre_de_vues" => $offre->getNombreDeVues(),
        ];
    }

    private function decodeJson(Request $request): ?array
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $payload;
    }

    private function parseDomaineActivite(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le domaine d'activité de l'offre est requis.";
            }
            return null;
        }

        // Pour la validation de l'enum, on utilise la méthode statique tryFrom()
        // qui va vérifier la présence du string dans l'enum
        $value = DomaineActivite::tryFrom($value);
        // S'il n'y a aucun résultat, c'est que le domaine d'activité est invalide
        if (!$value) return null;

        return $value;
    }

    private function parseIntitule(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "L'intitulé est requis.";
            }
            return null;
        }

        if (mb_strlen($value) > 150) {
            $error = "L'intitulé ne peut pas dépasser 150 caractères.";
            return null;
        }

        return $value;
    }

    private function parseDescription(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "La description est requise.";
            }
            return null;
        }

        return $value;
    }

    private function parseSalaire(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le salaire est requis.";
            }
            return null;
        }

        // On vérifie que la valeur reçue est bien un nombre (string ou int).
        if (!is_numeric($value)) {
            // Si ce n’est pas un nombre, on définit un message d’erreur.
            $error = "Le salaire doit être numérique.";
            return null;
        }

        // On convertit la valeur en int pour pouvoir la comparer et la stocker proprement.
        $value = (int) $value;

        // Pour essayer le plus possible d'éviter les valeurs absurdes,
        // On empêche le salaire d'être inférieur à 6 000 € / an (500 € / mois)
        if ($value < 6000) {
            $error = "Le salaire doit être d'au moins 6 000 € / an.";
            return null;
        }

        // On empêche le salaire de dépasser les 180 000 € / an (15 000 € / mois)
        if ($value > 180000) {
            $error = "Le salaire ne peut dépasser les 180 000 € / an.";
            return null;
        }

        // Si tout est valide, on retourne le salaire
        return $value;
    }

    private function parseNiveauEtudes(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le niveau d'études est requis.";
            }
            return null;
        }

        // Pour la validation de l'enum, on utilise la méthode statique tryFrom()
        // qui va vérifier la présence du string dans l'enum
        $value = NiveauEtude::tryFrom($value);
        // S'il n'y a aucun résultat, c'est que le niveau d'études est invalide
        if (!$value) return null;

        return $value;
    }

    private function parseCoeffNiveauEtudes(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le coefficient du niveau d'études est requis.";
            }
            return null;
        }

        // On vérifie que la valeur reçue est bien un nombre (string ou int).
        if (!is_numeric($value)) {
            // Si ce n’est pas un nombre, on définit un message d’erreur.
            $error = "Le coefficient du niveau d'études doit être numérique.";
            return null;
        }

        // On convertit la valeur en int pour pouvoir la comparer et la stocker proprement.
        $value = (int) $value;

        // On vérifie que le coefficient du niveau d'études n’est pas négatif ou égal à 0.
        // Une offre ne peut pas avoir un coefficient du niveau d'études inférieur à 1.
        if ($value < 1 || $value > 10) {
            $error = "Le coefficient du niveau d'études doit être compris entre 1 et 10.";
            return null;
        }

        // Si tout est valide, on retourne le coefficient du niveau d'études
        return $value;
    }

    private function parseNumeroDepartement(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le N° de département est requis.";
            }
            return null;
        }

        if (mb_strlen($value) > 3) {
            $error = "Le N° de département ne peut pas dépasser 3 caractères.";
            return null;
        }

        return $value;
    }

    private function parseCoeffDepartement(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le coefficient du département est requis.";
            }
            return null;
        }

        // On vérifie que la valeur reçue est bien un nombre (string ou int).
        if (!is_numeric($value)) {
            // Si ce n’est pas un nombre, on définit un message d’erreur.
            $error = "Le coefficient du département doit être numérique.";
            return null;
        }

        // On convertit la valeur en int pour pouvoir la comparer et la stocker proprement.
        $value = (int) $value;

        // On vérifie que le coefficient du département n’est pas négatif ou égal à 0.
        // Une offre ne peut pas avoir un coefficient du département inférieur à 1.
        if ($value < 1 || $value > 10) {
            $error = "Le coefficient du département doit être compris entre 1 et 10.";
            return null;
        }

        // Si tout est valide, on retourne le coefficient du département
        return $value;
    }

    private function parseSelectionCompetences(mixed $value, bool $required, ?string &$error)
    {
        if ($value === null || !is_array($value) || $value === []) {
            if ($required) {
                $error = "La sélection des compétences est requise.";
            }
            return null;
        }

        foreach ($value as $index => $selectionCompetence) {
            if (!is_array($selectionCompetence)) {
                $error = "La sélection de compétence n°".($index+1)." n'est pas valide.";
                return null;
            }

            if (!array_key_exists('competence_id',$selectionCompetence)) {
                $error = "La sélection de compétence n°".($index+1)." ne contient pas d'ID de compétence.";
                return null;
            }
            if (!array_key_exists('coeff',$selectionCompetence)) {
                $error = "La sélection de compétence n°".($index+1)." ne contient pas de coefficient.";
                return null;
            }

            $competenceID = $selectionCompetence['competence_id'];
            $coeff = $selectionCompetence['coeff'];

            if ($competenceID === null || $competenceID === '') {
                $error = "L'ID de la compétence de la sélection n°".($index+1)." n'est pas valide.";
                return null;
            }
            if ($coeff === null || $coeff === '') {
                $error = "Le coefficient de la sélection n°".($index+1)." n'est pas valide.";
                return null;
            }

            $coeff = (int) $coeff;
            
            if ($coeff < 1 || $coeff > 10) {
                $error = "Le coefficient de la sélection n°".($index+1)." n'est pas compris entre 1 et 10.";
                return null;
            }
        }

        return $value;
    }

    /**
     * Permet d'ajouter les sélections de compétences d'une offre
     * @param Offre $offre
     * @param array $selectionCompetences
     * @param CompetenceRepository $competenceRepository
     * @param EntityManagerInterface $entityManager
     * @param mixed $error
     * @return void
     */
    private function ajouterSelectionCompetences(
        Offre $offre,
        array $selectionCompetences,
        CompetenceRepository $competenceRepository,
        EntityManagerInterface $entityManager,
        ?string &$error
    )
    {
        // On parcourt le tableau pour cette fois-ci faire la validation en base de données
        foreach ($selectionCompetences as $index => $selectionCompetence) {
            $competenceID = $selectionCompetence['competence_id'];
            $coeff = $selectionCompetence['coeff'];

            // Vérification de l'éxistance de la compétence en base de données
            $competence = $competenceRepository->find($competenceID);
            if ($competence === null) {
                $error = "L'ID de la compétence de la sélection n°".($index+1)." n'existe pas en base de données.";
                return;
            }

            // Création de l'instance de la sélection de la compétence
            $selectionCompetence = (new SelectionCompetence())
                ->setCompetence($competence)
                ->setCoeffCompetence($coeff)
            ;

            // Enregistrement de l'instance de la sélection en mémoire, pour enregistrement en base de données
            $entityManager->persist($selectionCompetence);

            // Ajout de la sélection à l'offre
            $offre->addSelectionCompetence($selectionCompetence);
        }
    }

    private function parseStatutOffre(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le statut de l'offre est requis.";
            }
            return null;
        }

        // Pour la validation de l'enum, on utilise la méthode statique tryFrom()
        // qui va vérifier la présence du string dans l'enum
        $value = StatutOffre::tryFrom($value);
        // S'il n'y a aucun résultat, c'est que le statut de l'offre est invalide
        if (!$value) return null;

        return $value;
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
}