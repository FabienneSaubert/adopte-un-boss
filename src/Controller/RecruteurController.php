<?php

namespace App\Controller;

use App\Entity\Recruteur;
use App\Factory\EntrepriseFactory;
use App\Factory\UtilisateurFactory;
use App\Parser\EntrepriseInputParser;
use App\Parser\UtilisateurInputParser;
use App\Repository\EntrepriseRepository;
use App\Repository\RecruteurRepository;
use App\Repository\UtilisateurRepository;
use App\ValueObject\EmailPro;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
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
        UtilisateurInputParser $utilisateurInputParser,
        UtilisateurRepository $utilisateurRepository,
        RecruteurRepository $recruteurRepository,
        EntrepriseInputParser $entrepriseInputParser,
        UtilisateurFactory $utilisateurFactory,
        EntrepriseFactory $entrepriseFactory,
        EntrepriseRepository $entrepriseRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse("Données dans le JSON body invalides.", Response::HTTP_BAD_REQUEST);
        }

        // On utilise le parser de l'entité Utilisateur afin de valider les champs dans $data
        $errorMessage = $utilisateurInputParser->validate($data);
        if ($errorMessage !== null) {
            return $this->errorResponse($errorMessage, Response::HTTP_BAD_REQUEST);
        }

        $emailExistant = $utilisateurRepository->findOneBy(['email' => $data["email"]]);
        if ($emailExistant) {
            return $this->errorResponse("Un utilisateur avec la même adresse mail existe déjà. Veuillez vous connectez ou créer un compte avec une nouvelle adresse.", Response::HTTP_BAD_REQUEST);
        }

        $posteError = null;
        $poste = $this->parsePoste((string) ($data["poste"] ?? null), true, $posteError);
        if ($poste === null) {
            return $this->errorResponse($posteError ?? "Le poste n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }
        $data["poste"] = $poste;

        // En utilisant la classe EmailPro afin de créer une instanciation valide d'un email professionnel,
        // on s'attend à ce que la validation ne soit potentiellement pas passée. On utilise donc un bloc
        // de type try catch afin de renvoyer une erreur JSON en cas d'exception lors de la création d'un EmailPro.
        try {
            // On créer donc un email pro valide avec la classe Email pro
            $emailPro = EmailPro::fromString($data['email_pro'] ?? null);
            // Puis on inscrit dans $data la valeur validée par les validations de la classe
            $data['email_pro'] = $emailPro?->asString();
        }
        // Si une exception a été levée lors de la validation, et qu'elle est de type InvalidArgumentException
        catch (InvalidArgumentException $e) {
            // On renvoit de la même façon l'erreur JSON, en se servant de la classe de l'exception
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $emailProExistant = $recruteurRepository->findOneBy(['email_pro' => $data["email_pro"]]);
        if ($emailProExistant) {
            return $this->errorResponse("Un utilisateur avec la même adresse mail professionnelle existe déjà.", Response::HTTP_BAD_REQUEST);
        }

        $telephonePro = (string) ($data["telephone_pro"] ?? null);
        if ($telephonePro !== null && $telephonePro !== '') {
            $telephoneProError = null;
            $telephonePro = $this->parseTelephonePro($telephonePro, false, $telephoneProError);
            if ($telephonePro === null) {
                return $this->errorResponse($telephoneProError ?? "Le téléphone professionnel n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        } else {
            $telephonePro = null;
        }
        $data["telephone_pro"] = $telephonePro;

        // On utilise le parser de l'entité Entreprise afin de valider les champs dans $data
        $errorMessage = $entrepriseInputParser->validate($data);
        if ($errorMessage !== null) {
            return $this->errorResponse($errorMessage, Response::HTTP_BAD_REQUEST);
        }

        // Une fois que les champs de l'entreprise sont validés et formatés,
        // on peut chercher si l'entreprise existe déjà, avec son N° de SIRET
        $entrepriseExistante = $entrepriseRepository->findOneBy(['siret' => $data["siret_entreprise"]]);
        // Si une entreprise existe déjà avec le même N° de SIRET en base de données
        if ($entrepriseExistante) {
            // On utilise la même entreprise (on fait le choix ici d'ignorer les autre informations
            // que le client a renseigné sur l'entreprise, ça sera à l'admin de corriger les conflits éventuels)
            $entreprise = $entrepriseExistante;
        } else {
            // Si aucune entreprise existe avec le N° de SIRET envoyé, on récupère une nouvelle
            // instance de l'entité Entreprise grâce à son factory
            $entreprise = $entrepriseFactory->create($data);
        }

        // On attribut dans $data le champ "entreprise" qui va contenir l'instanciation,
        // sa présence dans $data sera prise en compte dans le factory de l'utilisateur
        $data["entreprise"] = $entreprise;
        // On peut donc maintenant faire appel au factory de l'utilisateur, en lui passant en paramètre le bon rôle
        $recruteur = $utilisateurFactory->create('Recruteur', $data);

        // Une fois que l'on a tous les objets dont on a besoin, on peut les enregistrer en base de données
        // 
        // On persiste alors l'entreprise
        $entityManager->persist($entreprise);
        // Puis le recruteur
        $entityManager->persist($recruteur);
        // Et pourquoi pas l'utilisateur ? Parce que Symfony le fait tout seul, via l'instruction cascade persist
        // dans l'entité Utilisateur :
        // 
        // #[ORM\OneToOne(inversedBy: 'recruteur', cascade: ['persist', 'remove'])]
        // #[ORM\JoinColumn(nullable: false)]
        // private ?Utilisateur $utilisateur = null;

        // Faire le flush va donc prendre en compte ces trois nouvelles instanciations,
        // et les ajouter en base de données dans le bon ordre
        $entityManager->flush();

        return $this->json($this->serializeRecruteur($recruteur), Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'api_recruteur_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $recruteur = $this->getRecruteurConnecte();

        if (!$recruteur) {
            return $this->errorResponse(
                "Recruteur non authentifié.",
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $this->json($this->serializeRecruteur($recruteur));
    }

    #[Route('/me', name: 'api_recruteur_me_edit', methods: ['PUT', 'PATCH'])]
    public function editMe(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $recruteur = $this->getRecruteurConnecte();

        if (!$recruteur) {
            return $this->errorResponse(
                "Recruteur non authentifié.",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse(
                "Données JSON invalides.",
                Response::HTTP_BAD_REQUEST
            );
        }

        if (array_key_exists('poste', $data)) {
            $posteError = null;
            $poste = $this->parsePoste(
                (string) $data['poste'],
                true,
                $posteError
            );

            if ($poste === null) {
                return $this->errorResponse(
                    $posteError ?? "Poste invalide.",
                    Response::HTTP_BAD_REQUEST
                );
            }

            $recruteur->setPoste($poste);
        }

        if (array_key_exists('telephone_pro', $data)) {
            $telephoneError = null;
            $telephone = $this->parseTelephonePro(
                (string) $data['telephone_pro'],
                false,
                $telephoneError
            );

            if ($telephone === null) {
                return $this->errorResponse(
                    $telephoneError ?? "Téléphone invalide.",
                    Response::HTTP_BAD_REQUEST
                );
            }

            $recruteur->setTelephonePro($telephone);
        }

        $isPut = $request->getMethod() === 'PUT';

        if (array_key_exists('email_pro', $data) || $isPut) {
            // On utilise le même type de validation que lors de la création
            try {
                // On créé la valeur valide de l'email via la classe
                $emailPro = EmailPro::fromString($data['email_pro'] ?? null);
                // Puis on met à jour l'email pro du recruteur
                $recruteur->setEmailPro($emailPro?->asString());
            }
            // Si une exception a été levée lors de la validation, et qu'elle est de type InvalidArgumentException
            catch (InvalidArgumentException $e) {
                // On peut la traiter exactement de la même manière
                return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        

        $entityManager->flush();

        return $this->json($this->serializeRecruteur($recruteur));
    }

    #[Route('/me', name: 'api_recruteur_me_delete', methods: ['DELETE'])]
    public function deleteMe(
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $recruteur = $this->getRecruteurConnecte();

        if (!$recruteur) {
            return $this->errorResponse(
                "Recruteur non authentifié.",
                Response::HTTP_UNAUTHORIZED
            );
        }

        $entityManager->remove($recruteur);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
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

    #[Route('/{id}', name: 'api_recruteur_put_item', methods: ['PUT', 'PATCH'])]
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

        $isPut = $request->getMethod() === 'PUT';

        if (array_key_exists('poste', $data) || $isPut) {
            $posteError = null;
            $poste = $this->parsePoste((string) ($data["poste"] ?? null), true, $posteError);
            if ($poste === null) {
                return $this->errorResponse($posteError ?? "Le poste n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $recruteur->setPoste($poste);
        }

        // Pour la validation de l'email professionnel
        if (array_key_exists('email_pro', $data) || $isPut) {
            // On utilise le même type de validation que lors de la création
            try {
                // On créé la valeur valide de l'email via la classe
                $emailPro = EmailPro::fromString($data['email_pro'] ?? null);
                // Puis on met à jour l'email pro du recruteur
                $recruteur->setEmailPro($emailPro?->asString());
            }
            // Si une exception a été levée lors de la validation, et qu'elle est de type InvalidArgumentException
            catch (InvalidArgumentException $e) {
                // On peut la traiter exactement de la même manière
                return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        if (array_key_exists('telephone_pro', $data) || $isPut) {
            $telephoneProError = null;
            $telephonePro = $this->parseTelephonePro((string) ($data["telephone_pro"] ?? null), true, $telephoneProError);
            if ($telephonePro === null) {
                return $this->errorResponse($telephoneProError ?? "Le téléphone professionnel n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
            $recruteur->setTelephonePro($telephonePro);
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
            "email_pro" => $recruteur->getEmailPro(),
            "telephone_pro" => $recruteur->getTelephonePro(),
            "utilisateur" => [
                "nom" => $recruteur->getUtilisateur()->getNom(),
                "prenom" => $recruteur->getUtilisateur()->getPrenom(),
                "date_de_naissance" => $recruteur->getUtilisateur()->getDateDeNaissance()?->format('d-m-Y'),
                "email" => $recruteur->getUtilisateur()->getEmail(),
                "telephone" => $recruteur->getUtilisateur()->getTelephone(),
            ],
            "entreprise" => [
                "nom" => $recruteur->getEntreprise()->getNom(),
                "siret" => $recruteur->getEntreprise()->getSiret(),
                "adresse" => $recruteur->getEntreprise()->getAdresse(),
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
                $error = "Le téléphone professionnel est requis.";
            }
            return null;
        }

        if (!is_string($value)) {
            $error = "Le téléphone professionnel doit contenir des chiffres.";
            return null;
        }

        $value = str_replace(' ', '', $value);

        if (mb_strlen($value) > 12) {
            $error = "Le téléphone professionnel ne peut pas dépasser 12 caractères.";
            return null;
        }

        $telephoneRegex = '/^(?:\+33|0)[1-9]\d{8}$/';
        if (!preg_match($telephoneRegex, $value)) {
            $error = "Le format du téléphone professionnel n'est pas valide.";
            return null;
        }

        return $value;
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }

    private function getRecruteurConnecte(): ?Recruteur
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\Utilisateur ? $user->getRecruteur() : null;
    }
}
