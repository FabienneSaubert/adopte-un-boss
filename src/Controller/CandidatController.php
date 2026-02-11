<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Competence;
use App\Entity\Departement;
use App\Enum\NiveauEtude;
use App\Factory\UtilisateurFactory;
use App\Parser\UtilisateurInputParser;
use App\Repository\CandidatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/candidat')]
final class CandidatController extends AbstractController
{
    #[Route('', name: 'api_candidat_get_collection', methods: ['GET'])]
    public function list(CandidatRepository $candidatRepository): JsonResponse
    {
        // Récupération du tableau d'objets "Candidats"  (findAll) -> sérialization index par index puis affichage.
        $candidats = array_map(
            fn(Candidat $candidat) => $this->serializeCandidat($candidat),
            $candidatRepository->findAll()
        );

        return $this->json($candidats);
    }

    #[Route('', name: 'api_candidat_post_collection', methods: ['POST'])]
    public function new(
        Request $request,
        UtilisateurInputParser $utilisateurInputParser,
        EntityManagerInterface $entityManager,
        UtilisateurFactory $utilisateurFactory
    ): JsonResponse {

        $data = $this->decodeJson($request);

        if (!$data) {
            return $this->errorResponse('JSON invalide', Response::HTTP_BAD_REQUEST);
        }

        // On stocke le message d'erreur potentiellement renvoyé par la méthode 
        // validate de UtilisateurInputParser
        $errorMessage = $utilisateurInputParser->validate($data);
        if ($errorMessage !== null) {
            return $this->errorResponse($errorMessage, Response::HTTP_BAD_REQUEST);
        }

        // Valeurs par défaut
        $data['profil_visible'] = true;
        $data['infos_visibles'] = true;

        // CV optionnel
        $data['cv'] = null;

        // Génération de l'UUID (package Symfony)
        $data['uuid'] = Uuid::v4()->toRfc4122();

        // Récupération du candidat créé par factory
        $candidat = $utilisateurFactory->create('Candidat', $data);

        // Gestion des compétences
        // Varification si $data contient competence_ids et qu'il s'agit bien d'un tableau
        if (isset($data['competence_ids']) && is_array($data['competence_ids'])) {
            // alors on boucle sur le tableau
            foreach ($data['competence_ids'] as $competenceId) {
                // Le repository va chercher en BDD les compétences associées aux ID trouvés
                $competence = $entityManager->getRepository(Competence::class)->find($competenceId);

                if ($competence) { // si il y a comptétence alors on ajoute au candidat
                    $candidat->addCompetence($competence);
                } else { // sinon on retourne une erreur 
                    return $this->errorResponse("La compétence avec l'id $competenceId est introuvable", Response::HTTP_NOT_FOUND);
                }
            }
        }

        // Gestion du département
        // Vérifiacation : Si $data contient bien "departement_id"
        if (isset($data['departement'])) {
            // On stocke le département récupéré par la requête du repo
            $departement = $entityManager->getRepository(Departement::class)->find($data['departement']);

            if ($departement) { // si département n'est pas null
                $candidat->setDepartement($departement); // on set le département du candidat
            } else { // sinoon, envoi d'une erreur
                return $this->errorResponse('Departement introuvable', Response::HTTP_NOT_FOUND);
            }
        }

        $entityManager->persist($candidat); // On met en mémoire
        $entityManager->flush(); // on sauvegarde

        return $this->json($this->serializeCandidat($candidat), Response::HTTP_CREATED);
    }


    #[Route('/{id}', name: 'api_candidat_get_item', methods: ['GET'])]
    public function show(int $id, CandidatRepository $candidatRepository): JsonResponse
    {
        // on récupère le candidat par son ID par requête à la BDD via le Repo candidat
        $candidat = $candidatRepository->find($id);

        if (!$candidat) {
            return $this->errorResponse('Candidat introuvable', Response::HTTP_NOT_FOUND);
        }

        // On retourne la représentation de l'objet candidat en JSON 
        return $this->json($this->serializeCandidat($candidat));
    }

