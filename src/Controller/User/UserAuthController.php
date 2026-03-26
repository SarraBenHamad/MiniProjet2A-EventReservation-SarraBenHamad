<?php
namespace App\Controller\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserAuthController extends AbstractController
{
    #[Route('/login', name: 'user_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        return $this->render('user/auth/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route('/logout', name: 'user_logout')]
    public function logout(): void {}

    #[Route('/register', name: 'user_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepo
    ): Response {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setUsername($request->request->get('username'));
            $user->setPassword(
                $hasher->hashPassword($user, $request->request->get('password'))
            );

            $userRepo->save($user);

            $this->addFlash('success', 'Account created! Please login.');
            return $this->redirectToRoute('user_login');
        }

        return $this->render('user/auth/register.html.twig');
    }

    #[Route('/passkey-session', name: 'passkey_session', methods: ['POST'])]
    public function passkeySession(
        Request $request,
        UserRepository $userRepo,
        \Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface $jwtManager,
        \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage,
        \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken $token = null
    ): Response {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Missing email'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepo->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Manually create Symfony session for this user
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $user, 'main', $user->getRoles()
        );
        $tokenStorage->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));

        return $this->json(['success' => true]);
    }
}
