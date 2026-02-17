<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Enum\DomaineActivite;
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

    #[Route('/{id}', name: 'api_offre_put_item', methods: ['PUT','PATCH'])]
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

        $isPut = $request->getMethod() === 'PUT';

        if (array_key_exists('categorie_offre', $data) || $isPut) {
            $categorieOffreError = null;
            $categorieOffre = $this->parseDomaineActivite((string) ($data["categorie_offre"] ?? null), true, $categorieOffreError);
            if ($categorieOffre === null) {
                return $this->errorResponse($categorieOffreError ?? "Le domaine d'activité de l'offre n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $offre->setCategorieOffre($categorieOffre);
        }

        if (array_key_exists('intitule', $data) || $isPut) {
            $intituleError = null;
            $intitule = $this->parseIntitule((string) ($data["intitule"] ?? null), true, $intituleError);
            if ($intitule === null) {
                return $this->errorResponse($intituleError ?? "L'intitulé n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $offre->setIntitule($intitule);
        }

        if (array_key_exists('description', $data) || $isPut) {
            $descriptionError = null;
            $description = $this->parseDescription((string) ($data["description"] ?? null), true, $descriptionError);
            if ($description === null) {
                return $this->errorResponse($descriptionError ?? "La description n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $offre->setDescription($description);
        }

        if (array_key_exists('salaire', $data) || $isPut) {
            $salaireError = null;
            $salaire = $this->parseSalaire((string) ($data["salaire"] ?? null), true, $salaireError);
            if ($salaire === null) {
                return $this->errorResponse($salaireError ?? "Le salaire n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $offre->setSalaire($salaire);
        }

        if (array_key_exists('niveau_etudes', $data) || $isPut) {
            $niveauEtudesError = null;
            $niveauEtudes = $this->parseNiveauEtudes((string) ($data["niveau_etudes"] ?? null), true, $niveauEtudesError);
            if ($niveauEtudes === null) {
                return $this->errorResponse($niveauEtudesError ?? "Le niveau d'études n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $offre->setNiveauEtudes($niveauEtudes);
        }

        if (array_key_exists('coeff_niveau_etudes', $data) || $isPut) {
            $coeffNiveauEtudesError = null;
            $coeffNiveauEtudes = $this->parseCoeffNiveauEtudes((string) ($data["coeff_niveau_etudes"] ?? null), true, $coeffNiveauEtudesError);
            if ($coeffNiveauEtudes === null) {
                return $this->errorResponse($coeffNiveauEtudesError ?? "Le coefficient du niveau d'études n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $offre->setCoeffNiveauEtude($coeffNiveauEtudes);
        }

        // Pour l'update, on vérifie simplement la présence de l'attribut dans la requête
        if (array_key_exists('teletravail_possible', $data) || $isPut) {
            // Si c'est le cas, on valide de la même façon
            if (!is_bool($data["teletravail_possible"])) {
                return $this->errorResponse("Le champ teletravail_possible doit être un booléen.", Response::HTTP_BAD_REQUEST);
            }
            // Puis on met à jour
            $teletravailPossible = $data["teletravail_possible"];
            $offre->setTeletravailPossible($teletravailPossible);
        }

        if (array_key_exists('coeff_departement', $data) || $isPut) {
            $coeffdepartementError = null;
            $coeffdepartement = $this->parseCoeffDepartement((string) ($data["coeff_departement"] ?? null), true, $coeffdepartementError);
            if ($coeffdepartement === null) {
                return $this->errorResponse($coeffdepartementError ?? "Le coefficient du département n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $offre->setCoeffDepartement($coeffdepartement);
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

        // La mise à jour du statut de l'offre ne peut se faire qu'en PATCH
        if (array_key_exists('statut_offre', $data) && !$isPut) {
            $statutError = null;
            $statutOffre = $this->parseStatutOffre((string) ($data["statut_offre"] ?? null), true, $statutError);
            if ($statutOffre === null) {
                return $this->errorResponse($statutError ?? "Le statut de l'offre n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $offre->setStatutOffre($statutOffre);
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