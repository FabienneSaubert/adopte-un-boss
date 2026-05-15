<?php 


namespace App\tests;

//On importe la classe que l’on veut tester :
use App\Controller\UtilisateurController;

// On importe l’entité Utilisateur, car on va créer un faux utilisateur pour le test: 
use App\Entity\Utilisateur;

//On importe TestCase, la classe de base de PHPUnit. Elle donne accès aux méthodes : assertSame()
//                                                                                   assertNull()
//                                                                                   assertArrayHasKey() etc.
use PHPUnit\Framework\TestCase;

// 👉 On crée une classe de test. Elle hérite de TestCase, donc c’est un vrai test PHPUnit.
// Le nom finit par Test → convention obligatoire pour que PHPUnit le détecte.
class UtilisateurTest extends TestCase
{
    // Méthode helper pour appeler une méthode privée
    private function callPrivateSerialiserUtilisateur(UtilisateurController $controller, Utilisateur $u): array
    {
    // Cette méthode sert à appeler serialiserUtilisateur(), qui est private dans notre contrôleur.
    // Comme on ne peut pas appeler une méthode private directement, on utilise Reflection.
        
        // On crée un objet Reflection basé sur notre contrôleur. Reflection permet d’inspecter et manipuler une classe dynamiquement.
        $ref = new \ReflectionClass($controller);

        // On récupère la méthode serialiserUtilisateur.
        $method = $ref->getMethod('serialiserUtilisateur');

        // On force l’accès à la méthode même si elle est private. Sinon PHP refuserait de l’exécuter.
        $method->setAccessible(true);

        // On appelle la méthode avec :
        //              $controller = l’objet sur lequel on appelle la méthode
        //              $u = l’argument envoyé à la méthode
        //              invoke() exécute réellement la méthode.
        //              On retourne le résultat (qui est un tableau).
        /** @var array */
        return $method->invoke($controller, $u);
    }

    // C’est une méthode de test. Chaque méthode publique commençant par test sera exécutée par PHPUnit.
    public function testSerialiserUtilisateurFormatsDate(): void
    {
        // On crée une instance du contrôleur. Ici on n’a pas besoin de dépendances car on teste juste une méthode interne
        $controller = new UtilisateurController();

        // On crée un nouvel objet Utilisateur et on remplit les propriétés de l’utilisateur.
        $u = (new Utilisateur())
            ->setNom('Dupont')
            ->setPrenom('Lina')
            ->setEmail('lina@example.com')
            ->setTelephone('0600000000')
            ->setDateDeNaissance(new \DateTimeImmutable('2000-01-02'));

        // On appelle la méthode privée via notre helper. Résultat : $data est un tableau.
        $data = $this->callPrivateSerialiserUtilisateur($controller, $u);

        // assertSame() vérifie : même valeur, même type
        $this->assertSame('Dupont', $data['nom']);
        // On vérifie que la clé nom contient bien "Dupont".
        $this->assertSame('Lina', $data['prenom']);
        $this->assertSame('lina@example.com', $data['email']);
        $this->assertSame('0600000000', $data['telephone']);
        $this->assertSame('2000-01-02', $data['date_de_naissance']);
        // On vérifie que la date a bien été formatée en Y-m-d. C’est le point important de ce test.

        // id peut être null en test unitaire (si doctrine ne l’a pas mis)
        $this->assertArrayHasKey('id', $data);
        // On vérifie que la clé id existe dans le tableau.
        // Même si Doctrine n’a pas généré d’id (car pas de DB en test unitaire), la clé doit être présente.
    }
}