<?php

namespace App\Controller;

use App\Entity\Departement;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/departement')]
final class DepartementController extends AbstractController
{

//! ======================================== LISTE DES DEPARTEMENTS ==============================================================================================

    #[Route(name: 'app_departement_index', methods: ['GET'])]
    public function index(DepartementRepository $departementRepository): JsonResponse
    {
        $departements = array_map(
            fn(Departement $departement) => $this->serialiserDepartement($departement),
            // transforme chaque departement en index de tableau grâce à la méthode serialiserDepartement()

            $departementRepository->findAll()
            // récupère tous les departements en base de données
        );
        return $this->json($departements);
        // Retourne la liste des départements au format JSON
    }

//! ============================== CREER UN NOUVEAU DEPARTEMENT ==============================================================================================

    #[Route('/new', name: 'app_departement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
     $data = $this->decodeJson($request);
        // lit le JSON envoyé par le client

        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
            // Si le JSON est invalide ou mal formé → erreur 400
        }

        $nom = trim((string) ($data["nom"] ?? ''));
        // Récupère le nom, le force en string et supprime les espaces inutiles
        if ($nom === '') {
            return $this->errorResponse('Le nom est obligatoire.', Response::HTTP_BAD_REQUEST);
        // Le nom ne peut pas être vide
        }

        $numero = strtoupper(trim((string) ($data['numero'] ?? '')));
        // Récupère le numéro, le force en string, enlève les espaces
        // et met en majuscules (utile pour 2A / 2B)
        if ($numero === '') {
                return $this->errorResponse('Le numéro est obligatoire.', Response::HTTP_BAD_REQUEST);
        // Le numéro est obligatoire
        }

            if (!preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5]|2A|2B|97[1-6])$/', $numero)) {
                return $this->errorResponse('Numéro de département invalide.', Response::HTTP_BAD_REQUEST);
            // Vérifie que le numéro correspond à un département français valide
            }
           

        $departement = (new Departement())
        ->setNom($nom)
        ->setNumero($numero);
        // Création de l'entité Departement et hydratation des propriétés
    

        $entityManager->persist($departement);
        // prépare l’insertion en base

        $entityManager->flush();
        // exécute l’insertion

        return $this->json($this->serialiserDepartement($departement), Response::HTTP_CREATED);
        // renvoie le code HTTP 201 (création réussie)
    }

//! =========================================== AFFICHER UN DEPARTEMENT ==============================================================================================


    #[Route('/{id}', name: 'app_departement_show', methods: ['GET'])]
    public function show(int $id, DepartementRepository $departementRepository): JsonResponse
    {
       $departement = $departementRepository->find($id);
       // Recherche du département par son identifiant
        
        if (!$departement) {
            return $this->errorResponse('Département non trouvé.', Response::HTTP_NOT_FOUND);
        // Si aucun département trouvé → erreur 404
        }

       return $this->json($this->serialiserDepartement($departement));
       // Retourne le département trouvé au format JSON
    }

