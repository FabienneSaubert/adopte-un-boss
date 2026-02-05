<?php
// composer require symfony/uid = package pour générer UUID
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

        if (!$data) { // $data === null plus sécurisé que !$data ?
            return $this->errorResponse('JSON invalide', Response::HTTP_BAD_REQUEST);
        }

        $errorMessage = $utilisateurInputParser->validate($data);
        if ($errorMessage !== null) {
            return $this->errorResponse($errorMessage, Response::HTTP_BAD_REQUEST);
        }

        // Validation des champs requis
        $requis = ['profil_visible', 'infos_visibles', 'niveau_etude', 'utilisateur_id'];
        foreach ($requis as $champs) {
            if (!isset($data[$champs])) {
                return $this->errorResponse("Le champs '$champs' est requis", Response::HTTP_BAD_REQUEST);
            }
        }

        // Validation des booléens
        if (!is_bool($data['profil_visible'])) {
            return $this->errorResponse('profil_visible doit être un booléen', Response::HTTP_BAD_REQUEST);
        }
        if (!is_bool($data['infos_visibles'])) {
            return $this->errorResponse('infos_visibles doit être un booléen', Response::HTTP_BAD_REQUEST);
        }

        // Validation de l'enum NiveauEtude
        // TryFrom = méthode pour énum, permet de comparerl'entrée avec les valaeurs présentes dans 
        // l'enum niveau_etude. Si match -> retourne une instance, sinon retourne null;

        // Ici, je compare la valeur de la requête à la valeur "niveau_etude" avec les enum possible
        // On s'assure que l'entrée utilisateur soit bonne et non null. 
        $niveauEtude = NiveauEtude::tryFrom($data['niveau_etude']);

        $data['niveau_etude'] = $niveauEtude;

        if (!$niveauEtude) {
            return $this->json(
                ['error' => 'niveau_etude invalide'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Génération de l'UUID
        $data['uuid'] = Uuid::v4()->toRfc4122();

        // Récupération du candidat créé par factory
        $candidat = $utilisateurFactory->create('Candidat', $data);

        // Gestion des compétences
        if (isset($data['competence_ids']) && is_array($data['competence_ids'])) {

            foreach ($data['competence_ids'] as $competenceId) {
                $competence = $entityManager->getRepository(Competence::class)->find($competenceId);

                if ($competence) {
                    $candidat->addCompetence($competence);
                } else {
                    return $this->errorResponse("La compétence avec l'id $competenceId est introuvable", Response::HTTP_NOT_FOUND);
                }
            }
        }

        // Gestion du département
        if (isset($data['departement_id'])) {

            $departement = $entityManager->getRepository(Departement::class)->find($data['departement_id']);

            if ($departement) {
                $candidat->setDepartement($departement);
            } else {
                return $this->errorResponse('Departement introuvable', Response::HTTP_NOT_FOUND);
            }
        }

        $entityManager->persist($candidat);
        $entityManager->flush();

        return $this->json($this->serializeCandidat($candidat), Response::HTTP_CREATED);
    }


    #[Route('/{id}', name: 'api_candidat_get_item', methods: ['GET'])]
    public function show(int $id, CandidatRepository $candidatRepository): JsonResponse
    {
        $candidat = $candidatRepository->find($id);

        if (!$candidat) {
            return $this->errorResponse('Candidat introuvable', Response::HTTP_NOT_FOUND);
        }

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

        if (!$candidat) {
            return $this->errorResponse("Ce candidat n'existe pas", Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);

        if (!$data) {
            return $this->errorResponse('JSON invalide', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request->getMethod() === 'PUT';

        // Mise à jour de profil_visible
        if (array_key_exists('profil_visible', $data)) {
            if (!is_bool($data['profil_visible'])) {
                return $this->errorResponse('profil_visible doit être un booléen', Response::HTTP_BAD_REQUEST);
            }
            $candidat->setProfilVisible($data['profil_visible']);
        } elseif ($isPut) {
            return $this->errorResponse('profil_visible doit être un booléen', Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour de infos_visibles
        if (array_key_exists('infos_visibles', $data)) {
            if (!is_bool($data['infos_visibles'])) {
                return $this->errorResponse('infos_visibles doit être un booléen', Response::HTTP_BAD_REQUEST);
            }
            $candidat->setInfosVisibles($data['infos_visibles']);
        } elseif ($isPut) {
            return $this->errorResponse('infos_visibles est requis', Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour du CV
        if (array_key_exists('cv', $data)) {
            $candidat->setCv($data['cv']);
        }

        // Mise à jour du niveau d'études
        if (array_key_exists('niveau_etude', $data)) {

            $niveauEtude = NiveauEtude::tryFrom($data['niveau_etude']);
            $candidat->setNiveauEtude($niveauEtude);

            if (!$niveauEtude) {
                return $this->errorResponse('Valeur de niveau_etude invalide', Response::HTTP_BAD_REQUEST);
            }
        } elseif ($isPut) {
            return $this->errorResponse('Le niveau d\'études est requis', Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour des compétences
        if (array_key_exists('competence_ids', $data)) {
            if (!is_array($data['competence_ids'])) {
                return $this->errorResponse('La liste des id compétence doit être un tableau', Response::HTTP_BAD_REQUEST);
            }

            // Supprimer toutes les compétences existantes
            foreach ($candidat->getCompetences() as $competence) {
                $candidat->removeCompetence($competence);
            }

            // Ajouter les nouvelles compétences
            foreach ($data['competence_ids'] as $competenceId) {
                $competence = $entityManager->getRepository(Competence::class)->find($competenceId);
                // Méthode plus concise :  $competence = $entityManager->find(Competence::class, $competenceId); 
                if ($competence) {
                    $candidat->addCompetence($competence);
                } else {
                    return $this->errorResponse("La compétence avec l'id $competenceId est introuvable", Response::HTTP_NOT_FOUND);
                }
            }
        }

        // Mise à jour du département 
        if (array_key_exists('departement_id', $data)) {
            if ($data['departement_id'] === null) {
                $candidat->setDepartement(null);
            } else {
                $departement = $entityManager->find(Departement::class, $data['departement_id']); // Méthode plus concise
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
        if (!$candidat) {
            return $this->errorResponse("Ce candidat n'existe pas", Response::HTTP_NOT_FOUND);
        }

        // Suppression de la BDD
        $entityManager->remove($candidat);
        $entityManager->flush(); // Sauvegarde de du nouvel état de la BDD 

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
                ],
                $candidat->getCompetences()->toArray()
            ),
            'departement' => $candidat->getDepartement() ? [
                'id' => $candidat->getDepartement()->getId(),
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
