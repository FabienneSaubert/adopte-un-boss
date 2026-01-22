<?php
// -- Version pédagogique avec commentaires explicatifs --

// On déclare que notre code (et donc la classe EntrepriseController) sera contenu dans le nomspace "App\Controller".
// La classe sera donc contenue dans une boîte nommée "App\Controller",
// et pourra donc être inclue dans un autre code PHP via : use App\Controller\EntrepriseController
// Toujours à déclarer en premier.
namespace App\Controller;

// On importe l'entité de la base de données.
// Cette classe Entreprise représente toutes les données d'un entreprise, avec leur formats et le getter et setter.
use App\Entity\Entreprise;

// On importe les méthodes utilitaires permettant de manipuler précisément l'entité Entreprise.
// Les méthodes dans cette classe sont spécifiques au entreprise, findAll() servira par exemple à
// obtenir la liste de tous les entreprise, c'est à ce moment-là que les requêtes en base de données seront préparées.
use App\Repository\EntrepriseRepository;

// On importe le parser de l'entité Entreprise qui contient toutes les méthodes de validation et de
// formatage des attributs d'une entreprise. Le parser s'occupe donc dans ce cas de valider puis
// attribuer les donneés venant du client.
use App\Parser\EntrepriseInputParser;

// On importe le factory de l'entité Entreprise qui va permettre de déplacer ailleurs l'instanciation
// d'un nouvel objet Entreprise au quel on va attribuer des valeurs avec les setter
use App\Factory\EntrepriseFactory;

// On inclut l'interface ORM qui va permettre la communication entre PHP et la base de données.
// Doctrine permet de manipuler n'importe quel type de base de données, les commandes utilisées avec ce dernier s'adaptent
// à l'architecture de la base de données.
use Doctrine\ORM\EntityManagerInterface;

// On importe les classes Symfony nécessaires pour gérer les requêtes et réponses HTTP.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

// On importe l'attribut Route permettant de définir les routes directement au-dessus des classes et méthodes.
use Symfony\Component\Routing\Attribute\Route;

// On précise un chemin de base pour toutes les routes de ce contrôleur.
// Cela permet d'éviter de répeter du code en écrivant à chaque fois la partie du début qui est toujours la même :
// /api/entreprise/{id} -> /{id} (PHP va automatiquement déclarer le chemin "/api/entreprise/{id}")
#[Route('/api/entreprise')]
// Déclaration de la classe de notre contrôleur pour gérer les entreprise.
// Ici dans les méthodes est gérée toute la logique métier que l'on souhaite implémenter avec nos entreprise.
// On utilise le mot clé "final" pour interdire à une classe enfant de redéfinir les méthodes de ce contrôleur.
final class EntrepriseController extends AbstractController
{
    // On déclare que pour la route, nommée "api_entreprise_get_collection", la méthode publique index()
    // sera exécutée si la méthode de la requête du client est GET.
    // Comme aucun chemin n'est précisé ici, la route correspond à : /api/entreprise
    #[Route(name: 'api_entreprise_get_collection', methods: ['GET'])]
    /**
     * Renvoi la liste de tous les entreprise en JSON.
     * @param EntrepriseRepository $entrepriseRepository repository permettant de lire les entreprise en base
     * @return JsonResponse réponse JSON contenant la liste des entreprise sérialisés
     */
    public function index(EntrepriseRepository $entrepriseRepository): JsonResponse
    {
        // Pour tous les entreprise récupérés depuis la base de données, on applique une
        // remise en forme de chaque entrée dans le tableau, donc pour chaque entreprise.
        $entreprise = array_map(
            // On déclare ici notre fonction de modification.
            // Elle prend en paramètre chaque entreprise (objet Doctrine),
            // puis renvoi le retour de la fonction serializeEntreprise() qui renvoie un tableau PHP simple.
            fn(Entreprise $entreprise) => $this->serializeEntreprise($entreprise),
            // On récupère tous les entreprise de la table entreprise.
            $entrepriseRepository->findAll()
        );

        // On retourne la réponse JSON avec les données.
        return $this->json($entreprise);
    }

