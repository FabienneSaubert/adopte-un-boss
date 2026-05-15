<?php

namespace App\Enum;

enum RoleUtilisateur: string {
    case ADMIN = "Admin";
    case CANDIDAT = "Candidat";
    case RECRUTEUR = "Recruteur";
}