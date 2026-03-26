<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Repository\UserRepository;
use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PasskeyAuthService
{
    public function __construct(
        private RequestStack $requestStack,
        private WebauthnCredentialRepository $credRepo,
        private UserRepository $userRepo,
        private EntityManagerInterface $em
    ) {}

    public function getRegistrationOptions(User $user): array
    {
        // Generate a random challenge
        $challenge = base64_encode(random_bytes(32));

        $options = [
            'challenge' => $challenge,
            'rp' => [
                'name' => $_ENV['WEBAUTHN_RP_NAME'] ?? 'EventRes',
                'id'   => $_ENV['APP_DOMAIN'] ?? 'localhost',
            ],
            'user' => [
                'id'          => base64_encode($user->getId()->toBinary()),
                'name'        => $user->getEmail(),
                'displayName' => $user->getUsername(),
            ],
            'pubKeyCredParams' => [
                ['alg' => -7,   'type' => 'public-key'], // ES256
                ['alg' => -257, 'type' => 'public-key'], // RS256
            ],
            'authenticatorSelection' => [
                'userVerification' => 'preferred',
                'residentKey'      => 'preferred',
            ],
            'timeout'             => 60000,
            'attestation'         => 'none',
            'excludeCredentials'  => [],
        ];

        // Store challenge in session for verification
        $this->requestStack->getSession()
            ->set('webauthn_register_challenge', $challenge);
        $this->requestStack->getSession()
            ->set('webauthn_register_user_id', (string) $user->getId());

        return $options;
    }

    public function verifyRegistration(array $credentialJson, User $user): void
    {

        if (empty($credentialJson)) {
            throw new \Exception('Invalid credential data');
        }

        // Verify the challenge
        $sessionChallenge = $this->requestStack->getSession()
            ->get('webauthn_register_challenge');

        if (!$sessionChallenge) {
            throw new \Exception('No challenge found in session');
        }

        // Decode clientDataJSON to verify challenge
        $clientDataJSON = $credentialJson['response']['clientDataJSON'] ?? null;
        if (!$clientDataJSON) {
            throw new \Exception('Missing clientDataJSON');
        }

        $clientData = json_decode(
            base64_decode(strtr($clientDataJSON, '-_', '+/')),
            true
        );

        // Verify challenge matches
        $receivedChallenge = $clientData['challenge'] ?? null;
        $expectedChallenge = strtr(
            base64_encode(base64_decode($sessionChallenge)),
            '+/', '-_'
        );
        $expectedChallenge = rtrim($expectedChallenge, '=');

        if ($receivedChallenge !== $expectedChallenge) {
            throw new \Exception('Challenge mismatch');
        }

        // Store credential in database
        $webauthnCred = new WebauthnCredential();
        $webauthnCred->setUser($user);
        $webauthnCred->setName('Passkey - ' . date('d/m/Y'));
        $webauthnCred->setRawCredentialData(json_encode($credentialJson));

        $this->em->persist($webauthnCred);
        $this->em->flush();

        // Clean session
        $this->requestStack->getSession()->remove('webauthn_register_challenge');
        $this->requestStack->getSession()->remove('webauthn_register_user_id');
    }

    public function getLoginOptions(): array
    {
        $challenge = base64_encode(random_bytes(32));

        $this->requestStack->getSession()
            ->set('webauthn_login_challenge', $challenge);

        return [
            'challenge'        => $challenge,
            'timeout'          => 60000,
            'rpId'             => $_ENV['APP_DOMAIN'] ?? 'localhost',
            'userVerification' => 'preferred',
            'allowCredentials' => [],
        ];
    }

    public function verifyLogin(string $credentialJson): User
    {
        $credential = json_decode($credentialJson, true);

        if (!$credential) {
            throw new \Exception('Invalid credential data');
        }

        $sessionChallenge = $this->requestStack->getSession()
            ->get('webauthn_login_challenge');

        if (!$sessionChallenge) {
            throw new \Exception('No challenge in session');
        }

        // Decode and verify challenge
        $clientDataJSON = $credential['response']['clientDataJSON'] ?? null;
        if (!$clientDataJSON) {
            throw new \Exception('Missing clientDataJSON');
        }

        $clientData = json_decode(
            base64_decode(strtr($clientDataJSON, '-_', '+/')),
            true
        );

        $receivedChallenge = $clientData['challenge'] ?? null;
        $expectedChallenge = rtrim(
            strtr(base64_encode(base64_decode($sessionChallenge)), '+/', '-_'),
            '='
        );

        if ($receivedChallenge !== $expectedChallenge) {
            throw new \Exception('Challenge mismatch during login');
        }

        // Find credential by ID
        $credentialId = $credential['id'] ?? null;
        $webauthnCred = $this->credRepo->findByCredentialId($credentialId);

        if (!$webauthnCred) {
            throw new \Exception('Passkey not found. Please register first.');
        }

        $webauthnCred->touch();
        $this->em->flush();

        $this->requestStack->getSession()->remove('webauthn_login_challenge');

        return $webauthnCred->getUser();
    }
}