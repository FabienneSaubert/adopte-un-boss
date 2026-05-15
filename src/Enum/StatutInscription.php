<?php

namespace App\Enum;

enum StatutInscription: string
{
    case EN_ATTENTE = 'En attente';
    case VALIDE = 'Validé';
    case REFUSE = 'Refusé';
}