    // Pour toute requête envoyée sur la route /api/entreprise, nommée "api_entreprise_post_item",
    // avec la méthode "POST", la méthode publique new() du contrôleur est exécutée.
    #[Route(name: 'api_entreprise_post_item', methods: ['POST'])]
    /**
     * Permet d'ajouter une nouvelle entreprise.
     * @param Request $request requête envoyée par le client
     * @param EntrepriseInputParser $entrepriseInputParser parser de l'entité Entreprise
     * @param EntrepriseFactory $entrepriseFactory factory de l'entité Entreprise
     * @param EntityManagerInterface $entityManager moteur Doctrine pour écrire en base
     * @return JsonResponse réponse JSON avec le entreprise créé (ou une erreur)
     */
    public function new(
        Request $request,
        EntrepriseInputParser $entrepriseInputParser,
        EntrepriseFactory $entrepriseFactory,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        // On décode le JSON contenu dans le body de la requête.
        // Le JSON contenu dans la requête n'est pas immédiatement lisible par PHP (il s'agit d'un blob de données),
        // on utilise alors la méthode privée utilitaire decodeJson() afin de transformer ce blob de données
        // en tableau lisible par PHP.
        $data = $this->decodeJson($request);

        // On valide puis on formatte les données contenues dans le tableau associatif $data venant du client
        // grâce à notre parser. On s'attend à un éventuel message d'erreur que la méthode validate()
        // peut nous renvoyer, on stocke alors la valeur de ce message.
        $errorMessage = $entrepriseInputParser->validate($data);
        // Si ce n'est pas la valeur null qui a été renvoyée, c'est que la méthode n'a pas pu
        // s'exécuter jusqu'au bout, et qu'il y a donc forcément eu une erreur.
        if ($errorMessage !== null) {
            // On renvoit donc cette fois-ci au client l'erreur en JSON
            return $this->errorResponse($errorMessage, Response::HTTP_BAD_REQUEST);
        }

        // Une fois que les champs contenus dans data sont validés et formatés,
        // on peut demander la création d'une nouvelle entreprise à notre factory
        $entreprise = $entrepriseFactory->create($data);

        // A ce moment précis, on va demander de préserver la valeur de l'objet de la nouvelle entreprise.
        // Cette valeur sera ajoutée en base de données à la table Entreprise correspondante.
        $entityManager->persist($entreprise);

        // On exécute tous les changements qui avaient été mis en attente précédement,
        // donc dans notre cas l'ajout de la nouvelle entreprise à la base de données.
        $entityManager->flush();

        // On retourne l'entreprise créé sous forme JSON sérialisée.
        return $this->json($this->serializeEntreprise($entreprise), Response::HTTP_CREATED);
    }

    // Pour toute requête envoyée sur la route /api/entreprise/{id}, nommée "api_entreprise_show",
    // avec la méthode "GET", la méthode publique show() du contrôleur est exécutée.
    #[Route('/{id}', name: 'api_entreprise_show', methods: ['GET'])]
    /**
     * Permet d'afficher un entreprise
     * @param int $id ID du entreprise à afficher
     * @param EntrepriseRepository $entrepriseRepository repository pour lire en base
     * @return JsonResponse réponse JSON du entreprise sérialisé
     */
    public function show(int $id, EntrepriseRepository $entrepriseRepository): JsonResponse
    {
        // On récupère la ligne correspondant au entreprise dans la table entreprise par son ID,
        // en utilisant la classe utilitaire spécifique au entreprise.
        $entreprise = $entrepriseRepository->find($id);

        // Si aucun entreprise n'est trouvé, on renvoie une erreur 404.
        if (!$entreprise) {
            return $this->errorResponse("Entreprise introuvable.", Response::HTTP_NOT_FOUND);
        }

        // On retourne simplement le entreprise à afficher sérialisé.
        return $this->json($this->serializeEntreprise($entreprise));
    }

