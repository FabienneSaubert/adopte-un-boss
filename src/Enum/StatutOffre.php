<?php

namespace App\Enum;

enum StatutOffre: string
{
    case EN_ATTENTE = 'En attente';
    case ACCEPTE = 'Accepté';
    case REFUSE = 'Refusé';
}