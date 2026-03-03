<?php

namespace App\Service;

use App\Entity\Candidat;
use App\Entity\Offre;

class MatchingService
{
    /**
     * Calcule un score de matching entre un candidat et une liste d'offres
     *
     * @param Candidat $candidat instance du candidat connecté
     * @param Offre[] $offres adresse mémoire du tableau des offres, celui-ci sera directement modifié par la méthode
     */
    public function match(Candidat $candidat, array &$offres): void
    {
        // Pour chaque offre
        foreach ($offres as $offre) {
            // Calculer son score
            $score = $this->calculerScore($candidat, $offre);

            // Inscrire le score dans le setter approprié
            $offre->setScore($score);
        }

        // Tri spécial en utilisant une fonction pour le tri par score décroissant
        usort($offres, fn(Offre $a, Offre $b) => $a->getScore() < $b->getScore());
    }

    private function calculerScore(Candidat $candidat, Offre $offre): int
    {
        // Initialisation du score
        $score = 0;
        
        // * Calcul du score - département *
        // Si le département a été défini des deux côtés
        if ($candidat->getDepartement() !== null && $offre->getDepartement() !== null) {
            // Si l'offre est dans le même département que le candidat
            if ($candidat->getDepartement()->getId() === $offre->getDepartement()->getId()) {
                // Si le coefficient du département est correctement défini
                if ($offre->getCoeffDepartement() !== null) {
                    // On le prend pour le calcul du score
                    $score += $offre->getCoeffDepartement();
                }
            }
        }

        // * Calcul du score - niveau d'études *
        // Si le niveau d'études a été défini chez le candidat
        if ($candidat->getNiveauEtude() !== null) {
            // Si l'offre correspond au même niveau d'étude que le candidat
            if ($candidat->getNiveauEtude() === $offre->getNiveauEtudes()) {
                // Si le coefficient du niveau d'études est correctement défini
                if ($offre->getCoeffNiveauEtudes() !== null) {
                    // On le prend pour le calcul du score
                    $score += $offre->getCoeffNiveauEtudes();
                }
            }
        }

        // * Calcul du score - compétences *
        // Pour chaque compétence du candidat
        foreach ($candidat->getCompetences() as $competence) {
            // Pour chaque selection de compétence de l'offre
            foreach ($offre->getSelectionCompetences() as $selectionCompetence) {
                // Si l'ID de la compétence du candidat correspond à l'ID de la compétence seléctionnée
                if ($competence->getId() === $selectionCompetence->getCompetence()?->getId()) {
                    // Si le coefficient de la seléction de la compétence est correctement défini
                    if ($selectionCompetence->getCoeffCompetence() !== null) {
                        // On le prend pour le calcul du score
                        $score += $selectionCompetence->getCoeffCompetence();
                    }
                }
            }
        }

        // Renvoi du score calculé selon l'algorithme de matching
        return $score;
    }
}
