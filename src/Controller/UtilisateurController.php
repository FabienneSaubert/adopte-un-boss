<?php

namespace App\Controller;
// chemin définie dans le fichier composer.json sur la ligne 35 (App = src)
// src contient notre application 

use App\Entity\Utilisateur;
// permet d’utiliser l’entité Utilisateur, c’est-à-dire la classe PHP qui représente une table dans la base de données

use App\Repository\UtilisateurRepository;
// Un repository est une classe qui sert à interroger la base de données pour une entité spécifique (ici User)
// permet de chercher, filtrer et récupérer les produits en base de données facilement
// Méthodes pratiques : find($id) → trouve un objet par son id
                     // findAll() → récupère tous les objets
                     // findBy([...]) → récupère des objets selon des critères
                     // findOneBy([...]) → récupère un seul objet selon des critères

use Doctrine\ORM\EntityManagerInterface;
// permet d’utiliser EntityManagerInterface, qui est le service principal de Doctrine pour interagir avec la base de données
// permet de lire, créer, mettre à jour ou supprimer des données en utilisant des entités (les classes PHP qui représentent des tables)

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// permet d’utiliser AbstractController, qui est une classe de base pour les contrôleurs
    // grace à AbstractController je peux utiliser directement :   render() → afficher une vue Twig
                                                                // redirectToRoute() → redirection vers une route
                                                                // json() → retourner du JSON
                                                                // getUser() → récupérer l’utilisateur connecté
                                                                // createForm() → créer un formulaire
                                                                // addFlash() → message flash
                                                                // isGranted() → vérifier les permissions

use Symfony\Component\HttpFoundation\Request;
// permet d’utiliser la classe Request, qui représente la requête HTTP envoyée par le client

use Symfony\Component\HttpFoundation\Response;
// permet d’utiliser la classe Response, qui représente la réponse HTTP envoyée au client

use Symfony\Component\Routing\Attribute\Route;
// importe la classe Route pour pouvoir l’utiliser comme attribut au-dessus d’une méthode ou d’une classe

use Symfony\Component\HttpFoundation\JsonResponse;
// permet d’utiliser la classe JsonResponse, qui sert à retourner une réponse au format JSON depuis un contrôleur




#[Route('/utilisateur')]
// C’est une annotation qui définit une route principale pour ce contrôleur
// /utilisateur → c’est le préfixe de toutes les routes dans ce contrôleur

final class UtilisateurController extends AbstractController
// final signifie qu’on ne peut pas hériter de cette classe
// UtilisateurController → nom du contrôleur.
// extends AbstractController → on hérite d’AbstractController pour avoir accès aux méthodes utiles (json(), render(), redirectToRoute(), etc.).
{

//! ==================================================== LISTE DES UTILISATEURS ==============================================================================================

    #[Route(name: 'app_utilisateur_index', methods: ['GET'])]
    // crée une route GET pour lister tous les produits
    public function index(UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        $utilisateurs = array_map(
            fn(Utilisateur $utilisateur) => $this->serialiserUtilisateur($utilisateur),
            // transforme chaque utilisateur en index de tableau grâce à la méthode serializeUser()

            $utilisateurRepository->findAll()
            // récupère tous les utilisateurs en base de données
        );
        return $this->json($utilisateurs);
        // renvoie la réponse au format JSON
        
    }

//! ==================================================== CREER UN NOUVEAU UTILISATEUR ==============================================================================================

    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    // Route /new → pour créer un utilisateur via GET ou POST

    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->decodeJson($request);
        // lit le JSON envoyé par le client

        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        // ! ROLE ?

        $nom = trim((string) ($data["nom"] ?? ""));
        // récupère le nom et supprime les espaces inutiles

        $prenom = trim((string) ($data["prenom"] ?? ""));
        // récupère le prénom et supprime les espaces inutiles

        $email = $this->verifEmail($data['email'] ?? null, true, $emailErreur);
      
        $dateDeNaissance = $this->verifDateNaissance($data['date_de_naissance'] ?? null, true, $naissanceErreur);

        $telephone = $this->verifTelephone($data['telephone'] ?? null, true, $telephoneErreur);
        // Vérifie que l'email : existe
        //                       est un int
        //                       respecte le format attendu → le regex


        $mdp_hash = $this->verifMdp($data['mot_de_passe'] ?? null, true, $mdpErreur);
        // Vérifie que l'email : existe
        //                       est un string
        //                       respecte le format attendu → le regex

        $hashedMdp = password_hash($mdp_hash, PASSWORD_BCRYPT);
        // Hash le mot de passe avec l'algorithme BCRYPT pour le stocker de manière sécurisée


        //!  $statut_inscription ????

        $utilisateur = (new Utilisateur())
        // crée un nouvel objet utilisateur

        ->setPrenom($prenom)
        ->setNom($nom)
        ->setEmail($email)
        ->setDateDeNaissance($dateDeNaissance)
        ->setTelephone($telephone)
        ->setMdpHash($hashedMdp);
        // remplit les propriétés de l'utilisateur

        $entityManager->persist($utilisateur);
        // prépare l’insertion en base

        $entityManager->flush();
        // exécute l’insertion

        return $this->json($this->serialiserUtilisateur($utilisateur), Response::HTTP_CREATED);
        // renvoie le code HTTP 201 (création réussie)
    }

