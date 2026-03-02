<?php

namespace App\ValueObject;

// Classe représentant le type d'erreur que l'on souhaite renvoyer
use InvalidArgumentException;

// Classe permettant de faire des instances de l'attribut emailPro.
// Elle contient la valeur avec son typage, ainsi que sa validation.
class EmailPro
{
    // Attribut privé représentant la valeur de l'email pro
    private string $value;

    /**
     * Méthode permettant de créer un email professionnel
     * 
     * Celle-ci est obligatoire pour créer une instance de l'objet. Cela pourrait
     * se faire via le constructeur, mais le but est de donner un sens métier à
     * la création, en indiquant vraiment comment créer un objet. On dit ici que
     * l'on peut créer un email professionnel en passant par une chaîne de caractères.
     * 
     * @param mixed $emailPro valeur souhaitée de l'email pro
     * @param bool $required permet de déterminer le message d'erreur 
     * @throws InvalidArgumentException type d'erreur renvoyé lors d'une erreur de saisie
     * @return EmailPro objet représentant l'instance de la classe EmailPro
     */
    public static function fromString(?string $emailPro, bool $required = false): self
    {
        // Si l'email n'est pas correctement défini
        if ($emailPro === null || $emailPro === '') {
            // Si l'email est requis
            if ($required) {
                // On renvoit l'erreur spécifique
                throw new InvalidArgumentException("L'email profesionnel est requis.");
            }
            // Sinon on renvoit une erreur de non validité
            throw new InvalidArgumentException("L'email professionnel n'est pas valide.");
        }

        // Nettoyage des espaces avant et après
        $emailPro = trim($emailPro);

        // Suppression des caractères illégaux
        $emailPro = filter_var($emailPro, FILTER_SANITIZE_EMAIL);

        // Si l'email fait plus de 100 caractères (limite en base de données)
        if (mb_strlen($emailPro) > 100) {
            // On renvoit l'erreur spécifique
            throw new InvalidArgumentException("L'email profesionnel ne peut pas dépasser 100 caractères.");
        }

        // Si le format de l'email n'est pas valide (on se fie au filtre PHP)
        if (!filter_var($emailPro, FILTER_VALIDATE_EMAIL)) {
            // On renvoit l'erreur spécifique
            throw new InvalidArgumentException("Le format de l'email profesionnel n'est pas valide.");
        }

        // Si toute la validation a été passée avec succès, on peut renvoyer une nouvelle
        // instance de la classe, en utilisant le constructeur avec le mot clé self qui
        // fait référence à la classe courante soit EmailPro
        return new self($emailPro);
    }

    // Constructeur appelé dans la méthode fromString() à la fin,
    // son seul rôle étant d'attribuer la valeur de l'email pro.
    private function __construct(string $email)
    {
        $this->value = $email;
    }

    // Méthode publique permettant de récupérer la valeur stockeé
    // dans l'attribut de la classe, ce qui joue un rôle de getter.
    public function asString(): string
    {
        return $this->value;
    }
}