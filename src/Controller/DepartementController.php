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
        $departemets = array_map(
            fn(Departement $departement) => $this->serialiserDepartement($departement),
            // transforme chaque departement en index de tableau grâce à la méthode serialiserDepartement()

            $departementRepository->findAll()
            // récupère tous les departement en base de données
        );
        return $this->json($departemets);
        // renvoie la réponse au format JSON
    }

//! ============================== CREER UN NOUVEAU DEPARTEMENT ==============================================================================================

    #[Route('/new', name: 'app_departement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
     $data = $this->decodeJson($request);
        // lit le JSON envoyé par le client

        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $nom = trim((string) ($data["nom"] ?? ""));

        $numero = $data['numero'];

        $departement = (new Departement())
        ->setNom($nom)
        ->setNumero($numero);
    

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
        
        if (!$departement) {
            return $this->errorResponse('Département non trouvé.', Response::HTTP_NOT_FOUND);
        }

       return $this->json($this->serialiserDepartement($departement));
    }

//! ========================================= MODIFIER UN DEPARTEMENT ==============================================================================================


    #[Route('/{id}/edit', name: 'app_departement_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request,DepartementRepository $departementRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $departement = $departementRepository->find($id);
        if (!$departement) {
            return $this->errorResponse('Département non trouvé.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request ->getMethod() === "PUT";

        //?? ====================================== NOM =====================================================
        if (array_key_exists('nom', $data) || $isPut) {
            $nom = trim((string) ($data['nom'] ?? ''));
            if ($nom === '') {
                return $this->errorResponse('Le nom est obligatoire.', Response::HTTP_BAD_REQUEST);
            }
            $departement->setNom($nom);
        }

                     //!  ????????????????????????? NUMERO ????????????????
        //?? ====================================== NUMERO =====================================================
        if (array_key_exists('numero', $data) || $isPut) {
            $numero = trim((string) ($data['numero'] ?? ''));
            if ($numero === '') {
                return $this->errorResponse('Le numéro est obligatoire.', Response::HTTP_BAD_REQUEST);
            }
            $departement->setNumero($numero);
        }

        $entityManager->flush();
        // Enregistre les modifications en base de données

        return $this->json($this->serialiserDepartement($departement));
    }

//! ==================================== SUPPRIMER UN DEPARTEMENT ==============================================================================================

    #[Route('/{id}', name: 'app_departement_delete', methods: ['POST'])]
    public function delete(int $id, DepartementRepository $departementRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $departement = $departementRepository->find($id);

        if (!$departement) {
            return $this->errorResponse('Département non trouvé.', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($departement);
        // prépare la suppression

        $entityManager->flush();
        // exécute la suppression

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

//! ================================ TRANSFORMATION D'UN OBJET Departement EN TABLEAU SIMPLE POUR JSON ==============================================================================================

    private function serialiserDepartement(Departement $departement): array
    {
        return [
        // On renvoie un tableau PHP

            "id"=>$departement->getId(),

            "nom"=>$departement->getNom(),

            "numero"=>$departement->getNumero()

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
