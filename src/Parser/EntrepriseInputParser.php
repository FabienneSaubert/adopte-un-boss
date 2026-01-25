<?php

namespace App\Parser;

class EntrepriseInputParser {
    /**
     * Permet de valider les champs envoyés par le client concernant l'entité Entreprise
     * 
     * @param array $data tableau associatif contenant tous les champs du JSON body d'une requête client
     * @return string|null message d'erreur lors de la validation si non null
     */
    public function validate(array &$data): ?string {
        // On prépare le message pour une éventuelle erreur de nom
        // (qui sera remplie plus tard par le parser pour faire de la gestion d'erreur).
        $nomError = null;

        // Pour la validation du nom d'une entreprise, on utilise la méthode privée utilitaire parseNom()
        // qui nous permet de récupérer un nom correctement défini.
        // On envoit donc le nom contenu à l'index "nom" dans le tableau $data, ou null s'il n'est pas défini, formaté en string,
        // puis le fait que le nom doit être défini (required=true),
        // ainsi que l'adresse mémoire de la variable $nomError, afin que la méthode privée puisse écrire sa valeur.
        $data["nom_entreprise"] = $this->parseNom((string) ($data["nom_entreprise"] ?? null), true, $nomError);

        // Si le nom n'a pas été correctement validé, on renvoie une erreur.
        if ($data["nom_entreprise"] === null) {
            // On retourne une erreur spécifique au nom.
            return $nomError ?? "Le nom n'est pas valide.";
        }

        // On prépare le message pour une éventuelle erreur de N° de SIRET
        // (qui sera remplie plus tard par le parser pour faire de la gestion d'erreur).
        $siretError = null;

        // Pour la validation du N° de SIRET d'une entreprise, on utilise la méthode privée utilitaire parseSiret()
        // qui nous permet de récupérer un N° de SIRET correctement défini.
        // On envoit donc le N° de SIRET contenu à l'index 'siret' dans le tableau $data, ou null s'il n'est pas défini,
        // puis le fait que le N° de SIRET doit être défini (required=true),
        // ainsi que l'adresse mémoire de la variable $siretError, afin que la méthode privée puisse écrire sa valeur.
        $data["siret_entreprise"] = $this->parseSiret($data["siret_entreprise"] ?? null, true, $siretError);

        // Si le N° de SIRET n'a pas été correctement validé, on renvoie une erreur.
        if ($data["siret_entreprise"] === null) {
            // On retourne une erreur spécifique au N° de SIRET.
            return $siretError ?? "N° de SIRET invalide.";
        }

        // On prépare le message pour une éventuelle erreur d'adresse
        // (qui sera remplie plus tard par le parser pour faire de la gestion d'erreur).
        $adresseError = null;

        // Pour la validation de l'adresse d'une entreprise, on utilise la méthode privée utilitaire parseAdresse()
        // qui nous permet de récupérer un adresse correctement défini.
        // On envoit donc l'adresse contenu à l'index 'adresse' dans le tableau $data, ou null s'il n'est pas défini,
        // puis le fait que l'adresse doit être défini (required=true),
        // ainsi que l'adresse mémoire de la variable $adresseError, afin que la méthode privée puisse écrire sa valeur.
        $data["adresse_entreprise"] = $this->parseAdresse($data["adresse_entreprise"] ?? null, true, $adresseError);

        // Si l'adresse n'a pas été correctement validé, on renvoie une erreur.
        if ($data["adresse_entreprise"] === null) {
            // On retourne une erreur spécifique au adresse.
            return $adresseError ?? "Adresse invalide.";
        }

        // On retourne un message d'erreur null s'il n'y a eu aucune erreur
        return null;
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
    public function parseNom(mixed $value, bool $required, ?string &$error)
    {
        // On effectue un nettoyage au préalable en enlevant les éventuels espaces avant et après
        $value = trim($value);

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
    public function parseSiret(mixed $value, bool $required, ?string &$error): ?string
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
    public function parseAdresse(mixed $value, bool $required, ?string &$error)
    {
        // On effectue un nettoyage au préalable en enlevant les éventuels espaces avant et après
        $value = trim($value);

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
}