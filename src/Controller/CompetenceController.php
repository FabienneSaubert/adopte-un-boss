<?php

namespace App\Controller;

use App\Entity\Competence;
use App\Enum\CategorieCompetence;
use App\Form\CompetenceType;
use App\Repository\CompetenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/competence')]
final class CompetenceController extends AbstractController
{

//! ======================================== LISTE DES COMPETENCES ==============================================================================================
    #[Route(name: 'app_competence_index', methods: ['GET'])]
    public function index(CompetenceRepository $competenceRepository): JsonResponse
    {
        $competences = array_map(
        fn(Competence $competence) => $this->serialiserCompetence($competence),
        // transforme chaque departement en index de tableau grâce à la méthode serialiserDepartement()

        $competenceRepository->findAll()
        // récupère tous les departement en base de données
        );
        return $this->json($competences);
        // renvoie la réponse au format JSON
    }
//! ============================== CREER UNE NOUVELLE COMPETENCE ==============================================================================================

    #[Route('/new', name: 'app_competence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->decodeJson($request);
        // lit le JSON envoyé par le client

        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $nom = trim((string) ($data["nom"] ?? ""));

        $categorie = CategorieCompetence::tryFrom($data['categorie_competence']);
        if (!$categorie) {
            return $this->errorResponse('Categorie de competence est invalide.',Response::HTTP_BAD_REQUEST);
        }

        $competence = (new Competence())
        ->setNom($nom)
        ->setCategorie($categorie); 
    

        $entityManager->persist($competence);
        // prépare l’insertion en base

        $entityManager->flush();
        // exécute l’insertion

        return $this->json($this->serialiserCompetence($competence), Response::HTTP_CREATED);
        // renvoie le code HTTP 201 (création réussie)
    }

//! =========================================== AFFICHER UNE COMPETENCE ==============================================================================================
    #[Route('/{id}', name: 'app_competence_show', methods: ['GET'])]
    public function show(int $id, CompetenceRepository $competenceRepository): JsonResponse
    {
        $competence = $competenceRepository->find($id);
        
        if (!$competence) {
            return $this->errorResponse('Compétence non trouvé.', Response::HTTP_NOT_FOUND);
        }

       return $this->json($this->serialiserCompetence($competence));
    }

//! ========================================= MODIFIER UNE COMPETENCE ==============================================================================================
    #[Route('/{id}/edit', name: 'app_competence_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, CompetenceRepository $competenceRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $competence = $competenceRepository->find($id);
        if (!$competence) {
            return $this->errorResponse('Compétence non trouvé.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request ->getMethod() === "PUT";

        if (array_key_exists('nom', $data) || $isPut) {
            $nom = trim((string) ($data['nom'] ?? ''));
            if ($nom === '') {
                return $this->errorResponse('Le nom est obligatoire.', Response::HTTP_BAD_REQUEST);
            }
            $competence->setNom($nom);
        }

         if (array_key_exists('categorie', $data) || $isPut) {
            $categorie = trim((string) ($data['categorie'] ?? ''));
            if ($categorie === '') {
                return $this->errorResponse('La catégorie est obligatoire.', Response::HTTP_BAD_REQUEST);
            }
            $competence->setCategorie($categorie);
        }

        $entityManager->flush();
        // Enregistre les modifications en base de données

        return $this->json($this->serialiserCompetence($competence));
    }

//! ==================================== SUPPRIMER UNE COMPETENCE ==============================================================================================
    #[Route('/{id}', name: 'app_competence_delete', methods: ['POST'])]
    public function delete(int $id, CompetenceRepository $competenceRepository, EntityManagerInterface $entityManager): JsonResponse
    {
     $competence = $competenceRepository->find($id);

        if (!$competence) {
            return $this->errorResponse('Compétence non trouvé.', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($competence);
        // prépare la suppression

        $entityManager->flush();
        // exécute la suppression

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
//! ================================ TRANSFORMATION D'UN OBJET Competence EN TABLEAU SIMPLE POUR JSON ==============================================================================================

    private function serialiserCompetence(Competence $competence): array
    {
        return [
        // On renvoie un tableau PHP

            "id"=>$competence->getId(),

            "nom"=>$competence->getNom(),

            "categorie"=>$competence->getCategorie()->value

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


