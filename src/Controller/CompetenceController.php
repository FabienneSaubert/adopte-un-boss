<?php

namespace App\Controller;

use App\Entity\Competence;
use App\Enum\TypeCompetence;
use App\Enum\DomaineActivite;
use App\Repository\CompetenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/competence')]
final class CompetenceController extends AbstractController
{

//! ======================================== LISTE DES COMPETENCES ==============================================================================================
    #[Route(name: 'api_competence_get_collection', methods: ['GET'])]
    public function index(CompetenceRepository $competenceRepository): JsonResponse
    {
        $competences = array_map(
        fn(Competence $competence) => $this->serialiserCompetence($competence),
        // transforme chaque competence en index de tableau grâce à la méthode serialiserCompetence()

        $competenceRepository->findAll()
        // récupère tous les compétences en base de données
        );
        return $this->json($competences);
        // Retourne la liste des compétences au format JSON
    }
//! ============================== CREER UNE NOUVELLE COMPETENCE ==============================================================================================

    #[Route('', name: 'api_competence_post_collection', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->decodeJson($request);
        // Lecture et décodage du JSON envoyé par le client

        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
            // JSON invalide ou mal formé
        }

        $nom = trim((string) ($data["nom"] ?? ''));
        // Récupération du nom de la compétence
        if ($nom === '') {
            return $this->errorResponse('Le nom est obligatoire.', Response::HTTP_BAD_REQUEST);
            // Le nom ne peut pas être vide
        }

        $type = TypeCompetence::tryFrom($data['type'] ?? null);
        if (!$type) {
            return $this->errorResponse('Type de compétence invalide.', Response::HTTP_BAD_REQUEST);
        }

        // Le domaine est optionnel (nullable)
        $domaineActivite = null;
        if (isset($data['domaine'])) {
            $domaineActivite = DomaineActivite::tryFrom($data['domaine']);
            if (!$domaineActivite) {
                return $this->errorResponse('Domaine d\'activité invalide.', Response::HTTP_BAD_REQUEST);
            }
        }

        $competence = (new Competence())
        ->setNom($nom)
            ->setType($type)
            ->setDomaine($domaineActivite);
    

        $entityManager->persist($competence);
        // prépare l’insertion en base

        $entityManager->flush();
        // exécute l’insertion

        return $this->json($this->serialiserCompetence($competence), Response::HTTP_CREATED);
        // Retourne la compétence créée avec le code HTTP 201
    }

//! =========================================== AFFICHER UNE COMPETENCE ==============================================================================================
    #[Route('/{id}', name: 'api_competence_get_item', methods: ['GET'])]
    public function show(int $id, CompetenceRepository $competenceRepository): JsonResponse
    {
        $competence = $competenceRepository->find($id);
        // Recherche de la compétence par son identifiant
        
        if (!$competence) {
            return $this->errorResponse('Compétence non trouvée.', Response::HTTP_NOT_FOUND);
            // Aucune compétence trouvée → erreur 404
        }

       return $this->json($this->serialiserCompetence($competence));
       // Retourne la compétence trouvée
    }

//! ========================================= MODIFIER UNE COMPETENCE ==============================================================================================
    #[Route('/{id}', name: 'api_competence_put_item', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, CompetenceRepository $competenceRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $competence = $competenceRepository->find($id);
        // Recherche de la compétence à modifier

        if (!$competence) {
            return $this->errorResponse('Compétence non trouvé.', Response::HTTP_NOT_FOUND);
            // Compétence inexistante → erreur 404
        }

        $data = $this->decodeJson($request);
        // Lecture et décodage du JSON
        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
            // JSON invalide
        }

        $isPut = $request ->getMethod() === "PUT";
        // PUT → tous les champs obligatoires
        // PATCH → seulement les champs fournis

        //? ====================================== NOM =====================================================
        if (array_key_exists('nom', $data) || $isPut) {
            $nom = trim((string) ($data['nom'] ?? ''));
            if ($nom === '') {
                return $this->errorResponse('Le nom est obligatoire.', Response::HTTP_BAD_REQUEST);
                // En PUT ou si présent → le nom ne peut pas être vide
            }
            $competence->setNom($nom);
            // Mise à jour du nom
        }

        //? ====================================== TYPE =====================================================
        if (array_key_exists('type', $data) || $isPut) {
            $type = TypeCompetence::tryFrom($data['type'] ?? null);
            if (!$type) {
                return $this->errorResponse('Type de compétence invalide.', Response::HTTP_BAD_REQUEST);
            }
            $competence->setType($type);
        }

        //? ====================================== DOMAINE =====================================================
        if (array_key_exists('domaine', $data)) {
            $domaineActivite = null;
            if ($data['domaine'] !== null) {
                $domaineActivite = DomaineActivite::tryFrom($data['domaine']);
                if (!$domaineActivite) {
                    return $this->errorResponse('Domaine d\'activité invalide.', Response::HTTP_BAD_REQUEST);
                }
            }
            $competence->setDomaine($domaineActivite);
        }

        $entityManager->flush();
        // Enregistre les modifications en base

        return $this->json($this->serialiserCompetence($competence));
        // Retourne la compétence mise à jour
    }

//! ==================================== SUPPRIMER UNE COMPETENCE ==============================================================================================
    #[Route('/{id}', name: 'api_competence_delete_item', methods: ['DELETE'])]
    
    public function delete(int $id, CompetenceRepository $competenceRepository, EntityManagerInterface $entityManager): JsonResponse
    {
     $competence = $competenceRepository->find($id);
     // Recherche de la compétence à supprimer

        if (!$competence) {
            return $this->errorResponse('Compétence non trouvé.', Response::HTTP_NOT_FOUND);
            // Si la compétence n’existe pas → erreur 404
        }

        $entityManager->remove($competence);
        // prépare la suppression

        $entityManager->flush();
        // exécute la suppression

        return $this->json(null, Response::HTTP_NO_CONTENT);
        // Retourne un code 204 (suppression réussie)
    }

//! ================================ TRANSFORMATION D'UN OBJET Competence EN TABLEAU SIMPLE POUR JSON ==============================================================================================

    private function serialiserCompetence(Competence $competence): array
    {
        return [
        // On renvoie un tableau PHP

            "id"=>$competence->getId(),
            // Identifiant de la compétence

            "nom"=>$competence->getNom(),
            // Nom de la compétence

            "type" => $competence->getType()?->value,
            // Valeur string de l’ENUM

            "domaine"=>$competence->getDomaine()->value
            // Valeur string de l’ENUM

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


