<?php

namespace Tourze\SMTPMailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;

#[ORM\Entity(repositoryClass: MailTaskRepository::class)]
#[ORM\Table(
    name: 'mail_task',
    options: ['comment' => '邮件任务表']
)]
class MailTask implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '发件人邮箱'])]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[IndexColumn]
    private string $fromEmail;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '发件人姓名'])]
    #[Assert\Length(max: 255)]
    private ?string $fromName = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '收件人邮箱'])]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[IndexColumn]
    private string $toEmail;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '收件人姓名'])]
    #[Assert\Length(max: 255)]
    private ?string $toName = null;

    /** @var array<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '抄送邮箱列表'])]
    #[Assert\All(constraints: [
        new Assert\Email(),
    ])]
    private ?array $cc = null;

    /** @var array<string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '密送邮箱列表'])]
    #[Assert\All(constraints: [
        new Assert\Email(),
    ])]
    private ?array $bcc = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '邮件主题'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $subject;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '邮件内容'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16777215)]
    private string $body;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否为HTML格式'])]
    #[Assert\Type(type: 'bool')]
    private bool $isHtml = true;

    /** @var array<int, array<string, mixed>>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '附件列表'])]
    #[Assert\Type(type: 'array')]
    private ?array $attachments = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '计划发送时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[IndexColumn]
    private ?\DateTimeImmutable $scheduledTime = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: MailTaskStatus::class, options: ['comment' => '任务状态'])]
    #[Assert\Choice(callback: [MailTaskStatus::class, 'cases'])]
    #[IndexColumn]
    private MailTaskStatus $status = MailTaskStatus::PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '状态消息'])]
    #[Assert\Length(max: 65535)]
    private ?string $statusMessage = null;

    #[ORM\ManyToOne(targetEntity: SMTPConfig::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SMTPConfig $smtpConfig = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '选择器策略'])]
    #[Assert\Length(max: 50)]
    private ?string $selectorStrategy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '发送时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[IndexColumn]
    private ?\DateTimeImmutable $sentTime = null;

    public function __toString(): string
    {
        return sprintf('邮件任务#%s: %s -> %s', $this->id ?? 'NEW', $this->fromEmail ?? '', $this->toEmail ?? '');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function setFromEmail(string $fromEmail): void
    {
        $this->fromEmail = $fromEmail;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function setFromName(?string $fromName): void
    {
        $this->fromName = $fromName;
    }

    public function getToEmail(): string
    {
        return $this->toEmail;
    }

    public function setToEmail(string $toEmail): void
    {
        $this->toEmail = $toEmail;
    }

    public function getToName(): ?string
    {
        return $this->toName;
    }

    public function setToName(?string $toName): void
    {
        $this->toName = $toName;
    }

    /**
     * @return array<string>|null
     */
    public function getCc(): ?array
    {
        return $this->cc;
    }

    /**
     * @param array<string>|null $cc
     */
    public function setCc(?array $cc): void
    {
        $this->cc = $cc;
    }

    /**
     * @return array<string>|null
     */
    public function getBcc(): ?array
    {
        return $this->bcc;
    }

    /**
     * @param array<string>|null $bcc
     */
    public function setBcc(?array $bcc): void
    {
        $this->bcc = $bcc;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function isHtml(): bool
    {
        return $this->isHtml;
    }

    public function setIsHtml(bool $isHtml): void
    {
        $this->isHtml = $isHtml;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    /**
     * @param array<int, array<string, mixed>>|null $attachments
     */
    public function setAttachments(?array $attachments): void
    {
        $this->attachments = $attachments;
    }

    public function getScheduledTime(): ?\DateTimeImmutable
    {
        return $this->scheduledTime;
    }

    public function setScheduledTime(?\DateTimeImmutable $scheduledTime): void
    {
        $this->scheduledTime = $scheduledTime;
    }

    public function getStatus(): MailTaskStatus
    {
        return $this->status;
    }

    public function setStatus(MailTaskStatus $status): void
    {
        $this->status = $status;
    }

    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }

    public function setStatusMessage(?string $statusMessage): void
    {
        $this->statusMessage = $statusMessage;
    }

    public function getSmtpConfig(): ?SMTPConfig
    {
        return $this->smtpConfig;
    }

    public function setSmtpConfig(?SMTPConfig $smtpConfig): void
    {
        $this->smtpConfig = $smtpConfig;
    }

    public function getSelectorStrategy(): ?string
    {
        return $this->selectorStrategy;
    }

    public function setSelectorStrategy(?string $selectorStrategy): void
    {
        $this->selectorStrategy = $selectorStrategy;
    }

    public function getSentTime(): ?\DateTimeImmutable
    {
        return $this->sentTime;
    }

    public function setSentTime(?\DateTimeImmutable $sentTime): void
    {
        $this->sentTime = $sentTime;
    }

    /**
     * 检查任务是否可以发送
     */
    public function isReadyToSend(): bool
    {
        // 只有状态为等待中的任务才能发送
        if (MailTaskStatus::PENDING !== $this->status) {
            return false;
        }

        // 如果有计划发送时间，检查是否到达发送时间
        if (null !== $this->scheduledTime) {
            $now = new \DateTimeImmutable();
            if ($this->scheduledTime > $now) {
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
        $this->status = MailTaskStatus::PROCESSING;

        return $this;
    }

    /**
     * 标记任务为已发送
     */
    public function markAsSent(): self
    {
        $this->status = MailTaskStatus::SENT;
        $this->sentTime = new \DateTimeImmutable();

        return $this;
    }

    /**
     * 标记任务为失败
     */
    public function markAsFailed(?string $errorMessage = null): self
    {
        $this->status = MailTaskStatus::FAILED;
        $this->statusMessage = $errorMessage;

        return $this;
    }
}
