<?php
namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\PasskeyAuthService;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthApiController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenManagerInterface $refreshManager,
        private UserRepository $userRepo
    ) {}

    #[Route('/register/options', methods: ['POST'])]
    public function registerOptions(
        Request $request,
        PasskeyAuthService $passkeyService
    ): JsonResponse {
        $content = $request->getContent();
        
        if (empty($content)) {
            return $this->json(['error' => 'Request body is empty'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON provided'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepo->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $options = $passkeyService->getRegistrationOptions($user);
            return $this->json($options);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/register/verify', methods: ['POST'])]
    public function registerVerify(
        Request $request,
        PasskeyAuthService $passkeyService
    ): JsonResponse {
        $content = $request->getContent();
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid or empty JSON'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? null;
        $credential = $data['credential'] ?? null;

        if (!$user = $this->userRepo->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$credential) {
            return $this->json(['error' => 'Credential data missing'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $passkeyService->verifyRegistration($credential, $user);
            $jwt = $this->jwtManager->create($user);
            $refresh = new RefreshToken(); 
            $refresh->setRefreshToken(bin2hex(random_bytes(64)));
            $refresh->setRefreshToken(bin2hex(random_bytes(64)));
            $refresh->setUsername($user->getUserIdentifier());
            $refresh->setValid((new \DateTime())->modify('+2592000 seconds'));
            $this->refreshManager->save($refresh);

            return $this->json([
                'success' => true,
                'token' => $jwt,
                'refresh_token' => $refresh->getRefreshToken(),
                'user' => ['id' => $user->getId(), 'email' => $user->getEmail()]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/login/options', methods: ['POST'])]
    public function loginOptions(PasskeyAuthService $passkeyService): JsonResponse
    {
        try {
            return $this->json($passkeyService->getLoginOptions());
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/login/verify', methods: ['POST'])]
    public function loginVerify(
        Request $request,
        PasskeyAuthService $passkeyService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $credential = $data['credential'] ?? null;

        if (!$credential) {
            return $this->json(['error' => 'Credential required'],
                Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $passkeyService->verifyLogin(json_encode($credential));

            $jwt = $this->jwtManager->create($user);
            $refresh = new RefreshToken(); 
            $refresh->setRefreshToken(bin2hex(random_bytes(64)));
            $refresh->setRefreshToken(bin2hex(random_bytes(64)));
            $refresh->setUsername($user->getUserIdentifier());
            $refresh->setValid((new \DateTime())->modify('+2592000 seconds'));
            $this->refreshManager->save($refresh);

            return $this->json([
                'success' => true,
                'token' => $jwt,
                'refresh_token' => $refresh->getRefreshToken(),
                'user' => ['id' => $user->getId(), 'email' => $user->getEmail()]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'Not authenticated'],
                Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ]);
    }
}
