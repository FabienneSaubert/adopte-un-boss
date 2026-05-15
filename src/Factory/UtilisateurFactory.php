<?php

namespace App\Factory;

use App\Entity\Candidat;
use App\Entity\Recruteur;
use App\Entity\Utilisateur;

class UtilisateurFactory
{
    /**
     * Renvoi un objet contenant l'instance de la classe entité Entreprise, rempli avec les
     * données contenues dans `$data`. A utiliser uniquement depuis les contrôleurs Candidat ou Recruteur.
     * 
     * @param array $data tableau associatif contenant les données à attribuer à l'objet `$entreprise`
     * @return Candidat|Recruteur objet représentant une nouvelle instance de la classe entité Entreprise
     */
    public function create(string $role, array $data): Candidat|Recruteur
    {
        // On crée dans un premier temps une nouvelle instance de l'entité Utilisateur
        $utilisateur = (new Utilisateur())
            ->setRole($data["role"])
            ->setNom($data["nom"])
            ->setPrenom($data["prenom"])
            ->setEmail($data["email"])
            ->setDateDeNaissance($data["date_de_naissance"])
            ->setTelephone($data["telephone"])
            ->setMdpHash($data["mdp_hash"])
            // Pas besoin d'attribuer la valeur du statut de l'inscription car c'est déjà "En attente" par défaut,
            // on utilise donc le DEFAULT venant de la base de données
        ;

        return match ($role) {
            'Candidat' => (new Candidat())
                ->setProfilVisible($data["profil_visible"])
                ->setInfosVisibles($data["infos_visibles"])
                ->setUuid($data["uuid"])
                ->setAccroche($data["accroche"])
                ->setCvFilename($data["cvFilename"])
                ->setNiveauEtude($data["niveau_etude"])
                ->setUtilisateur($utilisateur),
            'Recruteur' => (new Recruteur())
                ->setPoste($data["poste"])
                ->setEmailPro($data["email_pro"])
                ->setTelephonePro($data["telephone_pro"])
                ->setEntreprise($data["entreprise"])
                ->setUtilisateur($utilisateur),
            default => throw new \Exception("Rôle utilisateur non supporté.")
        };
    }
}
