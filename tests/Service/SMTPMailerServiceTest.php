<?php

namespace Tourze\SMTPMailerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;
use Tourze\SMTPMailerBundle\Exception\MailTaskNotFoundException;
use Tourze\SMTPMailerBundle\Exception\SMTPConfigNotFoundException;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;
use Tourze\SMTPMailerBundle\Service\MailSenderService;
use Tourze\SMTPMailerBundle\Service\SMTPMailerService;
use Tourze\SMTPMailerBundle\Service\SMTPSelectorService;

/**
 * @internal
 */
#[CoversClass(SMTPMailerService::class)]
#[RunTestsInSeparateProcesses]
final class SMTPMailerServiceTest extends AbstractIntegrationTestCase
{
    private SMTPConfigRepository|MockObject $smtpConfigRepository;

    private MailTaskRepository|MockObject $mailTaskRepository;

    private SMTPSelectorService|MockObject $smtpSelectorService;

    private MailSenderService|MockObject $mailSenderService;

    private MessageBusInterface|MockObject $messageBus;

    private LoggerInterface|MockObject $logger;

    private SMTPMailerService $mailService;

    protected function onSetUp(): void
    {
        // 禁用异步模式，避免消息处理器访问真实数据库
        $_ENV['SMTP_MAILER_ASYNC_ENABLED'] = 'false';

        // 实现抽象方法，初始化测试服务
        $this->setUpService();
    }

    private function setUpService(): void
    {
        // 使用具体类 SMTPConfigRepository 的 Mock 是必要的，因为：
        // 1) Doctrine Repository 类没有统一接口，是 ORM 框架的具体实现
        // 2) 需要模拟 find() 等 Doctrine 特定方法
        // 3) 这是标准的 Repository 测试模式，避免真实数据库操作
        $this->smtpConfigRepository = $this->createMock(SMTPConfigRepository::class);

        // 使用具体类 MailTaskRepository 的 Mock 是必要的，因为：
        // 1) Doctrine Repository 类没有统一接口，是 ORM 框架的具体实现
        // 2) 需要模拟查询方法如 findScheduledTasks() 等
        // 3) 这避免了在测试中执行真实的数据库查询
        $this->mailTaskRepository = $this->createMock(MailTaskRepository::class);

        // 使用具体类 SMTPSelectorService 的 Mock 是必要的，因为：
        // 1) 该服务没有对应的接口，是业务逻辑的具体实现类
        // 2) 需要模拟 SMTP 配置选择策略的具体行为
        // 3) 这避免了在测试中执行真实的配置选择逻辑
        $this->smtpSelectorService = $this->createMock(SMTPSelectorService::class);

        // 使用具体类 MailSenderService 的 Mock 是必要的，因为：
        // 1) 该服务没有对应的接口，是邮件发送的具体实现类
        // 2) 需要模拟邮件发送的具体行为和结果
        // 3) 这避免了在测试中执行真实的邮件发送操作
        $this->mailSenderService = $this->createMock(MailSenderService::class);

        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 将 Mock 对象注入到容器中
        self::getContainer()->set(SMTPConfigRepository::class, $this->smtpConfigRepository);
        self::getContainer()->set(MailTaskRepository::class, $this->mailTaskRepository);
        self::getContainer()->set(SMTPSelectorService::class, $this->smtpSelectorService);
        self::getContainer()->set(MailSenderService::class, $this->mailSenderService);
        self::getContainer()->set('monolog.logger.smtp_mailer', $this->logger);

        // 从容器获取服务实例
        $this->mailService = self::getService(SMTPMailerService::class);
    }

