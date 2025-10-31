<?php

namespace Tourze\SMTPMailerBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;

/**
 * @internal
 */
#[CoversClass(MailTask::class)]
final class MailTaskTest extends AbstractEntityTestCase
{
    protected function createEntity(): MailTask
    {
        return new MailTask();
    }

    /**
     * @return array<int, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            ['fromEmail', 'sender@example.com'],
            ['fromName', 'Sender Name'],
            ['toEmail', 'recipient@example.com'],
            ['toName', 'Recipient Name'],
            ['cc', ['cc1@example.com', 'cc2@example.com']],
            ['bcc', ['bcc1@example.com', 'bcc2@example.com']],
            ['subject', 'Test Subject'],
            ['body', 'Test body content'],
            ['attachments', [
                ['path' => '/path/to/file.pdf', 'name' => 'file.pdf', 'mime' => 'application/pdf'],
                ['data' => base64_encode('binary data'), 'name' => 'data.txt', 'mime' => 'text/plain'],
            ]],
            ['scheduledTime', new \DateTimeImmutable('+1 hour')],
            ['status', MailTaskStatus::PROCESSING],
            ['statusMessage', 'Processing message'],
            ['selectorStrategy', 'round_robin'],
            ['sentTime', new \DateTimeImmutable()],
        ];
    }

    public function testSmtpConfigRelation(): void
    {
        $mailTask = $this->createEntity();
        $smtpConfig = new SMTPConfig();
        $mailTask->setSmtpConfig($smtpConfig);
        $this->assertSame($smtpConfig, $mailTask->getSmtpConfig());
    }

    public function testTimestampFields(): void
    {
        $mailTask = $this->createEntity();
        // 在测试环境中，时间戳字段在没有Doctrine管理时为null是正常的
        $this->assertNull($mailTask->getCreateTime());
        $this->assertNull($mailTask->getUpdateTime());
    }

    public function testIsHtmlSetterAndGetter(): void
    {
        $mailTask = $this->createEntity();

        // 测试 setter 和 getter
        $mailTask->setIsHtml(true);
        $this->assertTrue($mailTask->isHtml());

        $mailTask->setIsHtml(false);
        $this->assertFalse($mailTask->isHtml());
    }

    public function testPreUpdateLifecycleCallback(): void
    {
        $mailTask = $this->createEntity();
        $this->assertNull($mailTask->getUpdateTime());

        $mailTask->setStatus(MailTaskStatus::PROCESSING);
    }

    public function testMarkAsProcessing(): void
    {
        $mailTask = $this->createEntity();
        $mailTask->markAsProcessing();

        $this->assertSame(MailTaskStatus::PROCESSING, $mailTask->getStatus());
    }

    public function testMarkAsSent(): void
    {
        $mailTask = $this->createEntity();
        $mailTask->markAsSent();

        $this->assertSame(MailTaskStatus::SENT, $mailTask->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $mailTask->getSentTime());
    }

    public function testMarkAsFailed(): void
    {
        $mailTask = $this->createEntity();
        $errorMessage = 'Connection timeout';
        $mailTask->markAsFailed($errorMessage);

        $this->assertSame(MailTaskStatus::FAILED, $mailTask->getStatus());
        $this->assertSame($errorMessage, $mailTask->getStatusMessage());
    }

    public function testIsReadyToSendPendingAndNoSchedule(): void
    {
        $mailTask = $this->createEntity();
        $mailTask->setStatus(MailTaskStatus::PENDING);
        $mailTask->setScheduledTime(null);

        $this->assertTrue($mailTask->isReadyToSend());
    }

    public function testIsReadyToSendPendingAndScheduledInPast(): void
    {
        $mailTask = $this->createEntity();
        $mailTask->setStatus(MailTaskStatus::PENDING);
        $mailTask->setScheduledTime(new \DateTimeImmutable('-1 hour'));

        $this->assertTrue($mailTask->isReadyToSend());
    }

    public function testIsReadyToSendPendingButScheduledInFuture(): void
    {
        $mailTask = $this->createEntity();
        $mailTask->setStatus(MailTaskStatus::PENDING);
        $mailTask->setScheduledTime(new \DateTimeImmutable('+1 hour'));

        $this->assertFalse($mailTask->isReadyToSend());
    }

    public function testIsReadyToSendNotPending(): void
    {
        $statuses = [
            MailTaskStatus::PROCESSING,
            MailTaskStatus::SENT,
            MailTaskStatus::FAILED,
        ];

        foreach ($statuses as $status) {
            $mailTask = $this->createEntity();
            $mailTask->setStatus($status);
            $mailTask->setScheduledTime(null);

            $this->assertFalse($mailTask->isReadyToSend(), "Status: {$status->value} should not be ready to send");
        }
    }
}
