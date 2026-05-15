<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Candidature;
use App\Repository\CandidatureRepository;
use App\Repository\CandidatRepository;
use App\Repository\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/candidature')]
final class CandidatureController extends AbstractController
{
    #[Route('', name: 'api_candidature_get_collection', methods: ['GET'])]
    public function list(
        Request $request,
        CandidatureRepository $candidatureRepository,
        OffreRepository $offreRepository
    ): JsonResponse
    {
        // Récupération du paramètre query de l'offre, par défaut invalide à 0
        $offreId = (int) $request->query->get('offreId', 0);

        // Si l'offre ID est à 0 (donc invalide)
        if ($offreId === 0) {
            return $this->errorResponse("Candidatures introuvables : ID de l'offre invalide.", Response::HTTP_BAD_REQUEST);
        }

        // Récupération de l'offre en base de données
        $offre = $offreRepository->find($offreId);

        // Vérification de l'existence de l'offre
        if ($offre === null) {
            return $this->errorResponse("Candidatures introuvables : l'offre est introuvable en base de données.", Response::HTTP_BAD_REQUEST);
        }

        // Récupération du tableau d'objet "Candidatures" ezt serialisation pour chaque index du tableau
        $candidatures = array_map(
            fn(Candidature $candidature) => $this->serializeCandidature($candidature),
            $candidatureRepository->findBy(['offre' => $offre])
        );

        // Retourne la lsite des objets "Candidatures" formatée
        return $this->json($candidatures);
    }

    #[Route('/me', name: 'api_candidature_get_collection_me', methods: ['GET'])]
    public function getMyCandidat(CandidatureRepository $candidatureRepository): JsonResponse
    {
        $candidat = $this->getCandidatConnecte();

        if (!$candidat) {
            return $this->errorResponse('Candidat non connecté ou introuvable', Response::HTTP_UNAUTHORIZED);
        }

        $candidatures = array_map(
            fn(Candidature $candidature) => $this->serializeCandidature($candidature),
            $candidatureRepository->findBy(['candidat' => $candidat])
        );

        return $this->json($candidatures);
    }