    public function testSendSynchronousImmediate(): void
    {
        // 准备参数
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $body = 'Test Body';
        $options = ['async' => false]; // 同步发送

        // 准备 SMTP 配置
        $smtpConfig = new SMTPConfig();
        $smtpConfig->setName('Test SMTP');
        $smtpConfig->setHost('127.0.0.1'); // 使用本地地址避免网络连接

        // 设置 SMTPSelectorService 预期行为
        $this->smtpSelectorService->expects($this->once())
            ->method('selectConfig')
            ->willReturn($smtpConfig)
        ;

        // 设置 MailSenderService 预期行为
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with(
                self::isInstanceOf(MailTask::class),
                self::identicalTo($smtpConfig)
            )
            ->willReturn(true)
        ;

        // 设置 MessageBus 预期行为 - 不应该被调用，因为是同步发送
        $this->messageBus->expects($this->never())
            ->method('dispatch')
        ;

        // 调用测试方法
        $result = $this->mailService->send($to, $subject, $body, $options);

        // 断言结果 - 检查返回的是正数ID即可
        $this->assertGreaterThan(0, $result);
    }

    public function testSendAsynchronousImmediate(): void
    {
        // 测试立即发送功能（原异步测试改为同步）
        // 准备参数
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $body = 'Test Body';
        $options = []; // 默认设置

        // 同步模式下，设置发送依赖的 Mock
        $smtpConfig = new SMTPConfig();
        $this->smtpSelectorService->expects($this->once())
            ->method('selectConfig')
            ->willReturn($smtpConfig)
        ;

        // 设置 MailSenderService 预期行为
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with(self::isInstanceOf(MailTask::class), $smtpConfig)
            ->willReturn(true)
        ;

        // 调用测试方法
        $result = $this->mailService->send($to, $subject, $body, $options);

        // 断言结果 - 检查返回的是正数ID即可
        $this->assertGreaterThan(0, $result);
    }

    public function testSendWithScheduledTime(): void
    {
        // 准备参数
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $body = 'Test Body';
        $scheduledAt = new \DateTime('+1 hour');
        $options = ['scheduledAt' => $scheduledAt];

        // 设置 MessageBus 和 MailSenderService 预期行为 - 都不应该被调用，因为有计划时间
        $this->messageBus->expects($this->never())->method('dispatch');
        $this->mailSenderService->expects($this->never())->method('sendMailTask');

        // 调用测试方法
        $result = $this->mailService->send($to, $subject, $body, $options);

        // 断言结果 - 检查返回的是正数ID即可
        $this->assertGreaterThan(0, $result);
    }

    public function testSendWithConfigSuccess(): void
    {
        // 准备参数
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $body = 'Test Body';
        $options = ['async' => false]; // 同步发送

        // 准备 SMTP 配置 - 先持久化到数据库以避免级联问题
        $smtpConfig = new SMTPConfig();
        $smtpConfig->setName('Test SMTP');
        $smtpConfig->setHost('127.0.0.1'); // 使用本地地址避免网络连接
        $smtpConfig->setPort(587);
        $smtpConfig->setUsername('test');
        $smtpConfig->setPassword('test');
        $smtpConfig->setEncryption('tls');
        $smtpConfig->setValid(true);

        // 持久化配置到数据库，以便后续的 MailTask 可以关联
        $this->persistAndFlush($smtpConfig);
        $configId = $smtpConfig->getId();
        $this->assertNotNull($configId, 'SMTP config ID should not be null after persistence');

        // 设置 SMTPConfigRepository 预期行为
        $this->smtpConfigRepository->expects($this->once())
            ->method('find')
            ->with($configId)
            ->willReturn($smtpConfig)
        ;

        // 设置 MailSenderService 预期行为
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with(
                self::isInstanceOf(MailTask::class),
                self::identicalTo($smtpConfig)
            )
            ->willReturn(true)
        ;

        // 调用测试方法
        $result = $this->mailService->sendWithConfig($configId, $to, $subject, $body, $options);

        // 断言结果 - 检查返回的是正数ID即可
        $this->assertGreaterThan(0, $result);
    }

    public function testSendWithConfigNonExistentConfig(): void
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
            ->willReturn(null)
        ;