    // Pour toute requête envoyée sur la route /api/entreprise/{id}, nommée "api_entreprise_put_item",
    // avec la méthode "PUT", la méthode publique edit() du contrôleur est exécutée.
    #[Route('/{id}', name: 'api_entreprise_put_item', methods: ['PUT'])]
    /**
     * Permet de mettre à jour les informations d'un entreprise
     * @param int $id ID du entreprise à mettre à jour
     * @param Request $request requête envoyée par le client
     * @param EntrepriseInputParser $entrepriseInputParser parser de l'entité Entreprise
     * @param EntrepriseRepository $entrepriseRepository repository pour lire le entreprise à modifier
     * @param EntityManagerInterface $entityManager moteur Doctrine pour écrire les changements
     * @return JsonResponse réponse JSON du entreprise modifié (ou erreur)
     */
    public function edit(
        int $id,
        Request $request,
        EntrepriseInputParser $entrepriseInputParser,
        EntrepriseRepository $entrepriseRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        // On récupère le entreprise en base.
        $entreprise = $entrepriseRepository->find($id);

        // Si aucun entreprise n'est trouvé, on renvoie une erreur.
        if (!$entreprise) {
            return $this->errorResponse("Entreprise introuvable.", Response::HTTP_NOT_FOUND);
        }

        // On décode le body de la requête HTTP du client en tableau associatif PHP
        $data = $this->decodeJson($request);

        // On récupère le nom depuis le JSON la requête (null si non défini)
        $nom = (string) ($data["nom"] ?? null);

        // Si le nom a été défini dans la requête du client
        if ($nom !== null && $nom !== '') {
            // On prépare le message pour une éventuelle erreur de nom
            // (qui sera remplie plus tard par le parser pour faire de la gestion d'erreur).
            $nomError = null;
    
            // On utilise notre paseur de la même façon afin de valider la donnée
            $nom = $entrepriseInputParser->parseNom($nom, false, $nomError);
    
            // Si le nom a été correctement validé
            if ($nom !== null) {
                // On peut utiliser le setter spécifique afin de mettre à jour la donnée
                $entreprise->setNom($nom);
            }
            else {
                // Si le client a voulu mettre à jour une valeur, mais qu'elle n'est pas validée, on renvoi une erreur
                return $this->errorResponse($nomError ?? "Le nom n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        // On récupère le N° de SIRET depuis le JSON la requête (null si non défini)
        $siret = (string) ($data["siret"] ?? null);

        // Si le N° de SIRET a été défini dans la requête du client
        if ($siret !== null && $siret !== '') {
            // On prépare le message pour une éventuelle erreur de N° de SIRET
            // (qui sera remplie plus tard par le parser pour faire de la gestion d'erreur).
            $siretError = null;
    
            // On utilise notre paseur de la même façon afin de valider la donnée
            $siret = $entrepriseInputParser->parseSiret($siret, false, $siretError);
    
            // Si le N° de SIRET a été correctement validé
            if ($siret !== null) {
                // On peut utiliser le setter spécifique afin de mettre à jour la donnée
                $entreprise->setSiret($siret);
            }
            else {
                // Si le client a voulu mettre à jour une valeur, mais qu'elle n'est pas validée, on renvoi une erreur
                return $this->errorResponse($siretError ?? "Le N° de SIRET n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        // On récupère l'adresse depuis le JSON la requête (null si non défini)
        $adresse = (string) ($data["adresse"] ?? null);

        // Si l'adresse a été défini dans la requête du client
        if ($adresse !== null && $adresse !== '') {
            // On prépare le message pour une éventuelle erreur d'adresse
            // (qui sera remplie plus tard par le parser pour faire de la gestion d'erreur).
            $adresseError = null;
    
            // On utilise notre paseur de la même façon afin de valider la donnée
            $adresse = $entrepriseInputParser->parseAdresse($adresse, false, $adresseError);
    
            // Si l'adresse a été correctement validé
            if ($adresse !== null) {
                // On peut utiliser le setter spécifique afin de mettre à jour la donnée
                $entreprise->setAdresse($adresse);
            }
            else {
                // Si le client a voulu mettre à jour une valeur, mais qu'elle n'est pas validée, on renvoi une erreur
                return $this->errorResponse($adresseError ?? "L'adresse n'est pas valide.", Response::HTTP_BAD_REQUEST);
            }
        }

        // Doctrine va générer la requête SQL UPDATE automatiquement lors du flush().
        $entityManager->flush();

        // On retourne le entreprise modifié.
        return $this->json($this->serializeEntreprise($entreprise));
    }

    // Pour toute requête envoyée sur la route /api/entreprise/{id}, nommée "api_entreprise_delete_item",
    // avec la méthode "DELETE", la méthode publique delete() du contrôleur est exécutée.
    #[Route('/{id}', name: 'api_entreprise_delete_item', methods: ['DELETE'])]
    /**
     * Permet de supprimer un entreprise
     * @param int $id ID du entreprise à supprimer
     * @param EntrepriseRepository $entrepriseRepository repository pour lire le entreprise à supprimer
     * @param EntityManagerInterface $entityManager moteur Doctrine pour supprimer en base
     * @return JsonResponse réponse HTTP 204 si OK
     */
    public function delete(int $id, EntrepriseRepository $entrepriseRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // On récupère le entreprise à supprimer.
        $entreprise = $entrepriseRepository->find($id);

        // Si aucun entreprise n'est trouvé, on renvoie une erreur.
        if (!$entreprise) {
            return $this->errorResponse("Entreprise introuvable.", Response::HTTP_NOT_FOUND);
        }

        // On demande à Doctrine de supprimer l'entité.
        $entityManager->remove($entreprise);

        // On exécute la suppression en base.
        $entityManager->flush();

        // On retourne une réponse vide avec le code 204 (No Content).
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Permet de sérialiser un entreprise (objet Doctrine) en tableau PHP simple.
     * Cela permet ensuite à Symfony de renvoyer un JSON propre et contrôlé.
     * @param Entreprise $entreprise entité à sérialiser
     * @return array tableau représentant le entreprise
     */
    private function serializeEntreprise(Entreprise $entreprise): array
    {
        // On retourne un tableau associatif.
        return [
            // On renvoie l'id
            "id" => $entreprise->getId(),
            // Le nom
            "nom" => $entreprise->getNom(),
            // Le N° de SIRET
            "siret" => $entreprise->getSiret(),
            // L'adresse
            "adresse" => $entreprise->getAdresse(),
        ];
    }

    /**
     * Permet de décoder le JSON envoyé par le client dans le body de la requête HTTP.
     * Cette méthode est utilisée pour récupérer les données d’un entreprise envoyées par le frontend
     * (ex: nom, N° de SIRET, adresse).
     *
     * @param Request $request Requête HTTP envoyée par le client
     * @return array|null Tableau PHP contenant les données du entreprise ou tableau vide si JSON invalide
     */
    private function decodeJson(Request $request): ?array
    {
        // On récupère le contenu brut (string) du body de la requête HTTP
        // puis on tente de le décoder en tableau PHP grâce à json_decode().
        $payload = json_decode($request->getContent(), true);

        // On vérifie que le résultat est bien un tableau
        // et que la fonction json_decode() ne contient pas d’erreur de parsing JSON.
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            // Si le JSON est invalide ou vide, on renvoie un tableau vide
            // afin d’éviter des erreurs lors de l’accès aux clés (nom, N° de SIRET, adresse).
            return [];
        }

        // Si le JSON est valide, on retourne le tableau contenant les données du entreprise.
        return $payload;
    }

    /**
     * Permet de retourner une réponse JSON standardisée en cas d’erreur.
     * Cette méthode évite de dupliquer le même code dans tout le contrôleur.
     *
     * @param string $message Message d’erreur à renvoyer au client
     * @param int $status Code HTTP (400, 404, etc.)
     * @return JsonResponse Réponse JSON contenant l’erreur
     */
    private function errorResponse(string $message, int $status): JsonResponse
    {
        // On retourne un JSON contenant le message d’erreur
        // avec le code HTTP correspondant (ex: 400, 404).
        return $this->json(['error' => $message], $status);
    }
}