<?php

namespace App\Factory;

use App\Entity\Entreprise;

class EntrepriseFactory {
    /**
     * Renvoi un objet contenant l'instance de la classe entité Entreprise,
     * rempli avec les données contenues dans `$data`
     * @param array $data tableau associatif contenant les données à attribuer à l'objet `$entreprise`
     * @return Entreprise objet représentant une nouvelle instance de la classe entité Entreprise
     */
    public function create(array $data): Entreprise {
        // On crée une nouvelle instance de l'entité Entreprise.
        $entreprise = (new Entreprise())
            // On fixe le nom avec le setter correspondant
            ->setNom($data["nom_entreprise"])
            // Le N° de SIRET
            ->setSiret($data["siret_entreprise"])
            // Puis l'adresse
            ->setAdresse($data["adresse_entreprise"])
            // Comme chaque setter renvoi la classe Entreprise, on peut immédiatement utiliser
            // un autre setter, ce qui permet de faire du chainage de méthode.
        ;

        // Une fois que les attributs ont été saisis, on peut renvoyer l'objet
        return $entreprise;
    }
}