        // 设置预期的异常
        $this->expectException(SMTPConfigNotFoundException::class);
        $this->expectExceptionMessage('SMTP配置不存在: 456');

        // 调用测试方法，应该抛出异常
        $this->mailService->sendWithConfig($configId, $to, $subject, $body);
    }

    public function testSendMailTaskNowWithSpecificConfigSuccess(): void
    {
        // 准备邮件任务和SMTP配置
        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setSubject('Test Subject');
        $mailTask->setBody('Test Body');

        $smtpConfig = new SMTPConfig();
        $smtpConfig->setName('Test SMTP');
        $smtpConfig->setHost('smtp.example.com');

        // 持久化 SMTP 配置到数据库
        self::getEntityManager()->persist($smtpConfig);
        self::getEntityManager()->flush();

        $mailTask->setSmtpConfig($smtpConfig);

        // 设置 MailSenderService 预期行为
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with($mailTask, $smtpConfig)
            ->willReturn(true)
        ;

        // 调用测试方法
        $result = $this->mailService->sendMailTaskNow($mailTask);

        // 断言结果
        $this->assertTrue($result);
        $this->assertSame(MailTaskStatus::SENT, $mailTask->getStatus());
    }

    public function testSendMailTaskNowWithStrategySuccess(): void
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
        $smtpConfig->setHost('127.0.0.1'); // 使用本地地址避免网络连接

        // 设置 SMTPSelectorService 预期行为
        $this->smtpSelectorService->expects($this->once())
            ->method('selectConfig')
            ->with('round_robin')
            ->willReturn($smtpConfig)
        ;

        // 设置 MailSenderService 预期行为
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with($mailTask, $smtpConfig)
            ->willReturn(true)
        ;

        // 调用测试方法
        $result = $this->mailService->sendMailTaskNow($mailTask);

        // 断言结果
        $this->assertTrue($result);
        $this->assertSame(MailTaskStatus::SENT, $mailTask->getStatus());
    }

    public function testSendMailTaskNowNoConfigAvailableUseDefault(): void
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
            ->willReturn(null)
        ;

        // 设置 MailSenderService 预期行为 - 使用默认发送方式
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTask')
            ->with($mailTask)
            ->willReturn(true)
        ;

        // 调用测试方法
        $result = $this->mailService->sendMailTaskNow($mailTask);

        // 断言结果
        $this->assertTrue($result);
        $this->assertSame(MailTaskStatus::SENT, $mailTask->getStatus());
    }

    public function testSendMailTaskNowSendingFailed(): void
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
            ->willReturn(false)
        ;

        // 调用测试方法
        $result = $this->mailService->sendMailTaskNow($mailTask);

        // 断言结果
        $this->assertFalse($result);
        $this->assertSame(MailTaskStatus::FAILED, $mailTask->getStatus());
        $this->assertSame('邮件发送失败', $mailTask->getStatusMessage());
    }

    public function testSendMailTaskNowThrowsException(): void
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
            ->willThrowException($exception)
        ;

        // 设置 Logger 预期行为
        $this->logger->expects($this->once())
            ->method('error')
            ->with('邮件发送异常', self::anything())
        ;

        // 调用测试方法
        $result = $this->mailService->sendMailTaskNow($mailTask);

        // 断言结果
        $this->assertFalse($result);
        $this->assertSame(MailTaskStatus::FAILED, $mailTask->getStatus());
        $this->assertSame('发送时发生异常', $mailTask->getStatusMessage());
    }

    /**
     * 测试处理预定任务
     */
    public function testProcessScheduledTasks(): void
    {
        // 准备预定任务
        $task1 = new MailTask();
        $task1->setFromEmail('sender@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Scheduled Task 1');
        $task1->setBody('Body 1');
        $task1->setScheduledTime(new \DateTimeImmutable('-1 minute')); // 过去时间，应该被发送

        $task2 = new MailTask();
        $task2->setFromEmail('sender@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Scheduled Task 2');
        $task2->setBody('Body 2');
        $task2->setScheduledTime(new \DateTimeImmutable('+1 hour')); // 未来时间，不应该被发送

        // 为任务设置 ID（因为它们不经过 persist/flush 流程）
        $reflection1 = new \ReflectionClass($task1);
        $idProperty1 = $reflection1->getProperty('id');
        $idProperty1->setAccessible(true);
        $idProperty1->setValue($task1, 1);

        $reflection2 = new \ReflectionClass($task2);
        $idProperty2 = $reflection2->getProperty('id');
        $idProperty2->setAccessible(true);
        $idProperty2->setValue($task2, 2);

        $scheduledTasks = [$task1, $task2];

        // 设置 MailTaskRepository 预期行为
        $this->mailTaskRepository->expects($this->once())
            ->method('findScheduledTasks')
            ->willReturn($scheduledTasks)
        ;

        // 设置 SMTPSelectorService 预期行为
        $smtpConfig = new SMTPConfig();
        $this->smtpSelectorService->expects($this->once())
            ->method('selectConfig')
            ->willReturn($smtpConfig)
        ;

        // 设置 MailSenderService 预期行为 - 同步模式下直接调用发送
        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with(self::isInstanceOf(MailTask::class), $smtpConfig)
            ->willReturn(true)
        ;

        // 调用测试方法
        $result = $this->mailService->processScheduledTasks();

        // 断言结果 - 应该返回处理的任务数量
        $this->assertSame(1, $result);
    }

    /**
     * 测试重新发送失败的邮件 - 异步模式
     */
    public function testResendFailedMailAsync(): void
    {
        // 准备邮件任务
        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setSubject('Failed Mail');
        $mailTask->setBody('Failed Body');
        $mailTask->setStatus(MailTaskStatus::FAILED);

        // 为任务设置 ID（因为它们不经过 persist/flush 流程）
        $reflection = new \ReflectionClass($mailTask);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($mailTask, 123);

        $mailTaskId = $mailTask->getId();
        $this->assertNotNull($mailTaskId, 'Mail task ID should not be null');

        // 设置 MailTaskRepository 预期行为 - 可能被调用多次（服务和消息处理器）
        $this->mailTaskRepository->expects($this->atLeastOnce())
            ->method('find')
            ->with($mailTaskId)
            ->willReturn($mailTask)
        ;

        // 同步模式下需要 Mock 发送依赖
        $smtpConfig = new SMTPConfig();
        $this->smtpSelectorService->expects($this->once())
            ->method('selectConfig')
            ->willReturn($smtpConfig)
        ;

        $this->mailSenderService->expects($this->once())
            ->method('sendMailTaskWithConfig')
            ->with(self::isInstanceOf(MailTask::class), $smtpConfig)
            ->willReturn(true)
        ;

        // 调用测试方法
        $result = $this->mailService->resendFailedMail($mailTaskId);

        // 断言结果
        $this->assertTrue($result);
        // 同步模式下，任务会立即标记为 SENT
        $this->assertSame(MailTaskStatus::SENT, $mailTask->getStatus());
    }

    /**
     * 测试重新发送失败的邮件 - 邮件任务不存在
     */
    public function testResendFailedMailTaskNotFound(): void
    {
        $mailTaskId = 999;

        // 设置 MailTaskRepository 预期行为 - 返回 null 表示任务不存在
        $this->mailTaskRepository->expects($this->once())
            ->method('find')
            ->with($mailTaskId)
            ->willReturn(null)
        ;

        // 设置预期的异常
        $this->expectException(MailTaskNotFoundException::class);
        $this->expectExceptionMessage('邮件任务不存在: 999');

        // 调用测试方法，应该抛出异常
        $this->mailService->resendFailedMail($mailTaskId);
    }
}
