<?php

namespace Tourze\SMTPMailerBundle\Tests\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Message\SendMailMessage;
use Tourze\SMTPMailerBundle\MessageHandler\SendMailMessageHandler;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;
use Tourze\SMTPMailerBundle\Service\MailSenderService;

class SendMailMessageHandlerTest extends TestCase
{
    private MailTaskRepository|MockObject $mailTaskRepository;
    private MailSenderService|MockObject $mailSenderService;
    private LoggerInterface|MockObject $logger;
    private SendMailMessageHandler $handler;
    private EntityManagerInterface|MockObject $entityManager;
    
    protected function setUp(): void
    {
        $this->mailTaskRepository = $this->createMock(MailTaskRepository::class);
        $this->mailSenderService = $this->createMock(MailSenderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->handler = new SendMailMessageHandler(
            $this->mailTaskRepository,
            $this->mailSenderService,
            $this->logger,
            $this->entityManager
        );
    }
    
    public function testInvoke_Success(): void
    {
        // 准备任务 ID
        $taskId = 123;
        
        // 准备邮件任务
        $mailTask = $this->createPartialMock(MailTask::class, ['isReadyToSend', 'markAsProcessing', 'markAsSent']);
        $mailTask->method('isReadyToSend')->willReturn(true);
        
        // 设置预期行为
        $mailTask->expects($this->once())->method('markAsProcessing');
        $mailTask->expects($this->once())->method('markAsSent');
        
        // 设置 MailTaskRepository 预期行为
        $this->mailTaskRepository->method('find')->with($taskId)->willReturn($mailTask);
        
        // 设置 MailSenderService 预期行为
        $this->mailSenderService->method('sendMailTask')->with($mailTask)->willReturn(true);
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->exactly(2))->method('flush');
        
        // 创建消息
        $message = new SendMailMessage($taskId);
        
        // 调用处理器
        $this->handler->__invoke($message);
        
        // 无需断言，如果执行到这里没有异常，测试通过
    }
    
    public function testInvoke_TaskNotFound(): void
    {
        // 准备任务 ID
        $taskId = 123;
        
        // 设置 MailTaskRepository 预期行为 - 返回 null 表示任务不存在
        $this->mailTaskRepository->method('find')->with($taskId)->willReturn(null);
        
        // 设置 Logger 预期行为
        $this->logger->expects($this->once())
            ->method('error')
            ->with('邮件任务不存在', ['id' => $taskId]);
        
        // 创建消息
        $message = new SendMailMessage($taskId);
        
        // 设置预期异常
        $this->expectException(UnrecoverableMessageHandlingException::class);
        $this->expectExceptionMessage('邮件任务不存在: 123');
        
        // 调用处理器
        $this->handler->__invoke($message);
    }
    
    public function testInvoke_TaskNotReadyToSend(): void
    {
        // 准备任务 ID
        $taskId = 123;
        
        // 准备邮件任务
        $mailTask = $this->createPartialMock(MailTask::class, ['isReadyToSend', 'getStatus']);
        $mailTask->method('isReadyToSend')->willReturn(false);
        $mailTask->method('getStatus')->willReturn(MailTask::STATUS_SENT); // 已经发送过
        
        // 设置 MailTaskRepository 预期行为
        $this->mailTaskRepository->method('find')->with($taskId)->willReturn($mailTask);
        
        // 设置 Logger 预期行为
        $this->logger->expects($this->once())
            ->method('info')
            ->with('邮件任务不处于可发送状态', $this->anything());
        
        // 设置 MailSenderService 预期行为 - 不应该被调用
        $this->mailSenderService->expects($this->never())->method('sendMailTask');
        
        // 创建消息
        $message = new SendMailMessage($taskId);
        
        // 调用处理器
        $this->handler->__invoke($message);
    }
    
    public function testInvoke_SendingFailed(): void
    {
        // 准备任务 ID
        $taskId = 123;
        
        // 准备邮件任务
        $mailTask = $this->createPartialMock(MailTask::class, ['isReadyToSend', 'markAsProcessing', 'markAsFailed']);
        $mailTask->method('isReadyToSend')->willReturn(true);
        
        // 设置预期行为
        $mailTask->expects($this->once())->method('markAsProcessing');
        $mailTask->expects($this->once())->method('markAsFailed')->with('邮件发送失败');
        
        // 设置 MailTaskRepository 预期行为
        $this->mailTaskRepository->method('find')->with($taskId)->willReturn($mailTask);
        
        // 设置 MailSenderService 预期行为 - 发送失败
        $this->mailSenderService->method('sendMailTask')->with($mailTask)->willReturn(false);
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->exactly(2))->method('flush');
        
        // 创建消息
        $message = new SendMailMessage($taskId);
        
        // 调用处理器
        $this->handler->__invoke($message);
    }
    
    public function testInvoke_ThrowsException(): void
    {
        // 准备任务 ID
        $taskId = 123;
        
        // 准备邮件任务
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
            ->with('邮件发送异常', $this->anything());
        
        // 设置 EntityManager 预期行为
        $this->entityManager->expects($this->exactly(2))->method('flush');
        
        // 创建消息
        $message = new SendMailMessage($taskId);
        
        // 调用处理器
        $this->handler->__invoke($message);
    }
} 