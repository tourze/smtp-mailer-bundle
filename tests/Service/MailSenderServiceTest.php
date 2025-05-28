<?php

namespace Tourze\SMTPMailerBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Service\MailSenderService;

class MailSenderServiceTest extends TestCase
{
    private MailerInterface|MockObject $mailer;
    private LoggerInterface|MockObject $logger;
    private MailSenderService $mailSenderService;
    
    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mailSenderService = new MailSenderService($this->mailer, $this->logger);
    }
    
    public function testSendMailTask_Success(): void
    {
        // 准备一个邮件任务
        $mailTask = $this->createMailTask();
        
        // 设置 mailer mock 预期行为：成功发送邮件
        $this->mailer->expects($this->once())
            ->method('send');
        
        // 调用测试方法
        $result = $this->mailSenderService->sendMailTask($mailTask);
        
        // 断言结果为成功
        $this->assertTrue($result);
    }
    
    public function testSendMailTask_ThrowsTransportException(): void
    {
        // 准备一个邮件任务
        $mailTask = $this->createMailTask();
        
        // 创建一个 TransportException 的 mock
        $exception = $this->createMock(TransportExceptionInterface::class);
        
        // 设置 mailer mock 预期行为：抛出异常
        $this->mailer->expects($this->once())
            ->method('send')
            ->willThrowException($exception);
        
        // 设置 logger 预期行为：记录错误
        $this->logger->expects($this->once())
            ->method('error')
            ->with('邮件发送失败', $this->anything());
        
        // 调用测试方法
        $result = $this->mailSenderService->sendMailTask($mailTask);
        
        // 断言结果为失败
        $this->assertFalse($result);
    }
    
    public function testSendMailTaskWithConfig_Success(): void
    {
        // 准备一个邮件任务
        $mailTask = $this->createMailTask();
        
        // 准备一个 SMTP 配置
        $smtpConfig = new SMTPConfig();
        $smtpConfig->setHost('smtp.example.com');
        $smtpConfig->setPort(587);
        $smtpConfig->setUsername('user');
        $smtpConfig->setPassword('pass');
        $smtpConfig->setEncryption('tls');
        
        // 由于这个方法内部使用了静态方法 Transport::fromDsn()，我们无法直接 mock
        // 但我们可以测试在真实环境下会发生什么（预期会因为无效的SMTP服务器而失败）
        // 这里我们主要测试方法调用不会抛出致命错误，并且错误会被正确处理
        
        // 设置 logger 预期行为：记录错误（因为SMTP服务器不存在）
        $this->logger->expects($this->once())
            ->method('error')
            ->with('邮件发送失败', $this->callback(function ($context) {
                // 验证错误日志包含正确的上下文信息
                // task_id 和 smtp_config_id 可能为 null（因为实体未保存到数据库）
                return isset($context['error']) && 
                       array_key_exists('task_id', $context) && 
                       array_key_exists('smtp_config_id', $context);
            }));
        
        // 调用测试方法 - 预期会失败但不会抛出异常
        $result = $this->mailSenderService->sendMailTaskWithConfig($mailTask, $smtpConfig);
        
        // 断言结果为失败（因为SMTP服务器不存在）
        $this->assertFalse($result);
    }
    
    public function testSendMailTaskWithConfig_ThrowsTransportException(): void
    {
        // 准备一个邮件任务
        $mailTask = $this->createMailTask();
        
        // 准备一个 SMTP 配置（使用不存在的主机名）
        $smtpConfig = new SMTPConfig();
        $smtpConfig->setHost('nonexistent.invalid.domain');
        $smtpConfig->setPort(587);
        $smtpConfig->setUsername('user');
        $smtpConfig->setPassword('pass');
        $smtpConfig->setEncryption('tls');
        
        // 设置 logger 预期行为：记录错误
        $this->logger->expects($this->once())
            ->method('error')
            ->with('邮件发送失败', $this->callback(function ($context) {
                // 验证错误日志包含正确的上下文信息
                return isset($context['error']) && 
                       array_key_exists('task_id', $context) && 
                       array_key_exists('smtp_config_id', $context);
            }));
        
        // 调用测试方法 - 预期会失败
        $result = $this->mailSenderService->sendMailTaskWithConfig($mailTask, $smtpConfig);
        
        // 断言结果为失败
        $this->assertFalse($result);
    }
    
    public function testCreateEmailFromTask_BasicProperties(): void
    {
        // 由于 createEmailFromTask 是私有方法，我们需要通过公共方法间接测试
        // 我们将通过 sendMailTask 验证 Email 对象的创建
        
        // 准备一个简单的邮件任务
        $mailTask = $this->createMailTask();
        
        // 设置 mailer 预期行为：捕获传递给 send 方法的 Email 对象
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) use ($mailTask) {
                $this->assertInstanceOf(Email::class, $email);
                
                // 验证基本属性
                $this->assertEquals($mailTask->getFromEmail(), $email->getFrom()[0]->getAddress());
                $this->assertEquals($mailTask->getFromName(), $email->getFrom()[0]->getName());
                $this->assertEquals($mailTask->getToEmail(), $email->getTo()[0]->getAddress());
                $this->assertEquals($mailTask->getToName(), $email->getTo()[0]->getName());
                $this->assertEquals($mailTask->getSubject(), $email->getSubject());
                
                // 如果是 HTML 邮件
                if ($mailTask->isHtml()) {
                    $this->assertEquals($mailTask->getBody(), $email->getHtmlBody());
                } else {
                    $this->assertEquals($mailTask->getBody(), $email->getTextBody());
                }
                
                return true;
            }));
        
        // 调用测试方法 - 这将触发 createEmailFromTask
        $this->mailSenderService->sendMailTask($mailTask);
    }
    
    public function testCreateEmailFromTask_WithCcAndBcc(): void
    {
        // 准备一个带有抄送和密送的邮件任务
        $mailTask = $this->createMailTask();
        $mailTask->setCc(['cc1@example.com', 'cc2@example.com']);
        $mailTask->setBcc(['bcc1@example.com', 'bcc2@example.com']);
        
        // 设置 mailer 预期行为：捕获传递给 send 方法的 Email 对象
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) {
                $this->assertInstanceOf(Email::class, $email);
                
                // 验证抄送地址
                $ccAddresses = array_map(fn($cc) => $cc->getAddress(), $email->getCc());
                $this->assertCount(2, $ccAddresses);
                $this->assertContains('cc1@example.com', $ccAddresses);
                $this->assertContains('cc2@example.com', $ccAddresses);
                
                // 验证密送地址
                $bccAddresses = array_map(fn($bcc) => $bcc->getAddress(), $email->getBcc());
                $this->assertCount(2, $bccAddresses);
                $this->assertContains('bcc1@example.com', $bccAddresses);
                $this->assertContains('bcc2@example.com', $bccAddresses);
                
                return true;
            }));
        
        // 调用测试方法 - 这将触发 createEmailFromTask
        $this->mailSenderService->sendMailTask($mailTask);
    }
    
    public function testCreateEmailFromTask_WithoutNames(): void
    {
        // 准备一个没有名称的邮件任务
        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setSubject('Test Subject');
        $mailTask->setBody('Test Body');
        
        // 设置 mailer 预期行为：捕获传递给 send 方法的 Email 对象
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) {
                $this->assertInstanceOf(Email::class, $email);
                
                // 验证发件人和收件人邮箱，但不应该有名称
                $this->assertEquals('sender@example.com', $email->getFrom()[0]->getAddress());
                $this->assertEquals('', $email->getFrom()[0]->getName());
                $this->assertEquals('recipient@example.com', $email->getTo()[0]->getAddress());
                $this->assertEquals('', $email->getTo()[0]->getName());
                
                return true;
            }));
        
        // 调用测试方法 - 这将触发 createEmailFromTask
        $this->mailSenderService->sendMailTask($mailTask);
    }
    
    /**
     * 创建一个带有基本属性的邮件任务
     */
    private function createMailTask(): MailTask
    {
        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setFromName('Sender Name');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setToName('Recipient Name');
        $mailTask->setSubject('Test Subject');
        $mailTask->setBody('<p>Test Body</p>');
        $mailTask->setIsHtml(true);
        return $mailTask;
    }
} 