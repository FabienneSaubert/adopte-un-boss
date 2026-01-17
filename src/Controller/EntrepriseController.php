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
     * @param EntityManagerInterface $entityManager moteur Doctrine pour écrire en base
     * @return JsonResponse réponse JSON avec le entreprise créé (ou une erreur)
     */
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // On décode le JSON contenu dans le body de la requête.
        // Le JSON contenu dans la requête n'est pas immédiatement lisible par PHP (il s'agit d'un blob de données),
        // on utilise alors la méthode privée utilitaire decodeJson() afin de transformer ce blob de données
        // en tableau lisible par PHP.
        $data = $this->decodeJson($request);

        // On prépare le message pour une éventuelle erreur de nom
        // (qui sera remplie plus tard par le parser pour faire de la gestion d'erreur).
        $nomError = null;

        // Pour la validation du nom d'une entreprise, on utilise la méthode privée utilitaire parseNom()
        // qui nous permet de récupérer un nom correctement défini.
        // On envoit donc le nom contenu à l'index "nom" dans le tableau $data, ou null s'il n'est pas défini, formaté en string,
        // puis le fait que le nom doit être défini (required=true),
        // ainsi que l'adresse mémoire de la variable $nomError, afin que la méthode privée puisse écrire sa valeur.
        $nom = $this->parseNom((string) ($data["nom"] ?? null), true, $nomError);

        // Si le nom n'a pas été correctement validé, on renvoie une erreur.
        if ($nom === null) {
            // On retourne une erreur spécifique au nom.
            return $this->errorResponse($nomError ?? "Le nom n'est pas valide.", Response::HTTP_BAD_REQUEST);
        }

        // On prépare le message pour une éventuelle erreur de N° de SIRET
        // (qui sera remplie plus tard par le parser pour faire de la gestion d'erreur).
        $siretError = null;

        // Pour la validation du N° de SIRET d'une entreprise, on utilise la méthode privée utilitaire parseSiret()
        // qui nous permet de récupérer un N° de SIRET correctement défini.
        // On envoit donc le N° de SIRET contenu à l'index 'siret' dans le tableau $data, ou null s'il n'est pas défini,
        // puis le fait que le N° de SIRET doit être défini (required=true),
        // ainsi que l'adresse mémoire de la variable $siretError, afin que la méthode privée puisse écrire sa valeur.
        $siret = $this->parseSiret($data["siret"] ?? null, true, $siretError);

        // Si le N° de SIRET n'a pas été correctement validé, on renvoie une erreur.
        if ($siret === null) {
            // On retourne une erreur spécifique au N° de SIRET.
            return $this->errorResponse($siretError ?? 'N° de SIRET invalide.', Response::HTTP_BAD_REQUEST);
        }

        // On prépare le message pour une éventuelle erreur d'adresse
        // (qui sera remplie plus tard par le parser pour faire de la gestion d'erreur).
        $adresseError = null;

        // Pour la validation de l'adresse d'une entreprise, on utilise la méthode privée utilitaire parseAdresse()
        // qui nous permet de récupérer un adresse correctement défini.
        // On envoit donc l'adresse contenu à l'index 'adresse' dans le tableau $data, ou null s'il n'est pas défini,
        // puis le fait que l'adresse doit être défini (required=true),
        // ainsi que l'adresse mémoire de la variable $adresseError, afin que la méthode privée puisse écrire sa valeur.
        $adresse = $this->parseAdresse($data["adresse"] ?? null, true, $adresseError);

        // Si l'adresse n'a pas été correctement validé, on renvoie une erreur.
        if ($adresse === null) {
            // On retourne une erreur spécifique au adresse.
            return $this->errorResponse($adresseError ?? "Adresse invalide.", Response::HTTP_BAD_REQUEST);
        }

        // On crée une nouvelle instance de l'entité Entreprise.
        $entreprise = (new Entreprise())
            // On fixe le nom avec le setter correspondant
            ->setNom($nom)
            // Le N° de SIRET
            ->setSiret($siret)
            // Puis l'adresse
            ->setAdresse($adresse);
        // Comme chaque setter renvoi la classe Entreprise, on peut immédiatement utiliser
        // un autre setter, ce qui permet de faire du chainage de méthode.

        // A ce moment précis, on va demander de préserver la valeur de l'objet de la nouvelle entreprise.
        // Cette valeur sera ajoutée en base de données à la table Entreprise correspondante.
        $entityManager->persist($entreprise);

        // On exécute tous les changements qui avaient été mis en attente précédement,
        // donc dans notre cas l'ajout de la nouvelle entreprise à la base de données.
        $entityManager->flush();

        // On retourne le entreprise créé sous forme JSON sérialisée.
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
     * @param EntrepriseRepository $entrepriseRepository repository pour lire le entreprise à modifier
     * @param EntityManagerInterface $entityManager moteur Doctrine pour écrire les changements
     * @return JsonResponse réponse JSON du entreprise modifié (ou erreur)
     */
    public function edit(int $id, Request $request, EntrepriseRepository $entrepriseRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // On récupère le entreprise en base.
        $entreprise = $entrepriseRepository->find($id);

        // Si aucun entreprise n'est trouvé, on renvoie une erreur.
        if (!$entreprise) {
            return $this->errorResponse("Entreprise introuvable.", Response::HTTP_NOT_FOUND);
        }

        // On décode le body de la requête HTTP du client en tableau associatif PHP
        $data = $this->decodeJson($request);
        // Si le décodage a échoué
        if ($data === null) {
            // On retourne une erreur au client
            return $this->errorResponse("Données dans le JSON body invalides.", Response::HTTP_BAD_REQUEST);
        }

        // On récupère le nom depuis le JSON la requête (null si non défini)
        $nom = (string) ($data["nom"] ?? null);

        // Si le nom a été défini dans la requête du client
        if ($nom !== null && $nom !== '') {
            // On prépare le message pour une éventuelle erreur de nom
            // (qui sera remplie plus tard par le parser pour faire de la gestion d'erreur).
            $nomError = null;
    
            // On utilise notre paseur de la même façon afin de valider la donnée
            $nom = $this->parseNom($nom, false, $nomError);
    
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
            $siret = $this->parseSiret($siret, false, $siretError);
    
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
            $adresse = $this->parseAdresse($adresse, false, $adresseError);
    
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
     * Permet de valider et normaliser le nom d’un entreprise.
     * Cette méthode s'assure que le nom de l'entreprise envoyée par le client
     * est bien compris entre 1 et 60 caractères.
     * 
     * @param mixed $value Valeur brute reçue (souvent une string venant du JSON)
     * @param bool $required Indique si le nom est obligatoire (true pour création, false pour édition)
     * @param mixed $error Variable passée par référence pour contenir le message d’erreur éventuel
     * @return string|null nom formaté ou null en cas d’erreur
     */
    private function parseNom(mixed $value, bool $required, ?string &$error)
    {
        // Si le nom est absent ou vide
        if ($value === null || $value === '') {
            // Si l'adresse est obligatoire (cas d’une nouvelle entreprise),
            // on définit un message d’erreur explicite.
            if ($required) {
                $error = "Le nom est requis.";
            }

            // On retourne null pour indiquer que le nom n’est pas valide.
            return null;
        }

        // Si la taille du nom est au dessus de la limite fixée en base de données
        // On utilise le préfixe "mb_" afin de prévoir les chaînes de caractères multi-octets
        if (mb_strlen($value) > 60) {
            $error = "Le nom ne peut pas dépasser 60 caractères.";
            return null;
        }

        // On retourne le nom
        return $value;
    }

    /**
     * Permet de valider et normaliser le N° de SIRET d’un entreprise.
     * Cette méthode s’assure que le N° de SIRET envoyé par le client est bien valide.
     *
     * @param mixed $value Valeur brute reçue (souvent une string venant du JSON)
     * @param bool $required Indique si le N° de SIRET est obligatoire (true pour création, false pour édition)
     * @param mixed $error Variable passée par référence pour contenir le message d’erreur éventuel
     * @return string|null N° de SIRET formaté ou null en cas d’erreur
     */
    private function parseSiret(mixed $value, bool $required, ?string &$error): ?string
    {
        // Si la valeur est absente ou vide (par exemple N° de SIRET non envoyé dans le JSON)
        if ($value === null || $value === '') {
            // Si le N° de SIRET est obligatoire (cas d’une nouvelle entreprise),
            // on définit un message d’erreur explicite.
            if ($required) {
                $error = "Le N° de SIRET est requis.";
            }

            // On retourne null pour indiquer que le N° de SIRET n’est pas valide.
            return null;
        }

        // On enlève tous les espaces dans le numéro afin de pouvoir correctement valider sa taille après
        $value = str_replace(' ','',$value);

        // Si la taille est exactement celle d'un N° de SIRET
        if (!is_numeric($value) || strlen($value) !== 14) {
            $error = "Le N° de SIRET doit faire exactement 14 chiffres.";
            return null;
        }

        // Si tout est valide, on retourne le N° de SIRET formaté
        return $value;
    }

    /**
     * Permet de valider et normaliser l'adresse d’un entreprise.
     * Cette méthode s'assure que l'adresse de l'entreprise envoyée par le client
     * est bien compris entre 1 et 60 caractères.
     * 
     * @param mixed $value Valeur brute reçue (souvent une string venant du JSON)
     * @param bool $required Indique si l'adresse est obligatoire (true pour création, false pour édition)
     * @param mixed $error Variable passée par référence pour contenir le message d’erreur éventuel
     * @return string|null adresse formaté ou null en cas d’erreur
     */
    private function parseAdresse(mixed $value, bool $required, ?string &$error)
    {
        // Si l'adresse est absente ou vide
        if ($value === null || $value === '') {
            // Si l'adresse est obligatoire (cas d’une nouvelle entreprise),
            // on définit un message d’erreur explicite.
            if ($required) {
                $error = "L'adresse est requise.";
            }

            // On retourne null pour indiquer que l'adresse n’est pas valide.
            return null;
        }

        // Si la taille du adresse est au dessus de la limite fixée en base de données
        // On utilise le préfixe "mb_" afin de prévoir les chaînes de caractères multi-octets
        if (mb_strlen($value) > 100) {
            $error = "L'adresse ne peut pas dépasser les 100 caractères.";
            return null;
        }

        // On retourne l'adresse
        return $value;
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