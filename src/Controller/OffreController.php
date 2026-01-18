<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Enum\CategorieOffre;
use App\Enum\NiveauEtude;
use App\Enum\StatutOffre;
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
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $data = $this->decodeJson($request);

        $categorieOffreError = null;
        $categorieOffre = $this->parseCategorieOffre((string) ($data["categorie_offre"] ?? null), true, $categorieOffreError);
        if ($categorieOffre === null) {
            return $this->errorResponse($categorieOffreError ?? "La catégorie de l'offre n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        $intituleError = null;
        $intitule = $this->parseIntitule((string) ($data["intitule"] ?? null), true, $intituleError);
        if ($intitule === null) {
            return $this->errorResponse($intituleError ?? "L'intitule n'est pas valide.", Response::HTTP_BAD_REQUEST);
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

        $niveauEtudessError = null;
        $niveauEtudess = $this->parseNiveauEtudes((string) ($data["niveau_etudes"] ?? null), true, $niveauEtudessError);
        if ($niveauEtudess === null) {
            return $this->errorResponse($niveauEtudessError ?? "Le niveau d'études n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        $coeffNiveauEtudesError = null;
        $coeffNiveauEtudes = $this->parseCoeffNiveauEtudes((string) ($data["coeff_niveau_etudes"] ?? null), true, $coeffNiveauEtudesError);
        if ($coeffNiveauEtudes === null) {
            return $this->errorResponse($coeffNiveauEtudesError ?? "Le coefficient du niveau d'études n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        // Le télétravail possible étant seulement un booléen, la vérification est beaucoup plus simple
        // On choisi ici de le mettre à true si on reçoit une valeur et qu'elle est à 1, sinon false
        $teletravailPossible = (bool) ($data["teletravail_possible"] ?? "0") === "1" ? true : false;

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

        // ==================== SOLUTION TEMPORAIRE ====================
        // On utilise par défaut afin que les contraintes relationnelles en base de données soient respectées,
        // à corriger plus tard en accord avec la partie Recruteur et Departement avec des Factory.
        $recruteur = $recruteurRepository->find(1);
        $departement = $departementRepository->find(1);
        if (!$recruteur || !$departement) {
            return $this->errorResponse(
                "Département ou entreprise par défaut introuvable.",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        // =============================================================

        $offre = (new Offre())
            ->setCategorieOffre($categorieOffre)
            ->setIntitule($intitule)
            ->setDescription($description)
            ->setSalaire($salaire)
            ->setNiveauEtudes($niveauEtudess)
            ->setCoeffNiveauEtudes($coeffNiveauEtudes)
            ->setTeletravailPossible($teletravailPossible)
            ->setCoeffDepartement($coeffdepartement)
            ->setDateDePublication($datePublication)
            ->setNombreDeVues($nombredevues)
            ->setRecruteur($recruteur)
            ->setDepartement($departement)
        ;
        // Le statut de l'offre est déjà défini par défaut comme étant "En attente"

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
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $offre = $offreRepository->find($id);

        if (!$offre) {
            return $this->errorResponse("Demande de contact introuvable.", Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse("Données dans le JSON body invalides.", Response::HTTP_BAD_REQUEST);
        }

        $categorieOffre = (string) ($data["categorie_offre"] ?? null);
        if ($categorieOffre !== null && $categorieOffre !== '') {
            $categorieOffreError = null;
            $categorieOffre = $this->parseCategorieOffre($categorieOffre, false, $categorieOffreError);
            if ($categorieOffre !== null) {
                $offre->setCategorieOffre($categorieOffre);
            }
            else {
                return $this->errorResponse($categorieOffreError ?? "La catégorie de l'offre n'est pas valide.", Response::HTTP_BAD_REQUEST);
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
                return $this->errorResponse($intituleError ?? "L'intitule n'est pas valide.", Response::HTTP_BAD_REQUEST);
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
                return $this->errorResponse($niveauEtudesError ?? "Le niveauetude n'est pas valide.", Response::HTTP_BAD_REQUEST);
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
                return $this->errorResponse($coeffNiveauEtudesError ?? "Le coeffniveauetude n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        // Pour l'update, on vérifie simplement la présence de l'attribut dans la requête
        if (isset($data["teletravail_possible"])) {
            // Si c'est le cas, on valide de la même façon
            $teletravailPossible = (bool) ($data["teletravail_possible"] ?? "0") === "1" ? true : false;
            // Puis on met à jour
            $offre->setTeletravailPossible($teletravailPossible);
        }

        $coeffdepartement = (string) ($data["coeff_departement"] ?? null);
        if ($coeffdepartement !== null && $coeffdepartement !== '') {
            $coeffdepartementError = null;
            $coeffdepartement = $this->parseCoeffDepartement($coeffdepartement, false, $coeffdepartementError);
            if ($coeffdepartement !== null) {
                $offre->setCoeffDepartement($coeffdepartement);
            }
            else {
                return $this->errorResponse($coeffdepartementError ?? "Le coeffdepartement n'est pas valide.", Response::HTTP_BAD_REQUEST);
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
            return $this->errorResponse("Demande de contact introuvable.", Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($offre);

        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeOffre(Offre $offre): array
    {
        return [
            "id" => $offre->getId(),
            "categorie_offre" => $offre->getCategorieOffre() ,
            "intitule" => $offre->getIntitule() ,
            "description" => $offre->getDescription() ,
            "salaire" => $offre->getSalaire() ,
            "niveau_etudes" => $offre->getNiveauEtudes() ,
            "coeff_niveau_etudes" => $offre->getCoeffNiveauEtudes() ,
            "teletravail_possible" => $offre->isTeletravailPossible() ,
            "coeff_departement" => $offre->getCoeffDepartement() ,
            "recruteur" => [
                "poste" => $offre->getRecruteur()->getPoste(),
                "email_pro" => $offre->getRecruteur()->getEmailPro(),
                "telephone_pro" => $offre->getRecruteur()->getTelephonePro(),
                "utilisateur" => [
                    "nom" => $offre->getRecruteur()->getUtilisateur()->getNom(),
                    "prenom" => $offre->getRecruteur()->getUtilisateur()->getPrenom(),
                    // ... à valider en groupe
                ],
                "entreprise" => [
                    "nom" => $offre->getRecruteur()->getEntreprise()->getNom(),
                    "siret" => $offre->getRecruteur()->getEntreprise()->getSiret(),
                    // ... à valider en groupe
                ],
            ],
            "date_de_publication" => $offre->getDateDePublication() ,
            "statut_offre" => $offre->getStatutOffre() ,
            "nombre_de_vues" => $offre->getNombreDeVues() ,
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

    private function parseCategorieOffre(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "La catégorie de l'offre est requise.";
            }
            return null;
        }

        // Conversion de la catégorie string en type CategorieOffre
        if ($value === "Informatique / Numérique") return CategorieOffre::INFORMATIQUE;
        if ($value === "Bâtiment") return CategorieOffre::BATIMENT;
        if ($value === "Recherche / Science") return CategorieOffre::SCIENCE;
        if ($value === "Industrie") return CategorieOffre::INDUSTRIE;
        if ($value === "Logistique / Transport") return CategorieOffre::LOGISTIQUE;
        if ($value === "Commerce / Vente") return CategorieOffre::COMMERCE;
        if ($value === "Communication / Marketing") return CategorieOffre::COMMUNICATION;
        if ($value === "Gestion / Finance") return CategorieOffre::FINANCE;
        if ($value === "Ressources humaines") return CategorieOffre::RH;
        if ($value === "Droit") return CategorieOffre::DROIT;
        if ($value === "Design") return CategorieOffre::DESIGN;
        if ($value === "Santé / Social") return CategorieOffre::SANTE;
        if ($value === "Hôtellerie / Restauration / Tourisme") return CategorieOffre::HOTELLERIE;
        if ($value === "Agriculture") return CategorieOffre::AGRICULTURE;
        if ($value === "Artisanat") return CategorieOffre::ARTISANAT;

        return null;
    }

    private function parseIntitule(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "L'intitule est requis.";
            }
            return null;
        }

        if (mb_strlen($value) > 150) {
            $error = "L'intitule ne peut pas dépasser 150 caractères.";
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

        // Conversion du niveau d'études string en type NiveauEtude
        if ($value === "Sans diplôme") return NiveauEtude::SANS_DIPLOME;
        if ($value === "BEP/CAP") return NiveauEtude::BEP_CAP;
        if ($value === "BAC") return NiveauEtude::BAC;
        if ($value === "BAC +2 - BTS/DUT") return NiveauEtude::BAC_2;
        if ($value === "BAC +3 - Licence") return NiveauEtude::BAC_3;
        if ($value === "BAC +5 - Master") return NiveauEtude::BAC_5;

        return null;
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

    private function parseStatutOffre(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le statut de l'offre est requis.";
            }
            return null;
        }

        // Conversion du statut de l'offre string en type StatutOffre
        if ($value === "En attente") return StatutOffre::EN_ATTENTE;
        if ($value === "Accepté") return StatutOffre::ACCEPTE;
        if ($value === "Refusé") return StatutOffre::REFUSE;

        return null;
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
}