    #[Route('/{id}', name: 'api_candidat_put_item', methods: ['PATCH', 'PUT'])]
    public function edit(
        int $id,
        Request $request,
        CandidatRepository $candidatRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $candidat = $candidatRepository->find($id);

        // Vérification : si le candidat n'existe pas :
        if (!$candidat) {
            return $this->errorResponse("Ce candidat n'existe pas", Response::HTTP_NOT_FOUND);
        }

        // on récupère la requête du client en JSON et que la fonction "decodeJson" transforme en tableau PHP pour manipulation 
        $data = $this->decodeJson($request);

        // Vérifiacation : Si $data est vide alors envoi erreur
        if (!$data) {
            return $this->errorResponse('JSON invalide', Response::HTTP_BAD_REQUEST);
        }

        // La varibale isUpdate stocke une valeur booléenne à savoir si la méthode de la requête est PUT ou PATCH
        $isPut = $request->getMethod() === "PUT";

        // Mise à jour de profil_visible
        // Vérification si profil_visible existe bien dans $data (requête HTTP)
        if (array_key_exists('profil_visible', $data)) {
            // Si il ne s'agit pas d'un booléen alors renvoi une erreur
            if (!is_bool($data['profil_visible'])) {
                return $this->errorResponse('profil_visible doit être un booléen', Response::HTTP_BAD_REQUEST);
            }
            // Si la valeur est bien booléenne alors on update la valeur de profil_visible
            $candidat->setProfilVisible($data['profil_visible']);
        } elseif ($isPut) { // Sinon renvoie une erreur
            return $this->errorResponse('profil_visible est obligatoire', Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour de infos_visibles
        // Vérifiaction : est-ce que infos_visibles 
        if (array_key_exists('infos_visibles', $data)) {
            if (!is_bool($data['infos_visibles'])) {
                return $this->errorResponse(
                    'infos_visibles doit être un booléen',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $candidat->setInfosVisibles($data['infos_visibles']);
        } elseif ($isPut) {
            return $this->errorResponse(
                'infos_visibles est obligatoire',
                Response::HTTP_BAD_REQUEST
            );
        }


        // Mise à jour du niveau d'études
        // Vérification : est-ce que niveau_etude est bien dans $data ? 
        if (array_key_exists('niveau_etude', $data)) {
            // Si oui, alors on vérifie si la valeur correspond bien au fichier ENUM 
            // et on la stocke dans $niveauEtude
            
            // Validation de l'enum NiveauEtude
            // TryFrom = méthode pour énum, permet de comparerl'entrée avec les valaeurs présentes dans 
            // l'enum niveau_etude. Si match -> retourne une instance, sinon retourne null;
            //! TryFrom = renvoi null si la valeur n'est pas dans l'enum, le script continue, on gère le null 
            //! en créant une réponse adaptée "si niveauEtude est null"... 
            //! J'aurais pu utiliser from à la place mais ce n'est pas recommandé car en cas 
            //! d'erreur, from stoppe le script (et donc l'application).
            // Ici, je compare la valeur de la requête à la valeur "niveau_etude" avec les enum possible
            // On s'assure que l'entrée utilisateur soit bonne et non null.
            $niveauEtude = NiveauEtude::tryFrom($data['niveau_etude']);
            // Puis le repo édite la nouvelle valeur de l'attribut niveau_etude en mémoire
            $candidat->setNiveauEtude($niveauEtude);

            // si pas de valeur alors erreur
            if (!$niveauEtude) {
                return $this->errorResponse('Valeur de niveau_etude invalide', Response::HTTP_BAD_REQUEST);
            } // Si la première vérifiaction échoue alors erreur car niveau_etude n'est pas dans $data
        } elseif ($isPut) {
            return $this->errorResponse('Le niveau d\'études est requis', Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour du CV
        if (array_key_exists('cv', $data)) {
            if (!is_null($data['cv']) && !is_string($data['cv'])) {
                return $this->errorResponse('cv doit être une chaîne de caractères', Response::HTTP_BAD_REQUEST);
            }
            $candidat->setCv($data['cv']);
        }

        // Mise à jour des compétences
        if (array_key_exists('competences', $data)) {
            if (!is_array($data['competences'])) {
                return $this->errorResponse('La liste des id compétence doit être un tableau', Response::HTTP_BAD_REQUEST);
            }

            // Vide la collection Competences
            $candidat->getCompetences()->clear();

            // Ajouter les nouvelles compétences
            foreach ($data['competences'] as $competenceId) {
                $competence = $entityManager->find(Competence::class, $competenceId);

                if ($competence) {
                    $candidat->addCompetence($competence);
                } else {
                    return $this->errorResponse("La compétence id $competenceId est introuvable", Response::HTTP_NOT_FOUND);
                }
            }
        }

        // Mise à jour du département 
        if (array_key_exists('departement', $data)) {
            if ($data['departement'] === null) {
                $candidat->setDepartement(null);
            } else {
                $departement = $entityManager->find(Departement::class, $data['departement']); // Méthode plus concise
                if ($departement) {
                    $candidat->setDepartement($departement);
                } else {
                    return $this->errorResponse('Departement non trouvé', Response::HTTP_NOT_FOUND);
                }
            }
        }

        $entityManager->flush();

        return $this->json($this->serializeCandidat($candidat));
    }

    #[Route('/{id}', name: 'api_candidat_delete_item', methods: ['DELETE'])]
    public function delete(int $id, CandidatRepository $candidatRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // Requête ppour récupérer le candidat par son id
        $candidat = $candidatRepository->find($id);

        // Gestion des erreurs
        // Vérification : Si candidat = null alors erreur
        if (!$candidat) {
            return $this->errorResponse("Ce candidat n'existe pas", Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($candidat); // préparation de la requête Delete 
        $entityManager->flush(); // Envoi la requête = modification de la BDD

        // une requête attend une réponse, on renvoit donc une réponse null ici car la donnée a été supprimée
        // le seul but de cette réponse est de faire savoir au client que la requête à bien aboutie 
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeCandidat(Candidat $candidat): array
    {
        return [
            'id' => $candidat->getId(),
            'profil_visible' => $candidat->isProfilVisible(),
            'infos_visibles' => $candidat->isInfosVisibles(),
            'uuid' => $candidat->getUuid(),
            'cv' => $candidat->getCv(),
            'niveau_etude' => $candidat->getNiveauEtude()?->value,
            "utilisateur" => [
                "nom" => $candidat->getUtilisateur()->getNom(),
                "prenom" => $candidat->getUtilisateur()->getPrenom(),
                "date_de_naissance" => $candidat->getUtilisateur()->getDateDeNaissance(),
                "email" => $candidat->getUtilisateur()->getEmail(),
                "telephone" => $candidat->getUtilisateur()->getTelephone(),
            ],
            'competences' => array_map(
                fn(Competence $c) => [
                    'id' => $c->getId(),
                    'type' => $c->getDomaine(),
                    'nom' => $c->getNom(),
                ],
                $candidat->getCompetences()->toArray()
            ),
            'departement' => $candidat->getDepartement() ? [
                'id' => $candidat->getDepartement()->getId(),
                'nom' => $candidat->getDepartement()->getNom(),
                'numero' => $candidat->getDepartement()->getNumero(),
            ] : null,
            'candidatures' => array_map(
                fn($candidature) => [
                    'id' => $candidature->getId(),
                ],
                $candidat->getCandidatures()->toArray()
            ),
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
}
