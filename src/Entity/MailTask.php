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
    #[IndexColumn]
    private string $fromEmail;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '发件人姓名'])]
    private ?string $fromName = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '收件人邮箱'])]
    #[Assert\Email]
    #[Assert\NotBlank]
    #[IndexColumn]
    private string $toEmail;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '收件人姓名'])]
    private ?string $toName = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '抄送邮箱列表'])]
    private ?array $cc = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '密送邮箱列表'])]
    private ?array $bcc = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '邮件主题'])]
    #[Assert\NotBlank]
    private string $subject;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '邮件内容'])]
    #[Assert\NotBlank]
    private string $body;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否为HTML格式'])]
    private bool $isHtml = true;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '附件列表'])]
    private ?array $attachments = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '计划发送时间'])]
    #[IndexColumn]
    private ?\DateTimeImmutable $scheduledTime = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: MailTaskStatus::class, options: ['comment' => '任务状态'])]
    #[IndexColumn]
    private MailTaskStatus $status = MailTaskStatus::PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '状态消息'])]
    private ?string $statusMessage = null;

    #[ORM\ManyToOne(targetEntity: SMTPConfig::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SMTPConfig $smtpConfig = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '选择器策略'])]
    private ?string $selectorStrategy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '发送时间'])]
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

    public function getScheduledTime(): ?\DateTimeImmutable
    {
        return $this->scheduledTime;
    }

    public function setScheduledTime(?\DateTimeImmutable $scheduledTime): self
    {
        $this->scheduledTime = $scheduledTime;
        return $this;
    }

    public function getStatus(): MailTaskStatus
    {
        return $this->status;
    }

    public function setStatus(MailTaskStatus $status): self
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

    public function getSentTime(): ?\DateTimeImmutable
    {
        return $this->sentTime;
    }

    public function setSentTime(?\DateTimeImmutable $sentTime): self
    {
        $this->sentTime = $sentTime;
        return $this;
    }

    /**
     * 检查任务是否可以发送
     */
    public function isReadyToSend(): bool
    {
        // 只有状态为等待中的任务才能发送
        if ($this->status !== MailTaskStatus::PENDING) {
            return false;
        }

        // 如果有计划发送时间，检查是否到达发送时间
        if ($this->scheduledTime !== null) {
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
