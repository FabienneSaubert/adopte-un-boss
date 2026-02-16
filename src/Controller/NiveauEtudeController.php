<?php

namespace App\Controller;

use App\Enum\NiveauEtude;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class NiveauEtudeController extends AbstractController
{
    #[Route('/api/niveaux-etude', name: 'api_niveaux_etude', methods: ['GET'])]
    public function getNiveauxEtude(): JsonResponse
    {
        $niveaux = [];

        foreach (NiveauEtude::cases() as $niveau) {
            $niveaux[] = $niveau->value;
        }

        return $this->json($niveaux);
    }
}
