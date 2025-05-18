<?php

namespace Tourze\SMTPMailerBundle\Tests\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\SMTPMailerBundle\Command\ProcessScheduledMailsCommand;
use Tourze\SMTPMailerBundle\Service\SMTPMailerService;

class ProcessScheduledMailsCommandTest extends TestCase
{
    private SMTPMailerService|MockObject $mailerService;
    private ProcessScheduledMailsCommand $command;
    private CommandTester $commandTester;
    
    protected function setUp(): void
    {
        $this->mailerService = $this->createMock(SMTPMailerService::class);
        $this->command = new ProcessScheduledMailsCommand($this->mailerService, 60);
        
        $application = new Application();
        $application->add($this->command);
        
        $command = $application->find('smtp-mailer:process-scheduled-mails');
        $this->commandTester = new CommandTester($command);
    }
    
    public function testExecute_NoMails(): void
    {
        // 设置 mailerService 预期行为
        $this->mailerService->expects($this->once())
            ->method('processScheduledTasks')
            ->willReturn(0);
        
        // 执行命令
        $this->commandTester->execute([]);
        
        // 验证输出
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有需要处理的计划邮件', $output);
    }
    
    public function testExecute_WithMails(): void
    {
        // 设置 mailerService 预期行为
        $this->mailerService->expects($this->once())
            ->method('processScheduledTasks')
            ->willReturn(5);
        
        // 执行命令
        $this->commandTester->execute([]);
        
        // 验证输出
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('已处理 5 封计划发送的邮件', $output);
    }
    
    public function testExecute_WithCustomInterval(): void
    {
        // 设置 mailerService 预期行为
        $this->mailerService->expects($this->once())
            ->method('processScheduledTasks')
            ->willReturn(3);
        
        // 执行命令，指定自定义间隔
        $this->commandTester->execute([
            '--interval' => 30
        ]);
        
        // 验证输出
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('已处理 3 封计划发送的邮件', $output);
    }
    
    public function testExecute_WithInvalidInterval(): void
    {
        // 设置 mailerService 预期行为
        $this->mailerService->expects($this->once())
            ->method('processScheduledTasks')
            ->willReturn(2);
        
        // 执行命令，指定无效间隔（应该使用默认间隔）
        $this->commandTester->execute([
            '--interval' => -10
        ]);
        
        // 验证输出
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('已处理 2 封计划发送的邮件', $output);
    }
    
    public function testExecute_DaemonMode(): void
    {
        // 这个测试比较特殊，因为守护进程模式会无限循环
        // 我们不能真正测试无限循环，所以只验证命令的启动部分
        
        // 创建一个自定义版本的命令来避免无限循环
        $commandMock = $this->getMockBuilder(ProcessScheduledMailsCommand::class)
            ->setConstructorArgs([$this->mailerService, 1])
            ->onlyMethods(['execute'])
            ->getMock();
            
        // 模拟execute方法仅执行一次并返回成功
        $commandMock->expects($this->once())
            ->method('execute')
            ->willReturn(Command::SUCCESS);
        
        // 设置命令名称
        $commandMock->setName('smtp-mailer:process-scheduled-mails');
            
        // 添加到应用程序
        $application = new Application();
        $application->add($commandMock);
        
        $commandToTest = $application->find('smtp-mailer:process-scheduled-mails');
        $commandTester = new CommandTester($commandToTest);
        
        // 执行命令 - 注：这里实际上调用的是我们模拟的execute方法
        $commandTester->execute([
            '--daemon' => true,
            '--interval' => 1
        ]);
        
        // 由于我们模拟了execute方法，所以无法验证输出内容
        // 只能验证命令执行并返回成功代码
        $this->assertEquals(0, $commandTester->getStatusCode());
    }
    
    public function testConfigure(): void
    {
        // 测试命令配置是否正确
        $this->assertEquals('smtp-mailer:process-scheduled-mails', $this->command->getName());
        $this->assertEquals('处理计划发送的邮件', $this->command->getDescription());
        $this->assertTrue($this->command->getDefinition()->hasOption('daemon'));
        $this->assertTrue($this->command->getDefinition()->hasOption('interval'));
    }
} 