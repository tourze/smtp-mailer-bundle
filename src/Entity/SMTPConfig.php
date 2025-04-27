<?php

namespace Tourze\SMTPMailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;

#[ORM\Entity(repositoryClass: SMTPConfigRepository::class)]
#[ORM\Table(name: 'smtp_config')]
#[ORM\HasLifecycleCallbacks]
class SMTPConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $host;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 65535)]
    private int $port = 587;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 20)]
    private string $encryption = 'tls';

    #[ORM\Column]
    private int $timeout = 30;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $authMode = null;

    #[ORM\Column]
    private int $weight = 1;

    #[ORM\Column]
    private int $priority = 0;

    #[ORM\Column]
    private bool $enabled = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getEncryption(): string
    {
        return $this->encryption;
    }

    public function setEncryption(string $encryption): self
    {
        $this->encryption = $encryption;
        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function getAuthMode(): ?string
    {
        return $this->authMode;
    }

    public function setAuthMode(?string $authMode): self
    {
        $this->authMode = $authMode;
        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * 获取SMTP DSN
     */
    public function getDsn(): string
    {
        $dsn = 'smtp://';

        if ($this->username) {
            $dsn .= urlencode($this->username);

            if ($this->password) {
                $dsn .= ':' . urlencode($this->password);
            }

            $dsn .= '@';
        }

        $dsn .= $this->host . ':' . $this->port;

        $params = [];

        if ($this->encryption && $this->encryption !== 'none') {
            $params['encryption'] = $this->encryption;
        }

        if ($this->authMode) {
            $params['auth_mode'] = $this->authMode;
        }

        if ($this->timeout !== 30) {
            $params['timeout'] = $this->timeout;
        }

        if (!empty($params)) {
            $dsn .= '?' . http_build_query($params);
        }

        return $dsn;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
