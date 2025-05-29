<?php

namespace Tourze\SMTPMailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;

#[ORM\Entity(repositoryClass: SMTPConfigRepository::class)]
#[ORM\Table(
    name: 'smtp_config',
    options: ['comment' => 'SMTP配置表']
)]
class SMTPConfig implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '配置名称'])]
    #[Assert\NotBlank]
    #[IndexColumn]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'SMTP服务器地址'])]
    #[Assert\NotBlank]
    private string $host;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'SMTP端口'])]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 65535)]
    private int $port = 587;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户名'])]
    private ?string $username = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '密码'])]
    private ?string $password = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '加密方式'])]
    private string $encryption = 'tls';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '超时时间(秒)'])]
    private int $timeout = 30;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '认证方式'])]
    private ?string $authMode = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '权重'])]
    #[IndexColumn]
    private int $weight = 1;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '优先级'])]
    #[IndexColumn]
    private int $priority = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否有效'])]
    #[IndexColumn]
    private bool $valid = true;

    public function __toString(): string
    {
        return $this->name;
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

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): self
    {
        $this->valid = $valid;
        return $this;
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
}
