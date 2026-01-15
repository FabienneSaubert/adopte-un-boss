<?php

namespace App\Enum;

enum CategorieOffre: string
{
    case INFORMATIQUE = 'Informatique/Numérique';
    case BATIMENT = 'Bâtiment';
    case SCIENCE = 'Recherche / Science';
    case INDUSTRIE = 'Industrie';
    case LOGISTIQUE = 'Logistique / Transport';
    case COMMERCE = 'Commerce / Vente';
    case COMMUNICATION = 'Communication / Marketing';
    case FINANCE = 'Gestion / Finance';
    case RH = 'Ressources humaines';
    case DROIT = 'Droit';
    case DESIGN = 'Design';
    case SANTE = 'Santé / Social';
    case HOTELLERIE = 'Hôtellerie / Restauration / Tourisme';
    case AGRICULTURE = 'Agriculture';
    case ARTISANAT = 'Artisanat';
}