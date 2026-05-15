<?php

namespace App\EventSubscriber;

// Entité utilisateur de l’application : on s’en sert pour typer et accéder à des getters spécifiques (ex: getRole()).
use App\Entity\Utilisateur;

// Événement émis par LexikJWT quand l’authentification réussit (token JWT généré et réponse prête à être renvoyée).
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

// Interface Symfony : indique que cette classe "s’abonne" à un ou plusieurs événements.
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

// Subscriber exécuté automatiquement lors de certains événements Symfony/Lexik
class JwtAuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    // Méthode exécutée à chaque authentification réussie via LexikJWT.
    // L’objet $event transporte notamment : les données de réponse (token) et l’utilisateur authentifié.
    public function onAuthenticationSuccessEvent(AuthenticationSuccessEvent $event): void
    {
        // Récupère le payload de réponse déjà construit par LexikJWT.
        // Par défaut, ce tableau contient surtout : ['token' => '...'].
        $data = $event->getData();

        // Récupère l’utilisateur authentifié (normalement une instance de l'entité Utilisateur).
        // Côté typage Symfony, ce user est exposé comme UserInterface.
        $user = $event->getUser();

        // Donc on vérifie le type réel avant d’appeler des méthodes spécifiques.
        // Sécurise le code : si $user n’est pas l’entité Utilisateur (provider différent, tests, autre implémentation),
        // on évite d’appeler des méthodes qui n’existeraient pas et on laisse la réponse par défaut.
        if (!$user instanceof Utilisateur) {
            // On ne modifie pas les données : la réponse REST restera celle de LexikJWT (token uniquement).
            return;
        }

        // Ajoute une clé "user" dans la réponse JSON afin de renvoyer des infos utiles au front à la connexion.
        $data['user'] = [
            // Ajoute le rôle extrait via le getter de l’entité Utilisateur.
            'role' => $user->getRole(),

            // On pourrait ici ajouter toute autre information utile à stocker du côté de la vue.
        ];

        // Remplace le payload de réponse de l’événement par notre version enrichie.
        // Résultat côté client : { "token": "...", "user": { "role": "..." } }.
        $event->setData($data);
    }

    // Méthode imposée par EventSubscriberInterface.
    // Elle déclare au dispatcher Symfony quels événements écouter et quelle méthode appeler pour chacun.
    public static function getSubscribedEvents(): array
    {
        return [
            // Mapping : nom de l’événement => méthode handler.

            // Quand LexikJWT déclenche l’événement "authentication success", Symfony appelle onAuthenticationSuccessEvent().
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccessEvent',
        ];
    }
}
