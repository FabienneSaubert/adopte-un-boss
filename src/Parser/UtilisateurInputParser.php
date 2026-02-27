<?php

namespace App\Parser;

use App\Enum\RoleUtilisateur;

class UtilisateurInputParser
{
    /**
     * Permet de valider les champs envoyés par le client concernant l'entité Utilisateur
     * 
     * @param array $data adresse mémoire du tableau associatif contenant tous les champs du JSON body d'une requête client
     * @return string|null message d'erreur lors de la validation si non null
     */
    public function validate(array &$data): ?string
    {
        if (!array_key_exists('role', $data)) {
            return "Le champ rôle est obligatoire.";
        }

        $data['role'] = RoleUtilisateur::tryFrom($data['role']);

        if (!$data['role']) {
            return 'Rôle utilisateur invalide.';
        }

        // TryFrom = méthode pour énum, permet de comparer l'entrée avec les valeurs présentes dans
        // l'enum niveau_etude. Si match -> retourne une instance, sinon retourne null;
        // Ici, je compare la valeur de la requête à la valeur "niveau_etude" avec les enum possible
        // On s'assure que l'entrée utilisateur soit bonne et non null.


        $data["nom"] = trim((string) ($data["nom"] ?? ""));
        // récupère le nom et supprime les espaces inutiles

        $data["prenom"] = trim((string) ($data["prenom"] ?? ""));
        // récupère le prénom et supprime les espaces inutiles

        $emailErreur = null;
        $data['email'] = $this->verifEmail($data['email'] ?? null, true, $emailErreur);
        if ($data['email'] === null) {
            return $emailErreur ?? "Email est obligatoire";
        }

        $naissanceErreur = null;
        $data['date_de_naissance'] = $this->verifDateNaissance($data['date_de_naissance'] ?? null, true, $naissanceErreur);
        if ($data['date_de_naissance'] === null) {
            return $naissanceErreur ?? "Date de naissance est obligatoire";
        }

        $telephoneErreur = null;
        $data['telephone'] = $this->verifTelephone($data['telephone'] ?? null, true, $telephoneErreur);
        // Vérifie que le téléphone : existe
        //                           est un string
        //                           respecte le format attendu → le regex
        if ($data['telephone'] === null) {
            return $telephoneErreur ?? "Telephone est obligatoire";
        }

        $mdpErreur = null;
        $mdp_hash = $this->verifMdp($data['mot_de_passe'] ?? null, true, $mdpErreur);
        // Vérifie que le mot de passe : existe
        //                              est un string
        //                              respecte le format attendu → le regex
        if ($mdp_hash === null) {
            return $mdpErreur ?? "Mot de passe est obligatoire";
        }
        $data["mdp_hash"] = password_hash($mdp_hash, PASSWORD_BCRYPT);
        // Hash le mot de passe avec l'algorithme BCRYPT pour le stocker de manière sécurisée

        return null;
    }

    //! ================================================= LES FONCTIONS UTILITAIRES ==================================================================================================================================

    //? ================================================ VERIFICATION DE EMAIL ===================================================================================================================================
    public function verifEmail(mixed $value, bool $required, ?string &$erreur): ?string
    // private → utilisée seulement dans ce contrôleur
    // verifEmail → sert à vérifier l'email
    // $value → l'email reçu (peut être n’importe quoi)
    // $required → indique si l'email est obligatoire
    // &$erreur → variable pour stocker un message d’erreur
    // : ?string → retourne un texte ou null
    {
        if ($value === null || $value === '') {
            // Vérifie si l'email est absent ou vide

            if ($required) {
                $erreur = 'Email est obligatoire.';
                // Si l'email' est obligatoire → message d’erreur
            }
            return null;
            // Arrête la fonction → email invalide
        }

        if (!is_string($value)) {
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

            $erreur = "Format d'email non valide.";
            //Message d’erreur

            return null;
            // Arrête la fonction → email invalide
        }
        return $value;
    }

    //? ================================================ VERIFICATION DE TELEPHONE ===================================================================================================================================

    public function verifTelephone(mixed $value, bool $required, ?string &$erreur): ?string
    // private → utilisée seulement dans ce contrôleur
    // verifTelephone → sert à vérifier le télephone
    // $value → le téléphone reçu (peut être n’importe quoi)
    // $required → indique si le téléphone est obligatoire
    // &$erreur → variable pour stocker un message d’erreur
    // : ?string → retourne un texte ou null

    {
        if ($value === null || $value === '') {
            // Vérifie si le téléphone est absent ou vide

            if ($required) {
                $erreur = "Téléphone est obligatoire.";
                // Si le téléphone est obligatoire → message d'erreur
            }

            return null;
            // Arrête la fonction → téléphone invalide
        }

        if (!is_string($value)) {
            // Si le téléphone n'est pas un string

            $erreur = 'Téléphone doit contenir des chiffres.';
            //Message d’erreur

            return null;
            // Arrête la fonction → téléphone invalide
        }

        // Retrait des espaces
        $value = str_replace(' ', '', $value);

        $telephoneRegex = '/^(?:\+33|0)[67]\d{8}$/';
        // Regex numéro de téléphone français
        // Accepte les numéros : 06XXXXXXXX, 07XXXXXXXX, +336XXXXXXXX, +337XXXXXXXX

        if (!preg_match($telephoneRegex, $value)) {
            // Vérifie si le numéro correspond au format attendu

            $erreur = 'Le format du téléphone est non valide.';
            //Message d’erreur

            return null;
            // Arrête la fonction → téléphone invalide
        }
        return $value;
    }

    //? ==================================== VERIFICATION DE LA DATE DE NAISSANCE ===================================================================================================================================

    public function verifDateNaissance(mixed $value, bool $required, ?string &$erreur): ?\DateTimeImmutable
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
            $date = new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            $erreur = 'Format de date invalide (YYYY-MM-DD).';
            return null;
        }

        // Empêche une date future
        if ($date > new \DateTimeImmutable()) {
            $erreur = 'La date de naissance ne peut pas être dans le futur.';
            return null;
        }
        return $date;
    }


    //? ==================================== VERIFICATION DE MOT DE PASSE ===================================================================================================================================
    public function verifMdp(mixed $value, bool $required, ?string &$erreur): ?string
    // private → utilisée seulement dans ce contrôleur
    // verifMdp → sert à vérifier le mot de passe
    // $value → le mot de passe reçu (peut être n’importe quoi)
    // $required → indique si le mot de passe est obligatoire
    // &$erreur → variable pour stocker un message d’erreur
    // : ?string → retourne un texte ou null
    {
        if ($value === null || $value === '') {
            // Vérifie si le mot de passe est absent ou vide

            if ($required) {
                $erreur = 'Mot de passe est obligatoire.';
                // Si le mot de passe est obligatoire → message d’erreur
            }

            return null;
            // Arrête la fonction → mot de passe invalide
        }

        if (!is_string($value)) {
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

            $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
            //Message d’erreur

            return null;
            // Arrête la fonction → téléphone invalide
        }

        $mdpRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).+$/';
        // Regex de sécurité du mot de passe
        //              Vérifie que le mot de passe contient :  - au moins une minuscule
        //                                                      - au moins une majuscule
        //                                                      - au moins un chiffre
        //                                                      - au moins un caractère spécial

        if (!preg_match($mdpRegex, $value)) {
            // Vérifie si le mot de passe respecte le format 

            $erreur = 'Le mot de passe doit contenir une minuscule, une majuscule, une chiffre et un caractère spécial.';
            //Message d’erreur

            return null;
            // Arrête la fonction → téléphone invalide
        }
        return $value;
    }
}
