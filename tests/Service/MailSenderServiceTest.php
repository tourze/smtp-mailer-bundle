<?php

namespace Tourze\SMTPMailerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Service\MailSenderService;

/**
 * @internal
 */
#[CoversClass(MailSenderService::class)]
#[RunTestsInSeparateProcesses]
final class MailSenderServiceTest extends AbstractIntegrationTestCase
{
    private MailerInterface|MockObject $mailer;

    private LoggerInterface|MockObject $logger;

    private MailSenderService $mailSenderService;

    protected function onSetUp(): void
    {
        $this->setUpService();
    }

    private function setUpService(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 将 Mock 对象注入到容器中
        self::getContainer()->set(MailerInterface::class, $this->mailer);
        self::getContainer()->set('monolog.logger.smtp_mailer', $this->logger);

        // 从容器获取服务实例
        $this->mailSenderService = self::getService(MailSenderService::class);
    }

    public function testSendMailTaskSuccess(): void
    {
        // 准备一个邮件任务
        $mailTask = $this->createMailTask();

        // 设置 mailer mock 预期行为：成功发送邮件
        $this->mailer->expects($this->once())
            ->method('send')
        ;

        // 调用测试方法
        $result = $this->mailSenderService->sendMailTask($mailTask);

        // 断言结果为成功
        $this->assertTrue($result);
    }

    public function testSendMailTaskThrowsTransportException(): void
    {
        // 准备一个邮件任务
        $mailTask = $this->createMailTask();

        // 创建一个 TransportException 的 mock
        $exception = $this->createMock(TransportExceptionInterface::class);

        // 设置 mailer mock 预期行为：抛出异常
        $this->mailer->expects($this->once())
            ->method('send')
            ->willThrowException($exception)
        ;

        // 设置 logger 预期行为：记录错误
        $this->logger->expects($this->once())
            ->method('error')
            ->with('邮件发送失败', self::anything())
        ;

        // 调用测试方法
        $result = $this->mailSenderService->sendMailTask($mailTask);

        // 断言结果为失败
        $this->assertFalse($result);
    }

    public function testSendMailTaskWithConfigHandlesConnectionFailure(): void
    {
        // 准备一个邮件任务
        $mailTask = $this->createMailTask();

        // 准备一个 SMTP 配置
        $smtpConfig = new SMTPConfig();
        $smtpConfig->setHost('127.0.0.1'); // 使用本地地址避免真实网络连接
        $smtpConfig->setPort(2525); // 使用不常用的端口避免连接到真实服务
        $smtpConfig->setUsername('user');
        $smtpConfig->setPassword('pass');
        $smtpConfig->setEncryption('tls');

        // 设置 logger 预期行为：记录错误（因为SMTP服务器不可用）
        $this->logger->expects($this->once())
            ->method('error')
            ->with('邮件发送失败', self::callback(function ($context) {
                // 验证错误日志包含正确的上下文信息
                return is_array($context)
                    && isset($context['error'])
                    && array_key_exists('task_id', $context)
                    && array_key_exists('smtp_config_id', $context);
            }))
        ;

        // 调用测试方法 - 预期会失败但不会抛出异常
        $result = $this->mailSenderService->sendMailTaskWithConfig($mailTask, $smtpConfig);

        // 断言结果为失败（因为SMTP服务器不可用）
        $this->assertFalse($result);
    }

    public function testCreateEmailFromTaskBasicProperties(): void
    {
        // 由于 createEmailFromTask 是私有方法，我们需要通过公共方法间接测试
        // 我们将通过 sendMailTask 验证 Email 对象的创建

        // 准备一个简单的邮件任务
        $mailTask = $this->createMailTask();

        // 设置 mailer 预期行为：捕获传递给 send 方法的 Email 对象
        $this->mailer->expects($this->once())
            ->method('send')
            ->with(self::callback(function ($email) use ($mailTask) {
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
            }))
        ;

        // 调用测试方法 - 这将触发 createEmailFromTask
        $this->mailSenderService->sendMailTask($mailTask);
    }

