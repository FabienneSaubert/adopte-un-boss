<?php
/* --- Entité ajoutée automatiquement par le bundle JWTRefreshTokenBundle --- */
// => Permet de spécialiser la table refresh_tokens avec des nouvelle colonnes

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken extends BaseRefreshToken
{
    // On peut ici ajouter nos attributs métier si nécéssaire,
    // par exemple la date d'expiration absolue.
}
