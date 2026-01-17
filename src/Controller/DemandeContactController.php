<?php

namespace App\Controller;

use App\Entity\DemandeContact;
use App\Enum\StatutDemande;
use App\Repository\DemandeContactRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/demandecontact')]
final class DemandeContactController extends AbstractController
{
    #[Route(name: 'api_demandecontact_get_collection', methods: ['GET'])]
    public function index(DemandeContactRepository $demandecontactRepository): JsonResponse
    {
        $demandecontact = array_map(
            fn(DemandeContact $demandecontact) => $this->serializeDemandeContact($demandecontact),
            $demandecontactRepository->findAll()
        );

        return $this->json($demandecontact);
    }

    #[Route(name: 'api_demandecontact_post_item', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->decodeJson($request);

        $nomError = null;
        $nom = $this->parseNom((string) ($data["nom"] ?? null), true, $nomError);
        if ($nom === null) {
            return $this->errorResponse($nomError ?? "Le nom n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        $prenomError = null;
        $prenom = $this->parsePrenom((string) ($data["prenom"] ?? null), true, $prenomError);
        if ($prenom === null) {
            return $this->errorResponse($prenomError ?? "Le prenom n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        $emailError = null;
        $email = $this->parseEmail((string) ($data["email"] ?? null), true, $emailError);
        if ($email === null) {
            return $this->errorResponse($emailError ?? "L'email n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        $sujetError = null;
        $sujet = $this->parseSujet((string) ($data["sujet"] ?? null), true, $sujetError);
        if ($sujet === null) {
            return $this->errorResponse($sujetError ?? "Le sujet n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        $messageError = null;
        $message = $this->parseMessage((string) ($data["message"] ?? null), true, $messageError);
        if ($message === null) {
            return $this->errorResponse($messageError ?? "Le message n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        // On défini ici la date qui correspond à la date actuelle, venant du serveur
        // On prend une nouvelle instance de DateTimeImmutable qui renvoi une date inchangeable
        // correspondante au moment où est exécuté la commande
        $dateEnvoi = new DateTimeImmutable();

        $demandecontact = (new DemandeContact())
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setEmail($email)
            ->setSujet($sujet)
            ->setMessage($message)
            ->setDateEnvoi($dateEnvoi)
        ;
        // Le statut de l'envoi est déjà défini par défaut comme étant "En attente"

        $entityManager->persist($demandecontact);

        $entityManager->flush();

        return $this->json($this->serializeDemandeContact($demandecontact), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_demandecontact_show', methods: ['GET'])]
    public function show(int $id, DemandeContactRepository $demandecontactRepository): JsonResponse
    {
        $demandecontact = $demandecontactRepository->find($id);

        if (!$demandecontact) {
            return $this->errorResponse("DemandeContact introuvable.", Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeDemandeContact($demandecontact));
    }

    #[Route('/{id}', name: 'api_demandecontact_put_item', methods: ['PUT'])]
    public function edit(int $id, Request $request, DemandeContactRepository $demandecontactRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $demandecontact = $demandecontactRepository->find($id);

        if (!$demandecontact) {
            return $this->errorResponse("Demande de contact introuvable.", Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse("Données dans le JSON body invalides.", Response::HTTP_BAD_REQUEST);
        }

        $nom = (string) ($data["nom"] ?? null);
        if ($nom !== null && $nom !== '') {
            $nomError = null;
            $nom = $this->parseNom($nom, false, $nomError);
            if ($nom !== null) {
                $demandecontact->setNom($nom);
            }
            else {
                return $this->errorResponse($nomError ?? "Le nom n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $prenom = (string) ($data["prenom"] ?? null);
        if ($prenom !== null && $prenom !== '') {
            $prenomError = null;
            $prenom = $this->parsePrenom($prenom, false, $prenomError);
            if ($prenom !== null) {
                $demandecontact->setPrenom($prenom);
            }
            else {
                return $this->errorResponse($prenomError ?? "Le prenom n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $email = (string) ($data["email"] ?? null);
        if ($email !== null && $email !== '') {
            $emailError = null;
            $email = $this->parseEmail($email, false, $emailError);
            if ($email !== null) {
                $demandecontact->setEmail($email);
            }
            else {
                return $this->errorResponse($emailError ?? "L'email n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $sujet = (string) ($data["sujet"] ?? null);
        if ($sujet !== null && $sujet !== '') {
            $sujetError = null;
            $sujet = $this->parseSujet($sujet, false, $sujetError);
            if ($sujet !== null) {
                $demandecontact->setSujet($sujet);
            }
            else {
                return $this->errorResponse($sujetError ?? "Le sujet n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $message = (string) ($data["message"] ?? null);
        if ($message !== null && $message !== '') {
            $messageError = null;
            $message = $this->parseMessage($message, false, $messageError);
            if ($message !== null) {
                $demandecontact->setMessage($message);
            }
            else {
                return $this->errorResponse($messageError ?? "Le message n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $statutDemande = (string) ($data["statut"] ?? null);
        if ($statutDemande !== null && $statutDemande !== '') {
            $statutError = null;
            $statutDemande = $this->parseStatutDemande($statutDemande, false, $statutError);
            if ($statutDemande !== null) {
                $demandecontact->setStatutDemande($statutDemande);
            }
            else {
                return $this->errorResponse($statutError ?? "Le statut n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        $entityManager->flush();

        return $this->json($this->serializeDemandeContact($demandecontact));
    }

    #[Route('/{id}', name: 'api_demandecontact_delete_item', methods: ['DELETE'])]
    public function delete(int $id, DemandeContactRepository $demandecontactRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $demandecontact = $demandecontactRepository->find($id);

        if (!$demandecontact) {
            return $this->errorResponse("Demande de contact introuvable.", Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($demandecontact);

        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeDemandeContact(DemandeContact $demandecontact): array
    {
        return [
            "id" => $demandecontact->getId(),
            "nom" => $demandecontact->getNom(),
            "prenom" => $demandecontact->getPrenom(),
            "email" => $demandecontact->getEmail(),
            "sujet" => $demandecontact->getSujet(),
            "message" => $demandecontact->getMessage(),
            "dateEnvoi" => $demandecontact->getDateEnvoi(),
            "statutDemande" => $demandecontact->getStatutDemande(),
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

    private function parseNom(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le nom est requis.";
            }
            return null;
        }

        if (mb_strlen($value) > 45) {
            $error = "Le nom ne peut pas dépasser 45 caractères.";
            return null;
        }

        return $value;
    }

    private function parsePrenom(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le prenom est requis.";
            }
            return null;
        }

        if (mb_strlen($value) > 45) {
            $error = "Le prenom ne peut pas dépasser 45 caractères.";
            return null;
        }

        return $value;
    }

    private function parseEmail(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "L'email est requis.";
            }
            return null;
        }

        if (mb_strlen($value) > 100) {
            $error = "L'email ne peut pas dépasser 100 caractères.";
            return null;
        }

        return $value;
    }

    private function parseSujet(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le sujet est requis.";
            }
            return null;
        }

        if (mb_strlen($value) > 100) {
            $error = "Le sujet ne peut pas dépasser 100 caractères.";
            return null;
        }

        return $value;
    }

    private function parseMessage(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le message est requis.";
            }
            return null;
        }

        return $value;
    }

    private function parseStatutDemande(mixed $value, bool $required, ?string &$error)
    {
        $value = trim($value);

        if ($value === null || $value === '') {
            if ($required) {
                $error = "Le statut est requis.";
            }
            return null;
        }

        // Conversion du statut string en type StatutDemande
        if ($value === "En attente") return StatutDemande::EN_ATTENTE;
        if ($value === "En cours") return StatutDemande::EN_COURS;
        if ($value === "Terminée") return StatutDemande::TERMINEE;

        return null;
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
}