//! ==================================================== AFFICHER UN UTILISATEUR ==============================================================================================


    #[Route('/{id}', name: 'app_utilisateur_show', methods: ['GET'])]
    // Route /utilisateur/{id} → pour afficher un utilisateur précis


    public function show(int $id, UtilisateurRepository $utilisateurRepository): JsonResponse
    // $id → id de l'utilisateur à afficher
    // $utilisateurRepository → accès à la base de données
    // JsonResponse → réponse en JSON
    {
        $utilisateur = $utilisateurRepository->find($id);
        // récupère l'utilisateur par son id
        
        if (!$utilisateur) {
            return $this->errorResponse('Utilisateur non trouvé.', Response::HTTP_NOT_FOUND);
        }

       return $this->json($this->serialiserUtilisateur($utilisateur));
       // Transforme l'utilisateur en tableau, retourne la réponse en JSON
    }

//! ==================================================== MODIFIER UN UTILISATEUR ==============================================================================================


    #[Route('/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $entityManager): JsonResponse
    // $id → id de l'utilisateur à modifier
    // $request → données envoyées par le client
    // $userRepository → accès à la base de données
    // $entityManager → sauvegarde les modifications
    // JsonResponse → réponse en JSON
    {
       $utilisateur = $utilisateurRepository->find($id);
        // Cherche l'utilisateur en base avec son id

        if (!$utilisateur) {
            return $this->errorResponse('Utilisateur non trouvé.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        // Lit le JSON envoyé par le client et le transforme en tableau PHP

        if ($data === null) {
            return $this->errorResponse('JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request ->getMethod() === "PUT";
        // vérifie si la méthode HTTP est PUT (PUT = modification complète)

        //?? ====================================== NOM =====================================================
        if (array_key_exists('nom', $data) || $isPut) {
          // Si le champ last_name est envoyé OU si c’est un PUT (donc obligatoire)

            $nom = trim((string) ($data['nom'] ?? ''));
            // Récupère le nom, supprime les espaces, si absent → chaîne vide

            if ($nom === '') {
            // Vérifie si le nom est vide

                return $this->errorResponse('Le nom est obligatoire.', Response::HTTP_BAD_REQUEST);
                // Retourne une erreur JSON, code HTTP 400
            }
            $utilisateur->setNom($nom);
            // Met à jour le nom de l'utilisateur
        }

        //?? ====================================== PRENOM =====================================================
        if (array_key_exists('prenom', $data) || $isPut) {
        // Si le champ fist_name est envoyé OU si c’est un PUT (donc obligatoire)

            $prenom = trim((string) ($data['prenom'] ?? ''));
            // Récupère le prénom, supprime les espaces, si absent → chaîne vide

            if ($prenom === '') {
            // Vérifie si le prénom est vide

                return $this->errorResponse('Prénom est obligatoire.', Response::HTTP_BAD_REQUEST);
                // Retourne une erreur JSON, code HTTP 400
            }
            $utilisateur->setPrenom($prenom);
            // Met à jour le prénom de l'utilisateur
        }

        //?? ====================================== E-MAIL =====================================================
        if (array_key_exists('email', $data) || $isPut) {
        // Si le champ email est envoyé OU si c’est un PUT (donc obligatoire)

            $emailErreur = null;
            // Variable pour stocker une erreur

            $email = $this->verifEmail($data['email'] ?? null, true, $emailErreur);
            // Vérifie que l'email : existe
            //                       est un string
            //                       respecte le format attendu → le regex

            if ($email === '') {
            // Vérifie si l'email est vide

                return $this->errorResponse('Email est obligatoire.', Response::HTTP_BAD_REQUEST);
                // Retourne une erreur JSON, code HTTP 400
            }

            $utilisateur->setEmail($email);
            // Met à jour l'email de l'utilisateur

        }

        //?? ====================================== DATE DE NAISSANCE =====================================================
        if (array_key_exists('date_de_naissance', $data) || $isPut) {
        // Si le champ date_de_niassance est envoyé OU si c’est un PUT (donc obligatoire)

            $naissanceErreur = null;
            // Variable pour stocker une erreur

            $dateDeNaissance = $this->verifDateNaissance($data['date_de_naissance'] ?? null, true, $naissanceErreur);

            if ($dateDeNaissance === '') {
                // Vérifie si la date de naissance est vide

                return $this->errorResponse('Date de naissance est obligatoire.', Response::HTTP_BAD_REQUEST);
                // Retourne une erreur JSON, code HTTP 400
            }

            $utilisateur->setDateDeNaissance($dateDeNaissance);
            // Met à jour la date de naissance de l'utilisateur
        }

        //?? ====================================== TELEPHONE =====================================================
        if (array_key_exists('telephone', $data) || $isPut) {
        // Si le champ téléphone est envoyé OU si c’est un PUT (donc obligatoire)

            $telephoneErreur = null;
            // Variable pour stocker une erreur

            $telephone = $this->verifTelephone($data['telephone'] ?? null, true, $telephoneErreur);
            // Vérifie que l'email : existe
            //                       est un int
            //                       respecte le format attendu → le regex

            if ($telephone === '') {
                // Vérifie si le numéro est vide

                return $this->errorResponse('Téléphone est obligatoire.', Response::HTTP_BAD_REQUEST);
                // Retourne une erreur JSON, code HTTP 400
            }

            $utilisateur->setTelephone($telephone);
            // Met à jour le numéro de téléphone de l'utilisateur
        }

        //?? ====================================== MOT DE PASSE =====================================================
        if (array_key_exists('mot_de_passe', $data) || $isPut) {
        // Si le champ mot de passe est envoyé OU si c’est un PUT (donc obligatoire)

            $mdp_hash = $this->verifMdp($data['mot_de_passe'] ?? null, true, $mdpErreur);
            // Vérifie que l'email : existe
            //                       est un string
            //                       respecte le format attendu → le regex

            if ($mdp_hash === '') {
            // Vérifie si le mot de passe est vide

                return $this->errorResponse('Password is required.', Response::HTTP_BAD_REQUEST);
                // Retourne une erreur JSON, code HTTP 400
            }

            $hashedMdp = password_hash($mdp_hash, PASSWORD_BCRYPT);
            // Hash le mot de passe avec l'algorithme BCRYPT pour le stocker de manière sécurisée

            $utilisateur->setMdpHash($hashedMdp);
            // Met à jour le mot de passe hashé
        }

        $entityManager->flush();
        // Enregistre les modifications en base de données

        return $this->json($this->serialiserUtilisateur($utilisateur));
        // Transforme l'utilisateur en tableau, retourne la réponse en JSON
    }

//! ==================================================== SUPPRIMER UN PRODUIT ==============================================================================================


    #[Route('/{id}', name: 'app_utilisateur_delete', methods: ['POST'])]

    public function delete(int $id, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $entityManager): JsonResponse
    {
     $utilisateur = $utilisateurRepository->find($id);
        // Cherche l'utilisateur dans la base de données grâce à son id

        if (!$utilisateur) {
            return $this->errorResponse('Utilisateur non trouvé.', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($utilisateur);
        // prépare la suppression

        $entityManager->flush();
        // exécute la suppression

        return $this->json(null, Response::HTTP_NO_CONTENT);
        // Renvoie une réponse sans contenu, code HTTP 204 → suppression réussie, aucun JSON à afficher   
    }

//! ================================================= LES FONCTIONS UTILITAIRES ==================================================================================================================================

    //? ================================================ VERIFICATION DE EMAIL ===================================================================================================================================
    private function verifEmail(mixed $value, bool $required, ?string &$erreur): ?string
    // private → utilisée seulement dans ce contrôleur
    // verifEmail → sert à vérifier l'email
    // $value → l'email reçu (peut être n’importe quoi)
    // $required → indique si l'email est obligatoire
    // &$erreur → variable pour stocker un message d’erreur
    // : ?string → retourne un texte ou null
    {
        if ($value === null || $value === ''){
        // Vérifie si l'email est absent ou vide

            if ($required){
                $erreur = 'Email est obligatoire.';
                // Si l'email' est obligatoire → message d’erreur
            }
            return null;
            // Arrête la fonction → email invalide
        }

        if (!is_string($value)){
            // Si l'email n'est pas un string

            $erreur = "Email n'est pas valide.";
            //Message d’erreur

            return null;
            // Arrête la fonction → email invalide
        }

        $emailRegex = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
        // Regex pour email
        //        Vérifie la présence : - d'un texte avant le @
        //                              - d'un @
        //                              - d'un nom de domaine
        //                              - d'un point et d'une extension

        if (!preg_match($emailRegex, $value)) {
        // Vérifie si l'email respecte le format attendu

            return $this->errorResponse("Format d'email non valide.", Response::HTTP_BAD_REQUEST);
            // Retourne une erreur JSON, code HTTP 400
        }
        return $value; 
    }

    //? ================================================ VERIFICATION DE TELEPHONE ===================================================================================================================================

    private function verifTelephone(mixed $value, bool $required, ?string &$erreur): ?string
    // private → utilisée seulement dans ce contrôleur
    // verifTelephone → sert à vérifier le télephone
    // $value → le téléphone reçu (peut être n’importe quoi)
    // $required → indique si le téléphone est obligatoire
    // &$erreur → variable pour stocker un message d’erreur
    // : ?string → retourne un texte ou null

    {
        if($value === null || $value === ''){
        // Vérifie si le téléphone est absent ou vide
            
            if ($required){
                $erreur = "Téléphone est obligatoire.";
            // Si le téléphone est obligatoire → message d'erreur
            }

            return null;
            // Arrête la fonction → téléphone invalide
        }

        if (!is_string($value)){
        // Si le téléphone n'est pas un string

            $erreur = 'Téléphone doit contenir des chiffres.';
            //Message d’erreur
 
            return null;
            // Arrête la fonction → téléphone invalide
        }

        $telephoneRegex = '/^(?:\+33|0)[67]\d{8}$/';
        // Regex numéro de téléphone français
        // Accepte les numéros : 06XXXXXXXX, 07XXXXXXXX, +336XXXXXXXX, +337XXXXXXXX

        if (!preg_match($telephoneRegex, $value)) {
        // Vérifie si le numéro correspond au format attendu

            return $this->errorResponse('Le format non valide.', Response::HTTP_BAD_REQUEST);
            // Retourne une erreur JSON, code HTTP 400
        }
        return $value;
    }

    //? ==================================== VERIFICATION DE LA DATE DE NAISSANCE ===================================================================================================================================

    private function verifDateNaissance(mixed $value, bool $required, ?string &$erreur): ?\DateTimeInterface
    {
        if ($value === null || $value === '') {
            // Vérifie si la date est absente ou vide

            if ($required) {
                $erreur = 'Date de naissance est obligatoire.';
            }

            return null;
        }

        if (!is_string($value)) {
            // Si la date n'est pas une chaîne

            $erreur = 'Date de naissance invalide.';
            return null;
        }

        try {
            $date = new \DateTime($value);
        } catch (\Exception $e) {
            $erreur = 'Format de date invalide (YYYY-MM-DD).';
            return null;
        }

        // Empêche une date future
        if ($date > new \DateTime()) {
            $erreur = 'La date de naissance ne peut pas être dans le futur.';
            return null;
        }
        return $date;
    }


    //? ==================================== VERIFICATION DE MOT DE PASSE ===================================================================================================================================
    private function verifMdp(mixed $value, bool $required, ?string &$erreur): ?string
    // private → utilisée seulement dans ce contrôleur
    // verifMdp → sert à vérifier le mot de passe
    // $value → le mot de passe reçu (peut être n’importe quoi)
    // $required → indique si le mot de passe est obligatoire
    // &$erreur → variable pour stocker un message d’erreur
    // : ?string → retourne un texte ou null
    {
        if($value === null || $value === ''){
        // Vérifie si le mot de passe est absent ou vide

            if ($required) {
                $erreur = 'Mot de passe est obligatoire.';
            // Si le mot de passe est obligatoire → message d’erreur
            }
 
            return null;
            // Arrête la fonction → mot de passe invalide
        }

        if (!is_string($value)){
        // Si l'email n'est pas un string

            $erreur = "Mot de passe n'est pas valide.";
            //Message d’erreur

            return null;
            // Arrête la fonction → email invalide
        }

        $minLength = 8;
        // Définit la longueur minimale requise

        if (strlen($value) < $minLength) {
        // Vérifie si le mot de passe est trop court

            return $this->errorResponse('Le mot de passe doit contenir au moins 8 caractères.', Response::HTTP_BAD_REQUEST);
            // Retourne une erreur JSON, code HTTP 400
        }

        $mdpRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/';
        // Regex de sécurité du mot de passe
        //              Vérifie que le mot de passe contient :  - au moins une minuscule
        //                                                      - au moins une majuscule
        //                                                      - au moins un chiffre
        //                                                      - au moins un caractère spécial

        if (!preg_match($mdpRegex, $value)) {
        // Vérifie si le mot de passe respecte le format 

            return $this->errorResponse('Le mot de passe doit contenire une minuscule, une majuscule, une chiffre et un caractère spécial.', Response::HTTP_BAD_REQUEST);
            // Retourne une erreur JSON, code HTTP 400
        }
        return $value; 
    }

    //? ================================ TRANSFORMATION D'UN OBJET User EN TABLEAU SIMPLE POUR JSON ==============================================================================================

    private function serialiserUtilisateur(Utilisateur $utilisateur): array
    // private → cette fonction est utilisée uniquement dans ce contrôleur
    // serialiserUtilisateur → transforme un objet Utilisateur
    // Utilisateur $utilisateur → le utilissateur à transformer
    // : array → la fonction retourne un tableau
    
    {
        return [
        // On renvoie un tableau PHP

            "id"=>$utilisateur->getId(),
            // "id" → clé du tableau
            // $user->getId() → récupère l’identifiant de l'utilisateur

            "role"=>$utilisateur->getRole(),

            "nom"=>$utilisateur->getNom(),
            // Récupère le nom de l'utilisateur

            "prenom"=>$utilisateur->getPrenom(),
            // Récupère le prénom de l'utilisateur

            "email"=>$utilisateur->getEmail(),
            // Récupère l'email de l'utilisateur

            "date_de_naissance"=>$utilisateur->getDateDeNaissance()?->format('Y-m-d'),
            // Récupère la date de naissance de l'utilisateur

            "telephone"=>$utilisateur->getTelephone(),
            // Récupère le télephone de l'utilisateur

            "statut_d_inscription"=>$utilisateur->getStatutInscription()
            // Récupère le statut de l'inscription
        ];
        // Le tableau est retourné
    }


    //? ============================= LECTURE DU CONTENU JSON ENVOYE PAR LE CLIENT ET TRANSFORMATION EN TABLEAU ==============================================================================================
    private function decodeJson(Request $request): ?array
    // private → utilisable seulement dans ce contrôleur
    // decodeJson → lit du JSON
    // Request $request → la requête envoyée par le client
    // : ?array → retourne un tableau ou null
    
    {
        $payload = json_decode($request->getContent(), true);
        // $request->getContent() → récupère le contenu brut (JSON)
        // json_decode(..., true) → transforme le JSON en tableau PHP
        // Le résultat est stocké dans $payload
 
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
        // Vérifie deux choses : le résultat est bien un tableau
        //                       le JSON ne contient aucune erreur
            return null;
            // Si le JSON est invalide → on retourne null
            // Le contrôleur saura que les données sont mauvaises
        }
 
        return $payload;
        // Si tout est correct → on retourne le tableau
    }


    //? ================================= RENVOYER DES ERREURS EN JSON AVEC UN CODE HTTP ==============================================================================================
    private function errorResponse(string $message, int $status): JsonResponse
    // private → utilisable seulement dans ce contrôleur
    // errorResponse → sert à renvoyer une erreur
    // $message → le texte de l’erreur
    // $status → le code HTTP (400, 404, 500…)
    // JsonResponse → la réponse est en JSON

    {
        return $this->json(['error' => $message], $status);
        // $this->json(...) → méthode Symfony pour créer une réponse JSON
        // ['error' => $message] → contenu JSON envoyé au client
        // $status → code HTTP de la réponse
    }

}
