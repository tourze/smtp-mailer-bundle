<?php

namespace Tourze\SMTPMailerBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Message\SendMailMessage;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;
use Tourze\SMTPMailerBundle\Service\MailSenderService;
use Tourze\SMTPMailerBundle\Service\SMTPMailerService;
use Tourze\SMTPMailerBundle\Service\SMTPSelectorService;

class SMTPMailerServiceTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private SMTPConfigRepository|MockObject $smtpConfigRepository;
    private MailTaskRepository|MockObject $mailTaskRepository;
    private SMTPSelectorService|MockObject $smtpSelectorService;
    private MailSenderService|MockObject $mailSenderService;
    private MessageBusInterface|MockObject $messageBus;
    private LoggerInterface|MockObject $logger;
    private SMTPMailerService $mailService;
    
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->smtpConfigRepository = $this->createMock(SMTPConfigRepository::class);
        $this->mailTaskRepository = $this->createMock(MailTaskRepository::class);
        $this->smtpSelectorService = $this->createMock(SMTPSelectorService::class);
        $this->mailSenderService = $this->createMock(MailSenderService::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->mailService = new SMTPMailerService(
            $this->entityManager,
            $this->smtpConfigRepository,
            $this->mailTaskRepository,
            $this->smtpSelectorService,
            $this->mailSenderService,
            $this->messageBus,
            $this->logger,
        );
    }
    
    public function testSend_SynchronousImmediate(): void
    {
        // 准备参数
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $body = 'Test Body';
        $options = ['async' => false]; // 同步发送
        
        // 准备 SMTP 配置
        $smtpConfig = new SMTPConfig();
        $smtpConfig->setName('Test SMTP');
        
        // 捕获持久化的 MailTask
        $capturedMailTask = null;
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($mailTask) use (&$capturedMailTask) {
                $capturedMailTask = $mailTask;
                return $mailTask instanceof MailTask 
                    && $mailTask->getToEmail() === 'recipient@example.com';
            }));
        
        // 模拟 ID 设置
        $taskId = 123;
        $this->entityManager->expects($this->atLeast(1))
            ->method('flush')
            ->willReturnCallback(function () use (&$capturedMailTask, $taskId) {
                // 只在第一次调用时设置ID
                if ($capturedMailTask && $capturedMailTask->getId() === null) {
                    $reflectionClass = new \ReflectionClass($capturedMailTask);
                    $property = $reflectionClass->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($capturedMailTask, $taskId);
                }
            });
        
        // 设置 SMTPSelectorService 预期行为
        $this->smtpSelectorService->expects($this->once())
            ->method('selectConfig')
            ->willReturn($smtpConfig);
            
        // 设置 MailSenderService 预期行为
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with(
                $this->isInstanceOf(MailTask::class),
                $this->identicalTo($smtpConfig)
            )
            ->willReturn(true);
        
        // 设置 MessageBus 预期行为 - 不应该被调用，因为是同步发送
        $this->messageBus->expects($this->never())
            ->method('dispatch');
        
        // 调用测试方法
        $result = $this->mailService->send($to, $subject, $body, $options);
        
        // 断言结果
        $this->assertSame($taskId, $result);
    }
    
    public function testSend_AsynchronousImmediate(): void
    {
        // 准备参数
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $body = 'Test Body';
        $options = []; // 默认异步发送
        
        // 捕获持久化的 MailTask
        $capturedMailTask = null;
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($mailTask) use (&$capturedMailTask) {
                $capturedMailTask = $mailTask;
                return $mailTask instanceof MailTask 
                    && $mailTask->getToEmail() === 'recipient@example.com';
            }));
        
        // 模拟 ID 设置
        $taskId = 123;
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use (&$capturedMailTask, $taskId) {
                $reflectionClass = new \ReflectionClass($capturedMailTask);
                $property = $reflectionClass->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($capturedMailTask, $taskId);
            });
        
        // 设置 MessageBus 预期行为 - 应该被调用发送异步消息
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($taskId) {
                return $message instanceof SendMailMessage 
                    && $message->getMailTaskId() === $taskId;
            }))
            ->willReturn(new \Symfony\Component\Messenger\Envelope(new SendMailMessage($taskId)));
        
        // 设置 MailSenderService 预期行为 - 不应该被调用，因为是异步发送
        $this->mailSenderService->expects($this->never())
            ->method('sendMailTask');
        
        // 调用测试方法
        $result = $this->mailService->send($to, $subject, $body, $options);
        
        // 断言结果
        $this->assertSame($taskId, $result);
    }
    
    public function testSend_WithScheduledTime(): void
    {
        // 准备参数
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $body = 'Test Body';
        $scheduledAt = new \DateTime('+1 hour');
        $options = ['scheduledAt' => $scheduledAt];
        
        // 捕获持久化的 MailTask
        $capturedMailTask = null;
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($mailTask) use (&$capturedMailTask, $scheduledAt) {
                $capturedMailTask = $mailTask;
                
                // 验证 scheduledAt 值被正确设置为 DateTimeImmutable
                $mailTaskScheduledAt = $mailTask->getScheduledAt();
                
                return $mailTask instanceof MailTask 
                    && $mailTaskScheduledAt instanceof \DateTimeImmutable
                    && $mailTaskScheduledAt->getTimestamp() === $scheduledAt->getTimestamp();
            }));
        
        // 模拟 ID 设置
        $taskId = 123;
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use (&$capturedMailTask, $taskId) {
                $reflectionClass = new \ReflectionClass($capturedMailTask);
                $property = $reflectionClass->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($capturedMailTask, $taskId);
            });
        
        // 设置 MessageBus 和 MailSenderService 预期行为 - 都不应该被调用，因为有计划时间
        $this->messageBus->expects($this->never())->method('dispatch');
        $this->mailSenderService->expects($this->never())->method('sendMailTask');
        
        // 调用测试方法
        $result = $this->mailService->send($to, $subject, $body, $options);
        
        // 断言结果
        $this->assertSame($taskId, $result);
    }
    
    public function testSendWithConfig_Success(): void
    {
        // 准备参数
        $configId = 456;
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $body = 'Test Body';
        $options = ['async' => false]; // 同步发送
        
        // 准备 SMTP 配置
        $smtpConfig = new SMTPConfig();
        $smtpConfig->setName('Test SMTP');
        
        // 设置 SMTPConfigRepository 预期行为
        $this->smtpConfigRepository->expects($this->once())
            ->method('find')
            ->with($configId)
            ->willReturn($smtpConfig);
        
        // 捕获持久化的 MailTask
        $capturedMailTask = null;
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($mailTask) use (&$capturedMailTask, $smtpConfig) {
                $capturedMailTask = $mailTask;
                return $mailTask instanceof MailTask 
                    && $mailTask->getSmtpConfig() === $smtpConfig
                    && $mailTask->getToEmail() === 'recipient@example.com';
            }));
        
        // 模拟 ID 设置
        $taskId = 123;
        $this->entityManager->expects($this->atLeast(1))
            ->method('flush')
            ->willReturnCallback(function () use (&$capturedMailTask, $taskId) {
                // 只在第一次调用时设置ID
                if ($capturedMailTask && $capturedMailTask->getId() === null) {
                    $reflectionClass = new \ReflectionClass($capturedMailTask);
                    $property = $reflectionClass->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($capturedMailTask, $taskId);
                }
            });
        
        // 设置 MailSenderService 预期行为
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with(
                $this->isInstanceOf(MailTask::class),
                $this->identicalTo($smtpConfig)
            )
            ->willReturn(true);
        
        // 调用测试方法
        $result = $this->mailService->sendWithConfig($configId, $to, $subject, $body, $options);
        
        // 断言结果
        $this->assertSame($taskId, $result);
    }
    
    public function testSendWithConfig_NonExistentConfig(): void
    {
        // 准备参数
        $configId = 456;
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $body = 'Test Body';
        
        // 设置 SMTPConfigRepository 预期行为 - 返回 null 表示配置不存在
        $this->smtpConfigRepository->expects($this->once())
            ->method('find')
            ->with($configId)
            ->willReturn(null);
        
        // 设置预期的异常
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SMTP配置不存在: 456');
        
        // 调用测试方法，应该抛出异常
        $this->mailService->sendWithConfig($configId, $to, $subject, $body);
    }
    
    public function testSendMailTaskNow_WithSpecificConfig_Success(): void
    {
        // 准备邮件任务和SMTP配置
        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setSubject('Test Subject');
        $mailTask->setBody('Test Body');
        
        $smtpConfig = new SMTPConfig();
        $smtpConfig->setName('Test SMTP');
        $mailTask->setSmtpConfig($smtpConfig);
        
        // 设置 MailSenderService 预期行为
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with($mailTask, $smtpConfig)
            ->willReturn(true);
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');
        
        // 调用测试方法
        $result = $this->mailService->sendMailTaskNow($mailTask);
        
        // 断言结果
        $this->assertTrue($result);
        $this->assertSame(MailTask::STATUS_SENT, $mailTask->getStatus());
    }
    
    public function testSendMailTaskNow_WithStrategy_Success(): void
    {
        // 准备邮件任务
        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setSubject('Test Subject');
        $mailTask->setBody('Test Body');
        $mailTask->setSelectorStrategy('round_robin');
        
        // 准备SMTP配置
        $smtpConfig = new SMTPConfig();
        $smtpConfig->setName('Strategy Selected SMTP');
        
        // 设置 SMTPSelectorService 预期行为
        $this->smtpSelectorService->expects($this->once())
            ->method('selectConfig')
            ->with('round_robin')
            ->willReturn($smtpConfig);
        
        // 设置 MailSenderService 预期行为
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with($mailTask, $smtpConfig)
            ->willReturn(true);
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');
        
        // 调用测试方法
        $result = $this->mailService->sendMailTaskNow($mailTask);
        
        // 断言结果
        $this->assertTrue($result);
        $this->assertSame(MailTask::STATUS_SENT, $mailTask->getStatus());
    }
    
    public function testSendMailTaskNow_NoConfigAvailable_UseDefault(): void
    {
        // 准备邮件任务
        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setSubject('Test Subject');
        $mailTask->setBody('Test Body');
        $mailTask->setSelectorStrategy('round_robin');
        
        // 设置 SMTPSelectorService 预期行为 - 返回 null 表示没有可用配置
        $this->smtpSelectorService->expects($this->once())
            ->method('selectConfig')
            ->with('round_robin')
            ->willReturn(null);
        
        // 设置 MailSenderService 预期行为 - 使用默认发送方式
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTask')
            ->with($mailTask)
            ->willReturn(true);
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');
        
        // 调用测试方法
        $result = $this->mailService->sendMailTaskNow($mailTask);
        
        // 断言结果
        $this->assertTrue($result);
        $this->assertSame(MailTask::STATUS_SENT, $mailTask->getStatus());
    }
    
    public function testSendMailTaskNow_SendingFailed(): void
    {
        // 准备邮件任务
        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setSubject('Test Subject');
        $mailTask->setBody('Test Body');
        
        // 设置 MailSenderService 预期行为 - 发送失败
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTask')
            ->with($mailTask)
            ->willReturn(false);
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');
        
        // 调用测试方法
        $result = $this->mailService->sendMailTaskNow($mailTask);
        
        // 断言结果
        $this->assertFalse($result);
        $this->assertSame(MailTask::STATUS_FAILED, $mailTask->getStatus());
        $this->assertSame('邮件发送失败', $mailTask->getStatusMessage());
    }
    
    public function testSendMailTaskNow_ThrowsException(): void
    {
        // 准备邮件任务
        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setSubject('Test Subject');
        $mailTask->setBody('Test Body');
        
        // 准备异常
        $exception = new \Exception('发送时发生异常');
        
        // 设置 MailSenderService 预期行为 - 抛出异常
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTask')
            ->with($mailTask)
            ->willThrowException($exception);
        
        // 设置 Logger 预期行为
        $this->logger->expects($this->once())
            ->method('error')
            ->with('邮件发送异常', $this->anything());
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');
        
        // 调用测试方法
        $result = $this->mailService->sendMailTaskNow($mailTask);
        
        // 断言结果
        $this->assertFalse($result);
        $this->assertSame(MailTask::STATUS_FAILED, $mailTask->getStatus());
        $this->assertSame('发送时发生异常', $mailTask->getStatusMessage());
    }
} 