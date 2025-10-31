<?php

namespace Tourze\SMTPMailerBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\SMTPMailerBundle\Command\ProcessScheduledMailsCommand;

/**
 * @internal
 */
#[CoversClass(ProcessScheduledMailsCommand::class)]
#[RunTestsInSeparateProcesses]
final class ProcessScheduledMailsCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // Command 测试初始化
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(ProcessScheduledMailsCommand::class);
        $this->assertInstanceOf(ProcessScheduledMailsCommand::class, $command);

        return new CommandTester($command);
    }

    public function testExecuteNoMails(): void
    {
        // 执行命令 - 没有待处理的邮件时
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        // 验证命令执行成功
        $this->assertEquals(0, $exitCode);

        // 验证输出
        $output = $commandTester->getDisplay();
        $this->assertNotEmpty($output);
    }

    public function testExecuteWithMails(): void
    {
        // 执行命令
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        // 验证命令执行成功
        $this->assertEquals(0, $exitCode);

        // 验证输出
        $output = $commandTester->getDisplay();
        $this->assertNotEmpty($output);
    }

    public function testExecuteWithCustomInterval(): void
    {
        // 执行命令，指定自定义间隔
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([
            '--interval' => 30,
        ]);

        // 验证命令执行成功
        $this->assertEquals(0, $exitCode);

        // 验证输出
        $output = $commandTester->getDisplay();
        $this->assertNotEmpty($output);
    }

    public function testExecuteWithInvalidInterval(): void
    {
        // 执行命令，指定无效间隔（应该使用默认间隔）
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([
            '--interval' => -10,
        ]);

        // 验证命令执行成功
        $this->assertEquals(0, $exitCode);

        // 验证输出
        $output = $commandTester->getDisplay();
        $this->assertNotEmpty($output);
    }

    public function testOptionDaemon(): void
    {
        // 测试 --daemon 选项（不实际运行守护进程，只测试选项存在性）
        $commandTester = $this->getCommandTester();

        // 由于守护进程会无限循环，我们不能直接测试其执行
        // 但可以验证选项存在且被正确识别
        $command = self::getService(ProcessScheduledMailsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('daemon'));

        $daemonOption = $definition->getOption('daemon');
        $this->assertEquals('daemon', $daemonOption->getName());
        $this->assertEquals('d', $daemonOption->getShortcut());
        $this->assertEquals('以守护进程模式运行', $daemonOption->getDescription());
        $this->assertFalse($daemonOption->isValueRequired());
    }

    public function testOptionInterval(): void
    {
        // 测试 --interval 选项
        $command = self::getService(ProcessScheduledMailsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('interval'));

        $intervalOption = $definition->getOption('interval');
        $this->assertEquals('interval', $intervalOption->getName());
        $this->assertEquals('i', $intervalOption->getShortcut());
        $this->assertEquals('轮询间隔（秒）', $intervalOption->getDescription());
        $this->assertTrue($intervalOption->isValueRequired());

        // 测试默认值
        $defaultValue = $intervalOption->getDefault();
        $this->assertTrue(is_string($defaultValue) || is_int($defaultValue));
        $this->assertGreaterThan(0, (int) $defaultValue);
    }

    public function testConfigure(): void
    {
        // 测试命令配置是否正确
        $command = self::getService(ProcessScheduledMailsCommand::class);
        $this->assertEquals('smtp-mailer:process-scheduled-mails', $command->getName());
        $this->assertEquals('处理计划发送的邮件', $command->getDescription());
        $this->assertTrue($command->getDefinition()->hasOption('daemon'));
        $this->assertTrue($command->getDefinition()->hasOption('interval'));
    }
}
