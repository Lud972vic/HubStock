<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur de sécurité.
 *
 * - Page de connexion: affiche le dernier identifiant saisi et une erreur éventuelle.
 * - Déconnexion: laissée à Symfony Security (route configurée, pas de logique ici).
 */
final class SecurityController extends AbstractController
{
    /**
     * Affiche le formulaire de connexion.
     * La logique d’authentification est gérée par le système de sécurité de Symfony.
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Déconnexion.
     * La logique est totalement prise en charge par Symfony via la configuration.
     */
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // handled by Symfony
    }
}