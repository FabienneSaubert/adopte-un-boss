<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\ValueObject\CvFilenameGenerator;

class CvFilenameGeneratorTest extends TestCase
{
    public function testGeneratedFilenameDoesNotExceed255Characters(): void
    {
        // Nouvel object de la classe CvFilenameGenerator stocké dans $generator
        $generator = new CvFilenameGenerator();

        // $longname = stocke un string qui dépasse la valeur autorisée de 255 char
        $longName = str_repeat('a', 300) . '.pdf';
        // On simule un préfix pour un code déterministe
        $prefix = '1234567890_';
        /* On stocke dans $filename le résultat de la méthode "generate" avec pour arguments ($longname, $prefix)
        qui correspondront à $originalName et $uniquePrefix*/
        $filename = $generator->generate($longName, $prefix);
        /* assertLessThanOrEqual = méthode de la classe "Asset" qui si une valeur est plus petite, 
        égale ou plus grande qu'une autre */
        $this->assertLessThanOrEqual(255, strlen($filename));
        // ici 255 définit le maximum autorisé et on donne l'élément à comparer $filename
    }

    public function testExtensionIsPreserved(): void
    // Vérifier que l'extension soit bien préservée
    {
        $generator = new CvFilenameGenerator(); // On instancie la classe CvFilenameGenerator

        // On passe les arguments à sa méthode "generate" et on stocke le résulat dans $filename
        $filename = $generator->generate('mon_cv.pdf', 'abc_');

        // On vérifie que le string se termine par le suffixe "pdf". 
        $this->assertStringEndsWith('.pdf', $filename);
        // 1er paramètre = suffix, 2eme paramètre = string concerné
    }

    public function testSpecialCharactersAreReplaced(): void
    // Vérifier si les caratères spécifiques soient bien remplacés. 
    {
        $generator = new CvFilenameGenerator(); // ""

        // On donne une valeur à originalName qui contient deux caractères non autrorisés "espace" et @
        $filename = $generator->generate('mon cv@2024!.pdf', 'abc_');

        // et on vérifie que ces caractères ne soient pas présents dans le résultat. 
        $this->assertStringNotContainsString(' ', $filename);
        $this->assertStringNotContainsString('@', $filename);
    }
}
