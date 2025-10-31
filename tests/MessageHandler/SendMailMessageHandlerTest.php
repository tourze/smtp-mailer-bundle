<?php

namespace Tourze\SMTPMailerBundle\Tests\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;
use Tourze\SMTPMailerBundle\Message\SendMailMessage;
use Tourze\SMTPMailerBundle\MessageHandler\SendMailMessageHandler;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;
use Tourze\SMTPMailerBundle\Service\MailSenderService;

/**
 * @internal
 */
#[CoversClass(SendMailMessageHandler::class)]
#[RunTestsInSeparateProcesses]
final class SendMailMessageHandlerTest extends AbstractIntegrationTestCase
{
    private MailTaskRepository|MockObject $mailTaskRepository;

    private MailSenderService|MockObject $mailSenderService;

    private LoggerInterface|MockObject $logger;

    private SendMailMessageHandler $handler;

    protected function onSetUp(): void
    {
        $this->setUpHandler();
    }

    private function setUpHandler(): void
    {
        $this->mailTaskRepository = $this->createMock(MailTaskRepository::class);
        $this->mailSenderService = $this->createMock(MailSenderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 将 Mock 对象注入到容器中
        self::getContainer()->set(MailTaskRepository::class, $this->mailTaskRepository);
        self::getContainer()->set(MailSenderService::class, $this->mailSenderService);
        self::getContainer()->set('monolog.logger.smtp_mailer', $this->logger);

        // 从容器获取服务实例
        $this->handler = self::getService(SendMailMessageHandler::class);
    }

    public function testInvokeSuccess(): void
    {
        // 准备任务 ID
        $taskId = 123;

        // 准备邮件任务
        // 使用具体类 MailTask 的 Mock 是必要的，因为：
        // 1) MailTask 是 Doctrine Entity，没有对应的接口
        // 2) 需要模拟实体的状态方法以控制测试流程
        // 3) 这避免了创建真实的 Entity 实例和数据库操作
        $mailTask = $this->createPartialMock(MailTask::class, ['isReadyToSend', 'markAsProcessing', 'markAsSent']);
        $mailTask->method('isReadyToSend')->willReturn(true);

        // 设置预期行为
        $mailTask->expects($this->once())->method('markAsProcessing');
        $mailTask->expects($this->once())->method('markAsSent');

        // 设置 MailTaskRepository 预期行为
        $this->mailTaskRepository->method('find')->with($taskId)->willReturn($mailTask);

        // 设置 MailSenderService 预期行为
        $this->mailSenderService->method('sendMailTask')->with($mailTask)->willReturn(true);

        // EntityManager 会在处理过程中调用 flush

        // 创建消息
        $message = new SendMailMessage($taskId);

        // 调用处理器
        $this->handler->__invoke($message);

        // 无需断言，如果执行到这里没有异常，测试通过
    }

    public function testInvokeTaskNotFound(): void
    {
        // 准备任务 ID
        $taskId = 123;

        // 设置 MailTaskRepository 预期行为 - 返回 null 表示任务不存在
        $this->mailTaskRepository->method('find')->with($taskId)->willReturn(null);

        // 设置 Logger 预期行为
        $this->logger->expects($this->once())
            ->method('error')
            ->with('邮件任务不存在', ['id' => $taskId])
        ;

        // 创建消息
        $message = new SendMailMessage($taskId);

        // 设置预期异常
        $this->expectException(UnrecoverableMessageHandlingException::class);
        $this->expectExceptionMessage('邮件任务不存在: 123');

        // 调用处理器
        $this->handler->__invoke($message);
    }

    public function testInvokeTaskNotReadyToSend(): void
    {
        // 准备任务 ID
        $taskId = 123;

        // 准备邮件任务
        // 使用具体类 MailTask 的 Mock 是必要的，因为：
        // 1) MailTask 是 Doctrine Entity，没有对应的接口
        // 2) 需要模拟实体的状态检查方法以验证业务逻辑
        // 3) 这避免了创建真实的 Entity 实例和复杂的状态设置
        $mailTask = $this->createPartialMock(MailTask::class, ['isReadyToSend', 'getStatus']);
        $mailTask->method('isReadyToSend')->willReturn(false);
        $mailTask->method('getStatus')->willReturn(MailTaskStatus::SENT); // 已经发送过

        // 设置 MailTaskRepository 预期行为
        $this->mailTaskRepository->method('find')->with($taskId)->willReturn($mailTask);

        // 设置 Logger 预期行为
        $this->logger->expects($this->once())
            ->method('info')
            ->with('邮件任务不处于可发送状态', self::anything())
        ;

        // 设置 MailSenderService 预期行为 - 不应该被调用
        $this->mailSenderService->expects($this->never())->method('sendMailTask');

        // 创建消息
        $message = new SendMailMessage($taskId);

        // 调用处理器
        $this->handler->__invoke($message);
    }

    public function testInvokeSendingFailed(): void
    {
        // 准备任务 ID
        $taskId = 123;

        // 准备邮件任务
        // 使用具体类 MailTask 的 Mock 是必要的，因为：
        // 1) MailTask 是 Doctrine Entity，没有对应的接口
        // 2) 需要模拟实体的状态方法以测试失败处理逻辑
        // 3) 这避免了创建真实的 Entity 实例和数据库状态管理
        $mailTask = $this->createPartialMock(MailTask::class, ['isReadyToSend', 'markAsProcessing', 'markAsFailed']);
        $mailTask->method('isReadyToSend')->willReturn(true);

        // 设置预期行为
        $mailTask->expects($this->once())->method('markAsProcessing');
        $mailTask->expects($this->once())->method('markAsFailed')->with('邮件发送失败');

        // 设置 MailTaskRepository 预期行为
        $this->mailTaskRepository->method('find')->with($taskId)->willReturn($mailTask);

        // 设置 MailSenderService 预期行为 - 发送失败
        $this->mailSenderService->method('sendMailTask')->with($mailTask)->willReturn(false);

        // EntityManager 会在处理过程中调用 flush

        // 创建消息
        $message = new SendMailMessage($taskId);

        // 调用处理器
        $this->handler->__invoke($message);
    }

    public function testInvokeThrowsException(): void
    {
        // 准备任务 ID
        $taskId = 123;

        // 准备邮件任务
        // 使用具体类 MailTask 的 Mock 是必要的，因为：
        // 1) MailTask 是 Doctrine Entity，没有对应的接口
        // 2) 需要模拟实体的状态方法以测试异常处理逻辑
        // 3) 这避免了创建真实的 Entity 实例和数据库异常处理
        $mailTask = $this->createPartialMock(MailTask::class, ['isReadyToSend', 'markAsProcessing', 'markAsFailed']);
        $mailTask->method('isReadyToSend')->willReturn(true);

        // 准备异常
        $exception = new \Exception('发送时发生异常');

        // 设置预期行为
        $mailTask->expects($this->once())->method('markAsProcessing');
        $mailTask->expects($this->once())->method('markAsFailed')->with('发送时发生异常');

        // 设置 MailTaskRepository 预期行为
        $this->mailTaskRepository->method('find')->with($taskId)->willReturn($mailTask);

        // 设置 MailSenderService 预期行为 - 抛出异常
        $this->mailSenderService->method('sendMailTask')->with($mailTask)->willThrowException($exception);

        // 设置 Logger 预期行为
        $this->logger->expects($this->once())
            ->method('error')
            ->with('邮件发送异常', self::anything())
        ;

        // EntityManager 会在处理过程中调用 flush

        // 创建消息
        $message = new SendMailMessage($taskId);

        // 调用处理器
        $this->handler->__invoke($message);
    }
}