    public function testCreateEmailFromTaskWithCcAndBcc(): void
    {
        // 准备一个带有抄送和密送的邮件任务
        $mailTask = $this->createMailTask();
        $mailTask->setCc(['cc1@example.com', 'cc2@example.com']);
        $mailTask->setBcc(['bcc1@example.com', 'bcc2@example.com']);

        // 设置 mailer 预期行为：捕获传递给 send 方法的 Email 对象
        $this->mailer->expects($this->once())
            ->method('send')
            ->with(self::callback(function ($email) {
                $this->assertInstanceOf(Email::class, $email);

                // 验证抄送地址
                $ccAddresses = array_map(fn ($cc) => $cc->getAddress(), $email->getCc());
                $this->assertCount(2, $ccAddresses);
                $this->assertContains('cc1@example.com', $ccAddresses);
                $this->assertContains('cc2@example.com', $ccAddresses);

                // 验证密送地址
                $bccAddresses = array_map(fn ($bcc) => $bcc->getAddress(), $email->getBcc());
                $this->assertCount(2, $bccAddresses);
                $this->assertContains('bcc1@example.com', $bccAddresses);
                $this->assertContains('bcc2@example.com', $bccAddresses);

                return true;
            }))
        ;

        // 调用测试方法 - 这将触发 createEmailFromTask
        $this->mailSenderService->sendMailTask($mailTask);
    }

    public function testCreateEmailFromTaskWithoutNames(): void
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
            ->with(self::callback(function ($email) {
                $this->assertInstanceOf(Email::class, $email);

                // 验证发件人和收件人邮箱，但不应该有名称
                $this->assertEquals('sender@example.com', $email->getFrom()[0]->getAddress());
                $this->assertEquals('', $email->getFrom()[0]->getName());
                $this->assertEquals('recipient@example.com', $email->getTo()[0]->getAddress());
                $this->assertEquals('', $email->getTo()[0]->getName());

                return true;
            }))
        ;

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

    public function testAddSingleAttachmentWithValidBase64Data(): void
    {
        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setSubject('Test with attachment');
        $mailTask->setBody('Test body');
        $mailTask->setIsHtml(false);

        // 添加有效的base64编码附件
        $testData = 'Hello, World!';
        $base64Data = base64_encode($testData);
        $attachment = [
            'data' => $base64Data,
            'name' => 'test.txt',
            'mime' => 'text/plain',
        ];
        $mailTask->setAttachments([$attachment]);

        // 设置 mailer 预期行为
        $this->mailer->expects($this->once())
            ->method('send')
            ->with(self::callback(function ($email) use ($testData) {
                $this->assertInstanceOf(Email::class, $email);

                // 验证附件内容
                $attachments = $email->getAttachments();
                $this->assertCount(1, $attachments);
                $this->assertEquals('test.txt', $attachments[0]->getFilename());
                $this->assertEquals('text/plain', $attachments[0]->getContentType());
                $this->assertEquals($testData, $attachments[0]->getBody());

                return true;
            }))
        ;

        $this->mailSenderService->sendMailTask($mailTask);
    }

    public function testAddSingleAttachmentWithInvalidBase64Data(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64 data in attachment');

        $mailTask = new MailTask();
        $mailTask->setFromEmail('sender@example.com');
        $mailTask->setToEmail('recipient@example.com');
        $mailTask->setSubject('Test with invalid attachment');
        $mailTask->setBody('Test body');
        $mailTask->setIsHtml(false);

        // 添加无效的base64数据
        $invalidBase64 = 'This is not valid base64 data!@#$%';
        $attachment = [
            'data' => $invalidBase64,
            'name' => 'test.txt',
            'mime' => 'text/plain',
        ];
        $mailTask->setAttachments([$attachment]);

        $this->mailSenderService->sendMailTask($mailTask);
    }
}