//! ========================================= MODIFIER UN DEPARTEMENT ==============================================================================================


    #[Route('/{id}/edit', name: 'app_departement_edit', methods: ['PUT', 'PATCH'])]
    public function edit(int $id, Request $request,DepartementRepository $departementRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $departement = $departementRepository->find($id);
        // Recherche du département à modifier

        if (!$departement) {
            return $this->errorResponse('Département non trouvé.', Response::HTTP_NOT_FOUND);
        // Si le département n'existe pas → erreur 404
        }

        $data = $this->decodeJson($request);
        // Lecture et décodage du JSON envoyé par le client
        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request ->getMethod() === "PUT";
        // PUT → tous les champs sont obligatoires
        // PATCH → seulement les champs présents sont modifiés

        //?? ====================================== NOM =====================================================
        if (array_key_exists('nom', $data) || $isPut) {
            $nom = trim((string) ($data['nom'] ?? ''));
            if ($nom === '') {
                return $this->errorResponse('Le nom est obligatoire.', Response::HTTP_BAD_REQUEST);
                // En PUT ou si le champ est fourni → le nom ne peut pas être vide
            }
            $departement->setNom($nom);
            // Mise à jour du nom
        }


        //?? ====================================== NUMERO =====================================================
        if (array_key_exists('numero', $data) || $isPut) {

            $numero = strtoupper(trim((string) ($data['numero'] ?? '')));
            if ($numero === '') {
                return $this->errorResponse('Le numéro est obligatoire.', Response::HTTP_BAD_REQUEST);
                // Le numéro est obligatoire en PUT ou s’il est fourni
            }

            if (!preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5]|2A|2B|97[1-6])$/', $numero)) {
                return $this->errorResponse('Numéro de département invalide.', Response::HTTP_BAD_REQUEST);
                // Vérification du format du numéro
            }
            $departement->setNumero($numero);
            // Mise à jour du numéro
        }

        $entityManager->flush();
        // Enregistre les modifications en base de données

        return $this->json($this->serialiserDepartement($departement));
        // Retourne le département mis à jour
    }

//! ==================================== SUPPRIMER UN DEPARTEMENT ==============================================================================================

    #[Route('/{id}', name: 'app_departement_delete', methods: ['DELETE'])]
    public function delete(int $id, DepartementRepository $departementRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $departement = $departementRepository->find($id);
        // Recherche du département à supprimer

        if (!$departement) {
            return $this->errorResponse('Département non trouvé.', Response::HTTP_NOT_FOUND);
            // Si le département n’existe pas → erreur 404
        }

        $entityManager->remove($departement);
        // prépare la suppression de l'entité

        $entityManager->flush();
        // exécute la suppression en base de données

        return $this->json(null, Response::HTTP_NO_CONTENT);
        // Retourne un code HTTP 204 (suppression réussie, pas de contenu)
    }

//! ================================ TRANSFORMATION D'UN OBJET Departement EN TABLEAU SIMPLE POUR JSON ==============================================================================================

    private function serialiserDepartement(Departement $departement): array
    {
        return [
        // On renvoie un tableau PHP

            "id"=>$departement->getId(),
            // Identifiant du département

            "nom"=>$departement->getNom(),
            // Nom du département

            "numero"=>$departement->getNumero()
            // Numéro du département

        ];
        // Le tableau est retourné
    }


//! ============================= LECTURE DU CONTENU JSON ENVOYE PAR LE CLIENT ET TRANSFORMATION EN TABLEAU ==============================================================================================
    private function decodeJson(Request $request): ?array
    // private → utilisable seulement dans ce contrôleur
    // decodeJson → lit du JSON
    // Request $request → la requête envoyée par le client
    // : ?array → retourne un tableau ou null
    
    {
        $payload = json_decode($request->getContent(), true);
        // $request->getContent() → récupère le contenu brut (JSON)
        // json_decode(..., true) → transforme le JSON en tableau PHP
        // Le résultat est stocké dans $payload
 
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
        // Vérifie deux choses : le résultat est bien un tableau
        //                       le JSON ne contient aucune erreur
            return null;
            // Si le JSON est invalide → on retourne null
            // Le contrôleur saura que les données sont mauvaises
        }
 
        return $payload;
        // Si tout est correct → on retourne le tableau
    }


//! ================================= RENVOYER DES ERREURS EN JSON AVEC UN CODE HTTP ==============================================================================================
    private function errorResponse(string $message, int $status): JsonResponse
    // private → utilisable seulement dans ce contrôleur
    // errorResponse → sert à renvoyer une erreur
    // $message → le texte de l’erreur
    // $status → le code HTTP (400, 404, 500…)
    // JsonResponse → la réponse est en JSON

    {
        return $this->json(['erreur' => $message], $status);
        // $this->json(...) → méthode Symfony pour créer une réponse JSON
        // ['error' => $message] → contenu JSON envoyé au client
        // $status → code HTTP de la réponse
    }

}
