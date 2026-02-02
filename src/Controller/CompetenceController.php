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

#[Route('/api/domaine')]
final class CompetenceController extends AbstractController
{

//! ======================================== LISTE DES DOMAINES ==============================================================================================
    #[Route(name: 'api_domaine_get_collection', methods: ['GET'])]
    public function index(CompetenceRepository $domaineRepository): JsonResponse
    {
        $domaines = array_map(
            fn(Competence $domaine) => $this->serialiserDomaine($domaine),
            $domaineRepository->findAll()
        );
        
        return $this->json($domaines);
    }

//! ============================== CREER UN NOUVEAU DOMAINE ==============================================================================================

    #[Route('', name: 'api_domaine_post_collection', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->decodeJson($request);

        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $nom = trim((string) ($data["nom"] ?? ''));
        if ($nom === '') {
            return $this->errorResponse('Le nom est obligatoire.', Response::HTTP_BAD_REQUEST);
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

        $domaine = (new Competence())
            ->setNom($nom)
            ->setType($type)
            ->setDomaine($domaineActivite);

        $entityManager->persist($domaine);
        $entityManager->flush();

        return $this->json($this->serialiserDomaine($domaine), Response::HTTP_CREATED);
    }

//! =========================================== AFFICHER UN DOMAINE ==============================================================================================
    #[Route('/{id}', name: 'api_domaine_get_item', methods: ['GET'])]
    public function show(int $id, CompetenceRepository $domaineRepository): JsonResponse
    {
        $domaine = $domaineRepository->find($id);
        
        if (!$domaine) {
            return $this->errorResponse('Domaine non trouvé.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serialiserDomaine($domaine));
    }

//! ========================================= MODIFIER UN DOMAINE ==============================================================================================
    #[Route('/{id}', name: 'api_domaine_put_item', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, CompetenceRepository $domaineRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $domaine = $domaineRepository->find($id);

        if (!$domaine) {
            return $this->errorResponse('Domaine non trouvé.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request->getMethod() === "PUT";

        //? ====================================== NOM =====================================================
        if (array_key_exists('nom', $data) || $isPut) {
            $nom = trim((string) ($data['nom'] ?? ''));
            if ($nom === '') {
                return $this->errorResponse('Le nom est obligatoire.', Response::HTTP_BAD_REQUEST);
            }
            $domaine->setNom($nom);
        }

        //? ====================================== TYPE =====================================================
        if (array_key_exists('type', $data) || $isPut) {
            $type = TypeCompetence::tryFrom($data['type'] ?? null);
            if (!$type) {
                return $this->errorResponse('Type de compétence invalide.', Response::HTTP_BAD_REQUEST);
            }
            $domaine->setType($type);
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
            $domaine->setDomaine($domaineActivite);
        }

        $entityManager->flush();

        return $this->json($this->serialiserDomaine($domaine));
    }

//! ==================================== SUPPRIMER UN DOMAINE ==============================================================================================
    #[Route('/{id}', name: 'api_domaine_delete_item', methods: ['DELETE'])]
    public function delete(int $id, CompetenceRepository $domaineRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $domaine = $domaineRepository->find($id);

        if (!$domaine) {
            return $this->errorResponse('Domaine non trouvé.', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($domaine);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

//! ================================ TRANSFORMATION D'UN OBJET Domaine EN TABLEAU SIMPLE POUR JSON ==============================================================================================

    private function serialiserDomaine(Competence $domaine): array
    {
        return [
            "id" => $domaine->getId(),
            "type" => $domaine->getType()?->value,
            "domaine" => $domaine->getDomaine()?->value,
            "nom" => $domaine->getNom()
        ];
    }

//! ============================= LECTURE DU CONTENU JSON ENVOYE PAR LE CLIENT ET TRANSFORMATION EN TABLEAU ==============================================================================================
    private function decodeJson(Request $request): ?array
    {
        $payload = json_decode($request->getContent(), true);
 
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
 
        return $payload;
    }

//! ================================= RENVOYER DES ERREURS EN JSON AVEC UN CODE HTTP ==============================================================================================
    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['erreur' => $message], $status);
    }
}