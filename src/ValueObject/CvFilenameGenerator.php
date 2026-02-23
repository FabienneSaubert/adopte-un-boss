<?php

namespace App\ValueObject;

class CvFilenameGenerator
{
    public function generate(string $originalName, string $uniquePrefix): string
    {
        // Je reprends ma logique métier de "CandidatController" mais je rends les variables déterministes
        $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        // Dans CandidatController : $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $nomFichierSansExtension = pathinfo($originalName, PATHINFO_FILENAME);

        $maxLength = 255 - strlen($uniquePrefix) - strlen($extension) - 1;

        $nomFichierSansExtension = substr($nomFichierSansExtension, 0, $maxLength);

        return $uniquePrefix . $nomFichierSansExtension . '.' . $extension;
    }
}