    #[Route('/me', name: 'api_candidature_post_collection_me', methods: ['POST'])]
    #[Route('', name: 'api_candidature_post_collection', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CandidatRepository $candidatRepository,
        CandidatureRepository $candidatureRepository,
        OffreRepository $offreRepository
    ): JsonResponse {
        $data = $this->decodeJson($request);

        if (!$data) {
            return $this->errorResponse('JSON invalide', Response::HTTP_BAD_REQUEST);
        }

        // Recupération du candidat connecté
        $candidat = $this->getCandidatConnecte();

        // Si le candidat n'a pas pu être recupéré, c'est qu'il n'est pas connecté
        if (!$candidat) {
            // On valide donc le champ 'candidat_id' dans le JSON de la requête

            // Validation de l'ID du candidat transmis
            if (!isset($data['candidat_id'])) {
                return $this->errorResponse("Le champs 'candidat_id' est requis", Response::HTTP_BAD_REQUEST);
            }

            // Récupération du candidat
            $candidat = $candidatRepository->find($data['candidat_id']);

            // Si le candidat n'a toujours pas pu être obtenu
            if (!$candidat) {
                // Il est impossible de créer une candidature, on renvoi un message d'erreur
                return $this->errorResponse('Candidat introuvable', Response::HTTP_NOT_FOUND);
            }
        }

        // Récupération de l'offre
        if (!isset($data['offre_id'])) {
            return $this->errorResponse("Le champs 'offre_id' est requis", Response::HTTP_BAD_REQUEST);
        }
        $offre = $offreRepository->find($data['offre_id']);
        if (!$offre) {
            return $this->errorResponse('Offre introuvable', Response::HTTP_NOT_FOUND);
        }

        // Vérifier si le candidat n'a pas déjà postulé à cette offre
        $candidatureExistante = $candidatureRepository->findOneBy([
            'candidat' => $candidat,
            'offre' => $offre
        ]);

        if ($candidatureExistante) {
            return $this->errorResponse(
                'Ce candidat a déjà postulé à cette offre',
                Response::HTTP_CONFLICT
            );
        }

        // Date d'envoi = date actuelle par défaut
        $dateEnvoi = new \DateTimeImmutable();

        // Si une date personnalisée est fournie
        // if (isset($data['date_envoi'])) {
        //     try {
        //         $dateEnvoi = new \DateTimeImmutable($data['date_envoi']);
        //     } catch (\Exception $e) {
        //         return $this->errorResponse('Format invalide', Response::HTTP_BAD_REQUEST);
        //     }
        // }

        // Création de la candidature
        $candidature = (new Candidature())
            ->setMessage($data['message'] ?? null)
            ->setDateEnvoi($dateEnvoi)
            ->setCandidat($candidat)
            ->setOffre($offre);

        $entityManager->persist($candidature);
        $entityManager->flush();

        return $this->json($this->serializeCandidature($candidature), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_candidature_get_item', methods: ['GET'])]
    public function show(int $id, CandidatureRepository $candidatureRepository): JsonResponse
    {
        $candidature = $candidatureRepository->find($id);

        if (!$candidature) {
            return $this->errorResponse('Candidature introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeCandidature($candidature));
    }

    #[Route('/{id}', name: 'api_candidature_put_item', methods: ['PATCH', 'PUT'])]
    public function edit(
        int $id,
        Request $request,
        CandidatureRepository $candidatureRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $candidature = $candidatureRepository->find($id);

        if (!$candidature) {
            return $this->errorResponse('Candidature introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);

        if (!$data) {
            return $this->errorResponse('JSON invalide', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request->getMethod() === 'PUT';

        // Mise à jour du message (optionnel)
        if (array_key_exists('message', $data)) {
            $candidature->setMessage($data['message']);
        }

        // Mise à jour de la date d'envoi (optionnel, peu commun)
        if (array_key_exists('date_envoi', $data)) {
            try {
                $dateEnvoi = new \DateTimeImmutable($data['date_envoi']);
                $candidature->setDateEnvoi($dateEnvoi);
            } catch (\Exception $e) {
                return $this->errorResponse('Format invalide', Response::HTTP_BAD_REQUEST);
            }
        }

        $entityManager->flush();

        return $this->json($this->serializeCandidature($candidature));
    }

    #[Route('/{id}', name: 'api_candidature_delete_item', methods: ['DELETE'])]
    public function delete(
        int $id,
        CandidatureRepository $candidatureRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $candidature = $candidatureRepository->find($id);

        if (!$candidature) {
            return $this->errorResponse('Candidature introuvable', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($candidature);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // Récupérer toutes les candidatures d'un candidat
    #[Route('/candidat/{candidatId}', name: 'api_candidature_by_candidat', methods: ['GET'])]
    public function getByCandidatId(int $candidatId, CandidatureRepository $candidatureRepository): JsonResponse
    {
        $candidatures = array_map(
            fn(Candidature $candidature) => $this->serializeCandidature($candidature),
            $candidatureRepository->findBy(['candidat' => $candidatId])
        );

        return $this->json($candidatures);
    }

    private function serializeCandidature(Candidature $candidature): array
    {
        return [
            'id' => $candidature->getId(),
            'message' => $candidature->getMessage(),
            'date_envoi' => $candidature->getDateEnvoi()?->format(\DateTimeInterface::RFC7231),

            'candidat' => $candidature->getCandidat() ? [
                'id' => $candidature->getCandidat()->getId(),
                'uuid' => $candidature->getCandidat()->getUuid(),
                'cvFilename' => $candidature->getCandidat()->getCvFilename(),
                'niveau_etude' => $candidature->getCandidat()->getNiveauEtude(),
                'departement' => $candidature->getCandidat()->getDepartement() ? [
                    'id' => $candidature->getCandidat()->getDepartement()->getId(),
                ] : null,
                // Crée un tableau avec les compétences du candidat au format "id" => id
                'competences' => array_map(
                    fn($competence) => [
                        'id' => $competence->getId(),
                        'type' => $competence->getType(),
                        'nom' => $competence->getNom(),
                        'domaine' => $competence->getDomaine()
                    ],
                    // Transforme collection competences en tableau
                    $candidature->getCandidat()->getCompetences()->toArray()
                ),
                // Récupère l'objet utilisateur lié au candidat (id uniquement)
                'utilisateur' => $candidature->getCandidat()->getUtilisateur() ? [
                    'id' => $candidature->getCandidat()->getUtilisateur()->getId(),
                    'nom' => $candidature->getCandidat()->getUtilisateur()->getNom(),
                    'prenom' => $candidature->getCandidat()->getUtilisateur()->getPrenom(),
                    'email' => $candidature->getCandidat()->getUtilisateur()->getEmail(),
                ] : null
            ] : null,

            'offre' => $candidature->getOffre() ? [
                'id' => $candidature->getOffre()->getId(),
                'intitule' => $candidature->getOffre()->getIntitule(),
                'description' => $candidature->getOffre()->getDescription(),
                'categorie_offre' => $candidature->getOffre()->getCategorieOffre(),
                'salaire' => $candidature->getOffre()->getSalaire(),
                'niveau_etudes' => $candidature->getOffre()->getNiveauEtudes(),
                'teletravail_possible' => $candidature->getOffre()->isTeletravailPossible(),
                'date_de_publication' => $candidature->getOffre()->getDateDePublication()?->format(\DateTimeInterface::RFC7231),
                'statut_offre' => $candidature->getOffre()->getStatutOffre(),
                'nombre_de_vues' => $candidature->getOffre()->getNombreDeVues(),
                'departement' => $candidature->getOffre()->getDepartement() ? [
                    'id' => $candidature->getOffre()->getDepartement()->getId(),
                ] : null,
                'recruteur' => $candidature->getOffre()->getRecruteur() ? [
                    'id' => $candidature->getOffre()->getRecruteur()->getId(),
                ] : null,
            ] : null,
        ];
    }

    private function decodeJson(Request $request): ?array
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $payload;
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }

    private function getCandidatConnecte(): ?Candidat
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\Utilisateur ? $user->getCandidat() : null;
    }
}
