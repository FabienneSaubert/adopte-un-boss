<?php

namespace App\Enum;

enum NiveauEtude: string
{
    case SANS_DIPLOME = 'Sans diplôme';
    case BEP_CAP = 'BEP/CAP';
    case BAC = 'BAC';
    case BAC_2 = 'BAC +2 - BTS/DUT';
    case BAC_3 = 'BAC +3 - Licence';
    case BAC_5 = 'BAC+5 - Master';
}