<?php
namespace App\Entity;

use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WebauthnCredentialRepository::class)]
#[ORM\Table(name: 'webauthn_credential')]
class WebauthnCredential
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy: 'webauthnCredentials')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'text')]
    private string $rawCredentialData;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $lastUsedAt;

    public function __construct()
    {
        $this->id          = Uuid::v4();
        $this->createdAt   = new \DateTimeImmutable();
        $this->lastUsedAt  = new \DateTimeImmutable();
        $this->name        = 'My Passkey';
    }

    public function getId(): Uuid { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getRawCredentialData(): string
    {
        return $this->rawCredentialData;
    }
    public function setRawCredentialData(string $data): static
    {
        $this->rawCredentialData = $data;
        return $this;
    }

    // Get the credential ID from stored data
    public function getCredentialId(): string
    {
        $data = json_decode($this->rawCredentialData, true);
        return $data['id'] ?? '';
    }

    public function touch(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getLastUsedAt(): \DateTimeImmutable { return $this->lastUsedAt; }
}