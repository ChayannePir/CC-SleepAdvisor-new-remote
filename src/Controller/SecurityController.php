<?php

namespace App\Controller;

use App\Entity\Client;
use App\Service\ClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur pour la gestion de l'authentification
 * 
 * @package App\Controller
 */
#[Route('/')]
class SecurityController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('public/home.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastEmail = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall.
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, ClientService $clientService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            try {
                $email = $request->request->getString('email');
                $password = $request->request->getString('password');
                $passwordConfirm = $request->request->getString('password_confirm');
                $nom = $request->request->getString('nom');
                $adresse = $request->request->getString('adresse');
                $telephone = $request->request->getString('telephone');

                if ($password !== $passwordConfirm) {
                    $this->addFlash('error', 'Les mots de passe ne correspondent pas');
                    return $this->redirectToRoute('app_register');
                }

                $client = $clientService->registerClient($email, $password, $nom, $adresse, $telephone);
                $this->addFlash('success', 'Inscription réussie! Veuillez vous connecter.');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'inscription: ' . $e->getMessage());
            }
        }

        return $this->render('security/register.html.twig');
    }

    #[Route('/mot-de-passe-perdu', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->getString('email');
            // TODO: Implémenter la logique de réinitialisation du mot de passe
            // Envoyer un email avec un lien de réinitialisation
            $this->addFlash('success', 'Vérifiez votre email pour réinitialiser votre mot de passe.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig');
    }
}
