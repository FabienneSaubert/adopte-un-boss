<?php

namespace App\Enum;

enum StatutDemande: string
{
    case EN_ATTENTE = 'En attente';
    case EN_COURS = 'En cours';
    case TERMINEE = 'Terminée';
}