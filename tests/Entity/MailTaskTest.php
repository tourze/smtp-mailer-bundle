<?php

namespace Tourze\SMTPMailerBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

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
        
        $scheduledAt = new \DateTimeImmutable('+1 hour');
        $this->mailTask->setScheduledAt($scheduledAt);
        $this->assertSame($scheduledAt, $this->mailTask->getScheduledAt());
        
        $this->mailTask->setStatus(MailTask::STATUS_PROCESSING);
        $this->assertSame(MailTask::STATUS_PROCESSING, $this->mailTask->getStatus());
        
        $this->mailTask->setStatusMessage('Processing message');
        $this->assertSame('Processing message', $this->mailTask->getStatusMessage());
        
        $smtpConfig = new SMTPConfig();
        $this->mailTask->setSmtpConfig($smtpConfig);
        $this->assertSame($smtpConfig, $this->mailTask->getSmtpConfig());
        
        $this->mailTask->setSelectorStrategy('round_robin');
        $this->assertSame('round_robin', $this->mailTask->getSelectorStrategy());
        
        $sentAt = new \DateTimeImmutable();
        $this->mailTask->setSentAt($sentAt);
        $this->assertSame($sentAt, $this->mailTask->getSentAt());
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->mailTask->getCreatedAt());
        $this->assertNull($this->mailTask->getUpdatedAt());
    }
    
    public function testPreUpdateLifecycleCallback(): void
    {
        $this->assertNull($this->mailTask->getUpdatedAt());
        
        $this->mailTask->setUpdatedAtValue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->mailTask->getUpdatedAt());
    }
    
    public function testMarkAsProcessing(): void
    {
        $this->mailTask->markAsProcessing();
        
        $this->assertSame(MailTask::STATUS_PROCESSING, $this->mailTask->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->mailTask->getUpdatedAt());
    }
    
    public function testMarkAsSent(): void
    {
        $this->mailTask->markAsSent();
        
        $this->assertSame(MailTask::STATUS_SENT, $this->mailTask->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->mailTask->getSentAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->mailTask->getUpdatedAt());
    }
    
    public function testMarkAsFailed(): void
    {
        $errorMessage = 'Connection timeout';
        $this->mailTask->markAsFailed($errorMessage);
        
        $this->assertSame(MailTask::STATUS_FAILED, $this->mailTask->getStatus());
        $this->assertSame($errorMessage, $this->mailTask->getStatusMessage());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->mailTask->getUpdatedAt());
    }
    
    public function testIsReadyToSend_PendingAndNoSchedule(): void
    {
        $this->mailTask->setStatus(MailTask::STATUS_PENDING);
        $this->mailTask->setScheduledAt(null);
        
        $this->assertTrue($this->mailTask->isReadyToSend());
    }
    
    public function testIsReadyToSend_PendingAndScheduledInPast(): void
    {
        $this->mailTask->setStatus(MailTask::STATUS_PENDING);
        $this->mailTask->setScheduledAt(new \DateTimeImmutable('-1 hour'));
        
        $this->assertTrue($this->mailTask->isReadyToSend());
    }
    
    public function testIsReadyToSend_PendingButScheduledInFuture(): void
    {
        $this->mailTask->setStatus(MailTask::STATUS_PENDING);
        $this->mailTask->setScheduledAt(new \DateTimeImmutable('+1 hour'));
        
        $this->assertFalse($this->mailTask->isReadyToSend());
    }
    
    public function testIsReadyToSend_NotPending(): void
    {
        $statuses = [
            MailTask::STATUS_PROCESSING,
            MailTask::STATUS_SENT,
            MailTask::STATUS_FAILED
        ];
        
        foreach ($statuses as $status) {
            $this->mailTask->setStatus($status);
            $this->mailTask->setScheduledAt(null);
            
            $this->assertFalse($this->mailTask->isReadyToSend(), "Status: $status should not be ready to send");
        }
    }
} 