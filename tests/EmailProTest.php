<?php

namespace App\Tests;

// Appel de la classe EmailPro, où l'on va tester son instanciation ainsi que sa validation
use App\ValueObject\EmailPro;
// Type d'erreur renvoyé par la classe et attendu lors des tests d'erreur
use InvalidArgumentException;
// Classe venant de PHPUnit contenant les méthodes utilitaires utiles pour
// évaluer les résultats des tests, avec assertions, exceptions, etc ...
use PHPUnit\Framework\TestCase;

// Classe contenant toutes les méthodes publiques représentant chacune un
// test unitaire relatant la classe EmailPro. Elles seront automatiquement
// lues et exécutées dans l'ordre lors du lancement des test unitaires.
class EmailProTest extends TestCase
{
    // Test de la possibilité de création d'un objet EmailPro dont on sait la valeur valide
    public function testValidEmailProCanBeCreated(): void
    {
        // On prend une valeur d'email qui doit être valide
        $emailProStringTest = "sylvainlerecruteur@gmail.com";

        // On lance la création de l'objet avec cet email en utilisant le fromString() de la classe
        $emailPro = EmailPro::fromString($emailProStringTest);

        // Et on compare les deux valeurs, le but étant que PHP valide ou non que le
        // résultat de la création est exactement ce que l'on attend d'une valeur valide
        $this->assertSame(
            // Valeur attendue
            $emailProStringTest,
            // Résultat en string de la création d'un EmailPro
            $emailPro->asString()
        );
    }

    // On teste ici qu'il n'est pas possible de rentrer un email contenant plus de 100 caractères
    public function testEmailProCannotExceed100Characters()
    {
        // On s'attend à une erreur du même type utilisé dans la validation à l'intérieur de la classe,
        // le test sera considéré comme un succès si l'on attrape cette erreur.
        $this->expectException(InvalidArgumentException::class);

        // On prend donc un email invalide selon le test, soit un email de plus de 100 caractères
        $longEmail = str_repeat('a',101) . '@gmail.com';
        // Et on tente de déclencher la création d'un EmailPro avec cette valeur érronée
        EmailPro::fromString($longEmail);
    }

    // On teste ici qu'il n'est pas possible de rentrer un email dont le format n'est pas valide
    public function testInvalidFormatThrowsException()
    {
        // On s'attend à une erreur du même type utilisé dans la validation à l'intérieur de la classe
        $this->expectException(InvalidArgumentException::class);

        // Puis on tente ainsi la création d'un EmailPro en y rentrant directement un email avec un format invalide
        EmailPro::fromString('pasvalideemail.com');
    }

    // Ici on teste si un email avec une valeur null renvoi bien une erreur
    public function testRequiredEmailThrowsIfNull()
    {
        // On s'attend à une erreur du même type utilisé dans la validation à l'intérieur de la classe
        $this->expectException(InvalidArgumentException::class);

        // Puis on tente ainsi la création d'un EmailPro avec une valeur nulle
        EmailPro::fromString(null);
    }
}