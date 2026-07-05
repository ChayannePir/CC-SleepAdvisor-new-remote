<?php

namespace App\Controller;

use App\Service\ClientService;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Authentification : connexion, inscription, récupération de mot de passe.
 */
#[Route('/')]
class SecurityController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('public/home.html.twig');
    }

    /**
     * Page de connexion.
     * Si l'utilisateur est déjà connecté, redirige vers l'accueil.
     * Affiche le dernier email utilisé et l'erreur d'authentification si présente.
     *
     * @param AuthenticationUtils $authenticationUtils Fournit le dernier username et l'erreur d'authentification
     * @return Response
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            'last_email' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Intercepted by the logout firewall.');
    }

    /**
     * Inscription d'un nouveau client.
     * Valide les mots de passe et délègue la création au service ClientService.
     * En cas d'erreur, un message flash est ajouté.
     *
     * @param Request $request Requête HTTP contenant les champs du formulaire
     * @param ClientService $clientService Service pour gérer la création du client
     * @return Response
     */
    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, ClientService $clientService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            try {
                $password = $request->request->getString('password');
                if ($password !== $request->request->getString('password_confirm')) {
                    $this->addFlash('error', 'Les mots de passe ne correspondent pas');

                    return $this->redirectToRoute('app_register');
                }

                $clientService->registerClient(
                    $request->request->getString('email'),
                    $password,
                    $request->request->getString('nom'),
                    $request->request->getString('adresse'),
                    $request->request->getString('telephone')
                );
                $this->addFlash('success', 'Inscription réussie ! Veuillez vous connecter.');

                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'inscription : ' . $e->getMessage());
            }
        }

        return $this->render('security/register.html.twig');
    }

    #[Route('/mot-de-passe-perdu', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    /**
     * Demande de réinitialisation de mot de passe.
     * Envoie un lien si l'adresse existe (silencieusement pour des raisons de sécurité).
     *
     * @param Request $request
     * @param PasswordResetService $passwordResetService
     * @return Response
     */
    public function forgotPassword(Request $request, PasswordResetService $passwordResetService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            try {
                $passwordResetService->requestPasswordReset($request->request->getString('email'));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue. Réessayez plus tard.');
            }
            $this->addFlash(
                'success',
                'Si un compte existe avec cet e-mail, vous recevrez un lien de réinitialisation sous peu.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/reinitialiser-mot-de-passe/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    /**
     * Réinitialisation du mot de passe à partir d'un token.
     * Vérifie la validité du token et applique le nouveau mot de passe si valide.
     *
     * @param string $token Token de réinitialisation
     * @param Request $request Requête contenant les nouveaux mots de passe
     * @param PasswordResetService $passwordResetService Service pour valider et appliquer le reset
     * @return Response
     */
    public function resetPassword(
        string $token,
        Request $request,
        PasswordResetService $passwordResetService
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $tokenValid = $passwordResetService->validateToken($token) !== null;

        if ($request->isMethod('POST') && $tokenValid) {
            try {
                $password = $request->request->getString('password');
                if ($password !== $request->request->getString('password_confirm')) {
                    $this->addFlash('error', 'Les mots de passe ne correspondent pas');

                    return $this->render('security/reset_password.html.twig', [
                        'token' => $token,
                        'token_valid' => true,
                    ]);
                }

                $passwordResetService->resetPassword($token, $password);
                $this->addFlash('success', 'Mot de passe mis à jour. Vous pouvez vous connecter.');

                return $this->redirectToRoute('app_login');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de réinitialiser le mot de passe.');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'token_valid' => $tokenValid,
        ]);
    }
}
