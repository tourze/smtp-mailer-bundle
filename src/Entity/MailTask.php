<?php

namespace Tourze\SMTPMailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;

#[ORM\Entity(repositoryClass: MailTaskRepository::class)]
#[ORM\Table(name: 'mail_task')]
#[ORM\HasLifecycleCallbacks]
class MailTask
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Email]
    private string $fromEmail;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fromName = null;

    #[ORM\Column(length: 255)]
    #[Assert\Email]
    #[Assert\NotBlank]
    private string $toEmail;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $toName = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $cc = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $bcc = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $subject;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $body;

    #[ORM\Column]
    private bool $isHtml = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attachments = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $statusMessage = null;

    #[ORM\ManyToOne(targetEntity: SMTPConfig::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SMTPConfig $smtpConfig = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $selectorStrategy = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

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

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function setFromEmail(string $fromEmail): self
    {
        $this->fromEmail = $fromEmail;
        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function setFromName(?string $fromName): self
    {
        $this->fromName = $fromName;
        return $this;
    }

    public function getToEmail(): string
    {
        return $this->toEmail;
    }

    public function setToEmail(string $toEmail): self
    {
        $this->toEmail = $toEmail;
        return $this;
    }

    public function getToName(): ?string
    {
        return $this->toName;
    }

    public function setToName(?string $toName): self
    {
        $this->toName = $toName;
        return $this;
    }

    public function getCc(): ?array
    {
        return $this->cc;
    }

    public function setCc(?array $cc): self
    {
        $this->cc = $cc;
        return $this;
    }

    public function getBcc(): ?array
    {
        return $this->bcc;
    }

    public function setBcc(?array $bcc): self
    {
        $this->bcc = $bcc;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function isHtml(): bool
    {
        return $this->isHtml;
    }

    public function setIsHtml(bool $isHtml): self
    {
        $this->isHtml = $isHtml;
        return $this;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }

    public function setStatusMessage(?string $statusMessage): self
    {
        $this->statusMessage = $statusMessage;
        return $this;
    }

    public function getSmtpConfig(): ?SMTPConfig
    {
        return $this->smtpConfig;
    }

    public function setSmtpConfig(?SMTPConfig $smtpConfig): self
    {
        $this->smtpConfig = $smtpConfig;
        return $this;
    }

    public function getSelectorStrategy(): ?string
    {
        return $this->selectorStrategy;
    }

    public function setSelectorStrategy(?string $selectorStrategy): self
    {
        $this->selectorStrategy = $selectorStrategy;
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

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    /**
     * 检查任务是否可以发送
     */
    public function isReadyToSend(): bool
    {
        // 只有状态为等待中的任务才能发送
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        // 如果有计划发送时间，检查是否到达发送时间
        if ($this->scheduledAt !== null) {
            $now = new \DateTimeImmutable();
            if ($this->scheduledAt > $now) {
                return false;
            }
        }

        return true;
    }

    /**
     * 标记任务为处理中
     */
    public function markAsProcessing(): self
    {
        $this->status = self::STATUS_PROCESSING;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * 标记任务为已发送
     */
    public function markAsSent(): self
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * 标记任务为失败
     */
    public function markAsFailed(string $errorMessage = null): self
    {
        $this->status = self::STATUS_FAILED;
        $this->statusMessage = $errorMessage;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}
