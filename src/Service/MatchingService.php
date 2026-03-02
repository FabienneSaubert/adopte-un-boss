<?php

namespace App\Service;

use App\Entity\Candidat;
use App\Entity\Offre;

class MatchingService
{
    /**
     * Calcule un score de matching entre un candidat et une liste d'offres
     *
     * @param Candidat $candidat
     * @param Offre[] $offres
     *
     * @return array Tableau d'offres triées
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
        
        // ** Algorithme de matching ici **
        $score = $offre->getId(); // temporaire, permet d'inverser la liste des offres pour un résultat visuel

        // Renvoi du score calculé selon l'algorithme de matching
        return $score;
    }
}
