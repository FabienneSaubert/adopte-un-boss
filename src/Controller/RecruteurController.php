<?php

namespace App\Controller;

use App\Entity\Recruteur;
use App\Repository\EntrepriseRepository;
use App\Repository\RecruteurRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/recruteur')]
final class RecruteurController extends AbstractController
{
    #[Route(name: 'api_recruteur_get_collection', methods: ['GET'])]
    public function index(RecruteurRepository $recruteurRepository): JsonResponse
    {
        $recruteur = array_map(
            fn(Recruteur $recruteur) => $this->serializeRecruteur($recruteur),
            $recruteurRepository->findAll()
        );

        return $this->json($recruteur);
    }

    #[Route(name: 'api_recruteur_post_item', methods: ['POST'])]
    public function new(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        EntrepriseRepository $entrepriseRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $data = $this->decodeJson($request);

        $posteError = null;
        $poste = $this->parsePoste((string) ($data["poste"] ?? null), true, $posteError);
        if ($poste === null) {
            return $this->errorResponse($posteError ?? "Le poste n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        $emailPro = (string) ($data["emailpro"] ?? null);
        if ($emailPro !== null && $emailPro !== '') {
            $emailProError = null;
            $emailPro = $this->parseEmailPro($emailPro, false, $emailProError);
            if ($emailPro === null) {
                return $this->errorResponse($emailProError ?? "L'email professionnel n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }
        else {
            $emailPro = null;
        }

        $telephonePro = (string) ($data["telephonepro"] ?? null);
        if ($telephonePro !== null && $telephonePro !== '') {
            $telephoneProError = null;
            $telephonePro = $this->parseTelephonePro($telephonePro, false, $telephoneProError);
            if ($telephonePro === null) {
                return $this->errorResponse($telephoneProError ?? "Le telephone professionnel n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }
        else {
            $telephonePro = null;
        }

        // ==================== SOLUTION TEMPORAIRE ====================
        // On utilise par défaut afin que les contraintes relationnelles en base de données soient respectées,
        // à corriger plus tard en accord avec la partie Utilisateur et Entreprise avec des Factory.
        $utilisateur = $utilisateurRepository->find(1);
        $entreprise = $entrepriseRepository->find(1);
        if (!$utilisateur || !$entreprise) {
            return $this->errorResponse(
                "Utilisateur ou entreprise par défaut introuvable.",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        // =============================================================

        $recruteur = (new Recruteur())
            ->setPoste($poste)
            ->setEmailPro($emailPro)
            ->setTelephonePro($telephonePro)
            ->setUtilisateur($utilisateur)
            ->setEntreprise($entreprise)
        ;
        // Le statut de l'envoi est déjà défini par défaut comme étant "En attente"

        $entityManager->persist($recruteur);

        $entityManager->flush();

        return $this->json($this->serializeRecruteur($recruteur), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_recruteur_show', methods: ['GET'])]
    public function show(int $id, RecruteurRepository $recruteurRepository): JsonResponse
    {
        $recruteur = $recruteurRepository->find($id);

        if (!$recruteur) {
            return $this->errorResponse("Recruteur introuvable.", Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeRecruteur($recruteur));
    }

    #[Route('/{id}', name: 'api_recruteur_put_item', methods: ['PUT'])]
    public function edit(int $id, Request $request, RecruteurRepository $recruteurRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $recruteur = $recruteurRepository->find($id);

        if (!$recruteur) {
            return $this->errorResponse("Demande de contact introuvable.", Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse("Données dans le JSON body invalides.", Response::HTTP_BAD_REQUEST);
        }

        $poste = (string) ($data["poste"] ?? null);
        if ($poste !== null && $poste !== '') {
            $posteError = null;
            $poste = $this->parsePoste($poste, false, $posteError);
            if ($poste !== null) {
                $recruteur->setPoste($poste);
            }
            else {
                return $this->errorResponse($posteError ?? "Le poste n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $emailPro = (string) ($data["emailpro"] ?? null);
        if ($emailPro !== null && $emailPro !== '') {
            $emailProError = null;
            $emailPro = $this->parseEmailPro($emailPro, false, $emailProError);
            if ($emailPro !== null) {
                $recruteur->setEmailPro($emailPro);
            }
            else {
                return $this->errorResponse($emailProError ?? "L'email professionnel n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $telephonePro = (string) ($data["telephonepro"] ?? null);
        if ($telephonePro !== null && $telephonePro !== '') {
            $telephoneProError = null;
            $telephonePro = $this->parseTelephonePro($telephonePro, false, $telephoneProError);
            if ($telephonePro !== null) {
                $recruteur->setTelephonePro($telephonePro);
            }
            else {
                return $this->errorResponse($telephoneProError ?? "Le telephone professionnel n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $entityManager->flush();

        return $this->json($this->serializeRecruteur($recruteur));
    }

    #[Route('/{id}', name: 'api_recruteur_delete_item', methods: ['DELETE'])]
    public function delete(int $id, RecruteurRepository $recruteurRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $recruteur = $recruteurRepository->find($id);

        if (!$recruteur) {
            return $this->errorResponse("Demande de contact introuvable.", Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($recruteur);

        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeRecruteur(Recruteur $recruteur): array
    {
        return [
            "id" => $recruteur->getId(),
            "poste" => $recruteur->getPoste(),
            "emailPro" => $recruteur->getEmailPro(),
            "telephonePro" => $recruteur->getTelephonePro(),
            "utilisateur" => [
                "nom" => $recruteur->getUtilisateur()->getNom(),
                "prenom" => $recruteur->getUtilisateur()->getPrenom(),
                // ... à valider en groupe
            ],
            "entreprise" => [
                "nom" => $recruteur->getEntreprise()->getNom(),
                "siret" => $recruteur->getEntreprise()->getSiret(),
                // ... à valider en groupe
            ],
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

    private function parsePoste(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le poste est requis.";
            }
            return null;
        }

        if (mb_strlen($value) > 100) {
            $error = "Le poste ne peut pas dépasser 100 caractères.";
            return null;
        }

        return $value;
    }

    private function parseEmailPro(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "L'email profesionnel est requis.";
            }
            return null;
        }

        // Suppression des caractères illégaux
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);

        if (mb_strlen($value) > 100) {
            $error = "L'email profesionnel ne peut pas dépasser 100 caractères.";
            return null;
        }

        // Validation de l'email profesionnel par filtre PHP
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $error = "Le format de l'email profesionnel n'est pas valide.";
            return null;
        }

        return $value;
    }

    private function parseTelephonePro(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le telephone professionnel est requis.";
            }
            return null;
        }

        if (mb_strlen($value) > 12) {
            $error = "Le telephone professionnel ne peut pas dépasser 12 caractères.";
            return null;
        }

        return $value;
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
}