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
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'SMTP服务器地址'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $host;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'SMTP端口'])]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 65535)]
    #[Assert\Type(type: 'int')]
    private int $port = 587;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户名'])]
    #[Assert\Length(max: 255)]
    private ?string $username = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '密码'])]
    #[Assert\Length(max: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '加密方式'])]
    #[Assert\Length(max: 20)]
    #[Assert\Choice(choices: ['none', 'ssl', 'tls', 'starttls'])]
    private string $encryption = 'tls';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '超时时间(秒)'])]
    #[Assert\Range(min: 1, max: 3600)]
    #[Assert\Type(type: 'int')]
    private int $timeout = 30;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '认证方式'])]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['login', 'plain', 'cram-md5', 'xoauth2'])]
    private ?string $authMode = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '权重'])]
    #[Assert\Range(min: 0, max: 999)]
    #[Assert\Type(type: 'int')]
    #[IndexColumn]
    private int $weight = 1;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '优先级'])]
    #[Assert\Range(min: 0, max: 999)]
    #[Assert\Type(type: 'int')]
    #[IndexColumn]
    private int $priority = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否有效'])]
    #[Assert\Type(type: 'bool')]
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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getEncryption(): string
    {
        return $this->encryption;
    }

    public function setEncryption(string $encryption): void
    {
        $this->encryption = $encryption;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function getAuthMode(): ?string
    {
        return $this->authMode;
    }

    public function setAuthMode(?string $authMode): void
    {
        $this->authMode = $authMode;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }

    /**
     * 获取SMTP DSN
     */
    public function getDsn(): string
    {
        return sprintf(
            'smtp://%s%s:%d%s',
            $this->buildAuthPart(),
            $this->host,
            $this->port,
            $this->buildParamsPart()
        );
    }

    /**
     * 构建认证部分
     */
    private function buildAuthPart(): string
    {
        if (!$this->hasUsername()) {
            return '';
        }

        $auth = urlencode($this->username ?? '');
        if ($this->hasPassword()) {
            $auth .= ':' . urlencode($this->password ?? '');
        }

        return $auth . '@';
    }

    /**
     * 构建参数部分
     */
    private function buildParamsPart(): string
    {
        $params = [];

        if ($this->hasEncryption()) {
            $params['encryption'] = $this->encryption;
        }

        if ($this->hasAuthMode()) {
            $params['auth_mode'] = $this->authMode;
        }

        if ($this->hasCustomTimeout()) {
            $params['timeout'] = $this->timeout;
        }

        return 0 === count($params) ? '' : '?' . http_build_query($params);
    }

    /**
     * 检查是否有用户名
     */
    private function hasUsername(): bool
    {
        return null !== $this->username && '' !== $this->username;
    }

    /**
     * 检查是否有密码
     */
    private function hasPassword(): bool
    {
        return null !== $this->password && '' !== $this->password;
    }

    /**
     * 检查是否有加密配置
     */
    private function hasEncryption(): bool
    {
        return '' !== $this->encryption && 'none' !== $this->encryption;
    }

    /**
     * 检查是否有认证模式
     */
    private function hasAuthMode(): bool
    {
        return null !== $this->authMode && '' !== $this->authMode;
    }

    /**
     * 检查是否有自定义超时
     */
    private function hasCustomTimeout(): bool
    {
        return 30 !== $this->timeout;
    }
}
