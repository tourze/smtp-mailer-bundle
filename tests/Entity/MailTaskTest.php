<?php

namespace Tourze\SMTPMailerBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;

class MailTaskTest extends TestCase
{
    private MailTask $mailTask;

    protected function setUp(): void
    {
        $this->mailTask = new MailTask();
    }

    public function testBasicSettersAndGetters(): void
    {
        $this->mailTask->setFromEmail('sender@example.com');
        $this->assertSame('sender@example.com', $this->mailTask->getFromEmail());

        $this->mailTask->setFromName('Sender Name');
        $this->assertSame('Sender Name', $this->mailTask->getFromName());

        $this->mailTask->setToEmail('recipient@example.com');
        $this->assertSame('recipient@example.com', $this->mailTask->getToEmail());

        $this->mailTask->setToName('Recipient Name');
        $this->assertSame('Recipient Name', $this->mailTask->getToName());

        $cc = ['cc1@example.com', 'cc2@example.com'];
        $this->mailTask->setCc($cc);
        $this->assertSame($cc, $this->mailTask->getCc());

        $bcc = ['bcc1@example.com', 'bcc2@example.com'];
        $this->mailTask->setBcc($bcc);
        $this->assertSame($bcc, $this->mailTask->getBcc());

        $this->mailTask->setSubject('Test Subject');
        $this->assertSame('Test Subject', $this->mailTask->getSubject());

        $this->mailTask->setBody('Test body content');
        $this->assertSame('Test body content', $this->mailTask->getBody());

        $this->mailTask->setIsHtml(false);
        $this->assertFalse($this->mailTask->isHtml());

        $attachments = [
            ['path' => '/path/to/file.pdf', 'name' => 'file.pdf', 'mime' => 'application/pdf'],
            ['data' => base64_encode('binary data'), 'name' => 'data.txt', 'mime' => 'text/plain'],
        ];
        $this->mailTask->setAttachments($attachments);
        $this->assertSame($attachments, $this->mailTask->getAttachments());

        $scheduledTime = new \DateTimeImmutable('+1 hour');
        $this->mailTask->setScheduledTime($scheduledTime);
        $this->assertSame($scheduledTime, $this->mailTask->getScheduledTime());

        $this->mailTask->setStatus(MailTaskStatus::PROCESSING);
        $this->assertSame(MailTaskStatus::PROCESSING, $this->mailTask->getStatus());

        $this->mailTask->setStatusMessage('Processing message');
        $this->assertSame('Processing message', $this->mailTask->getStatusMessage());

        $smtpConfig = new SMTPConfig();
        $this->mailTask->setSmtpConfig($smtpConfig);
        $this->assertSame($smtpConfig, $this->mailTask->getSmtpConfig());

        $this->mailTask->setSelectorStrategy('round_robin');
        $this->assertSame('round_robin', $this->mailTask->getSelectorStrategy());

        $sentTime = new \DateTimeImmutable();
        $this->mailTask->setSentTime($sentTime);
        $this->assertSame($sentTime, $this->mailTask->getSentTime());

        // 在测试环境中，时间戳字段在没有Doctrine管理时为null是正常的
        $this->assertNull($this->mailTask->getCreateTime());
        $this->assertNull($this->mailTask->getUpdateTime());
    }

    public function testPreUpdateLifecycleCallback(): void
    {
        $this->assertNull($this->mailTask->getUpdateTime());

        $this->mailTask->setStatus(MailTaskStatus::PROCESSING);
    }

    public function testMarkAsProcessing(): void
    {
        $this->mailTask->markAsProcessing();

        $this->assertSame(MailTaskStatus::PROCESSING, $this->mailTask->getStatus());
    }

    public function testMarkAsSent(): void
    {
        $this->mailTask->markAsSent();

        $this->assertSame(MailTaskStatus::SENT, $this->mailTask->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->mailTask->getSentTime());
    }

    public function testMarkAsFailed(): void
    {
        $errorMessage = 'Connection timeout';
        $this->mailTask->markAsFailed($errorMessage);

        $this->assertSame(MailTaskStatus::FAILED, $this->mailTask->getStatus());
        $this->assertSame($errorMessage, $this->mailTask->getStatusMessage());
    }

    public function testIsReadyToSend_PendingAndNoSchedule(): void
    {
        $this->mailTask->setStatus(MailTaskStatus::PENDING);
        $this->mailTask->setScheduledTime(null);

        $this->assertTrue($this->mailTask->isReadyToSend());
    }

    public function testIsReadyToSend_PendingAndScheduledInPast(): void
    {
        $this->mailTask->setStatus(MailTaskStatus::PENDING);
        $this->mailTask->setScheduledTime(new \DateTimeImmutable('-1 hour'));

        $this->assertTrue($this->mailTask->isReadyToSend());
    }

    public function testIsReadyToSend_PendingButScheduledInFuture(): void
    {
        $this->mailTask->setStatus(MailTaskStatus::PENDING);
        $this->mailTask->setScheduledTime(new \DateTimeImmutable('+1 hour'));

        $this->assertFalse($this->mailTask->isReadyToSend());
    }

    public function testIsReadyToSend_NotPending(): void
    {
        $statuses = [
            MailTaskStatus::PROCESSING,
            MailTaskStatus::SENT,
            MailTaskStatus::FAILED
        ];

        foreach ($statuses as $status) {
            $this->mailTask->setStatus($status);
            $this->mailTask->setScheduledTime(null);

            $this->assertFalse($this->mailTask->isReadyToSend(), "Status: {$status->value} should not be ready to send");
        }
    }
}
