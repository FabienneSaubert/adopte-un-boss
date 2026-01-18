<?php

namespace App\Controller;

use App\Entity\SelectionCompetence;
use App\Repository\SelectionCompetenceRepository;
use App\Repository\OffreRepository;
use App\Repository\CompetenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/selection/competence')]
final class SelectionCompetenceController extends AbstractController
{
    #[Route('', name: 'app_selection_competence_list', methods: ['GET'])]
    public function list(SelectionCompetenceRepository $selectionCompetenceRepository): JsonResponse
    {
        $selectionCompetences = array_map(
            fn(SelectionCompetence $selectionCompetence)
            => $this->serializeSelectionCompetence($selectionCompetence),
            $selectionCompetenceRepository->findAll()
        );

        return $this->json($selectionCompetences);
    }

    #[Route('', name: 'app_selection_competence_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        OffreRepository $offreRepository,
        CompetenceRepository $competenceRepository,
        SelectionCompetenceRepository $selectionCompetenceRepository
    ): JsonResponse {
        $data = $this->decodeJson($request);

        if (!$data) {
            return $this->errorResponse('JSON invalide', Response::HTTP_BAD_REQUEST);
        }

        // Validation des champs requis
        $requis = ['offre_id', 'competence_id', 'coeff_competence'];
        foreach ($requis as $champs) {
            if (!isset($data[$champs])) {
                return $this->errorResponse("Le champs '$champs' est requis", Response::HTTP_BAD_REQUEST);
            }
        }

        // Validation du coefficient
        if (!is_int($data['coeff_competence'])) {
            return $this->errorResponse('Le coefficient de la compétence doit être un nombre', Response::HTTP_BAD_REQUEST);
        }

        if ($data['coeff_competence'] < 1 || $data['coeff_competence'] > 10) {
            return $this->errorResponse('Le coefficient de la compétence doit être compris entre 1 et 10', Response::HTTP_BAD_REQUEST);
        }

        // Récupération de l'offre
        $offre = $offreRepository->find($data['offre_id']);
        if (!$offre) {
            return $this->errorResponse('Offre introuvable', Response::HTTP_NOT_FOUND);
        }

        // Récupération de la compétence
        $competence = $competenceRepository->find($data['competence_id']);
        if (!$competence) {
            return $this->errorResponse('Compétence introuvable', Response::HTTP_NOT_FOUND);
        }

        // Vérifie si cette compétence n'est pas déjà sélectionnée pour cette offre
        $competenceExistante = $selectionCompetenceRepository->findOneBy([
            'offre' => $offre, // ref à ligne 63
            'competence' => $competence // ref à ligne 69
        ]);

        if ($competenceExistante) {
            return $this->errorResponse(
                'Cette compétence est déjà selectionnée sur cette offre',
                Response::HTTP_CONFLICT
            );
        }

        // Création de la sélection de compétence
        $selectionCompetence = (new SelectionCompetence())
            ->setCoeffCompetence($data['coeff_competence'])
            ->setOffre($offre)
            ->setCompetence($competence);

        $entityManager->persist($selectionCompetence);
        $entityManager->flush();

        return $this->json($this->serializeSelectionCompetence($selectionCompetence), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_selection_competence_show', methods: ['GET'])]
    public function show(int $id, SelectionCompetenceRepository $selectionCompetenceRepository): JsonResponse
    {
        $selectionCompetence = $selectionCompetenceRepository->find($id);

        if (!$selectionCompetence) {
            return $this->errorResponse('La compétence sélectionnée est introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeSelectionCompetence($selectionCompetence));
    }

    #[Route('/{id}', name: 'app_selection_competence_edit', methods: ['PATCH', 'PUT'])]
    public function edit(
        int $id,
        Request $request,
        SelectionCompetenceRepository $selectionCompetenceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $selectionCompetence = $selectionCompetenceRepository->find($id);

        if (!$selectionCompetence) {
            return $this->errorResponse('La compétence sélectionnée est introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);

        if (!$data) {
            return $this->errorResponse('JSON invalide', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request->getMethod() === 'PUT';

        // Mise à jour du coefficient
        if (array_key_exists('coeff_competence', $data)) {
            if (!is_int($data['coeff_competence'])) {
                return $this->errorResponse('Le coeffecient de compétence doit être un nombre', Response::HTTP_BAD_REQUEST);
            }

            if ($data['coeff_competence'] < 1 || $data['coeff_competence'] > 10) {
                return $this->errorResponse('Le coefficient de compétence doit être un nombre entre 1 et 10', Response::HTTP_BAD_REQUEST);
            }

            $selectionCompetence->setCoeffCompetence($data['coeff_competence']);
        } elseif ($isPut) {
            return $this->errorResponse('Le coefficient est requis', Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json($this->serializeSelectionCompetence($selectionCompetence));
    }

    // Supprimer une compétence selectionnée
    #[Route('/{id}', name: 'app_selection_competence_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        SelectionCompetenceRepository $selectionCompetenceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $selectionCompetence = $selectionCompetenceRepository->find($id);

        if (!$selectionCompetence) {
            return $this->errorResponse('La compétence sélectionnée est introuvable', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($selectionCompetence);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    //Récupérer toutes les compétences pour une offre
    #[Route('/offre/{offreId}', name: 'app_selection_competence_by_offre', methods: ['GET'])]
    public function getByOffre(int $offreId, SelectionCompetenceRepository $selectionCompetenceRepository): JsonResponse
    {
        $selections = array_map(
            fn(SelectionCompetence $selection) => $this->serializeSelectionCompetence($selection),
            $selectionCompetenceRepository->findBy(['offre' => $offreId], ['coeff_competence' => 'DESC'])
        );

        return $this->json($selections);
    }


    private function serializeSelectionCompetence(SelectionCompetence $selectionCompetence): array
    {
        return [
            'id' => $selectionCompetence->getId(),
            'coeff_competence' => $selectionCompetence->getCoeffCompetence(),
            'offre' => $selectionCompetence->getOffre() ? [
                'id' => $selectionCompetence->getOffre()->getId(),
                'intitule' => $selectionCompetence->getOffre()->getIntitule(),
                'statut_offre' => $selectionCompetence->getOffre()->getStatutOffre()?->value,
            ] : null,
            'competence' => $selectionCompetence->getCompetence() ? [
                'id' => $selectionCompetence->getCompetence()->getId(),
            ] : null,
        ];
    }

    // Récupère le contenu de la requête et le convertit en tableau associatif pour manipulation PHP
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
}
