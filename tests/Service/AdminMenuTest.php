<?php

declare(strict_types=1);

namespace Tourze\SMTPMailerBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\SMTPMailerBundle\Service\AdminMenu;

/**
 * AdminMenu服务测试
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    private LinkGeneratorInterface $linkGenerator;

    protected function onSetUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    public function testServiceIsCallable(): void
    {
        self::assertIsCallable($this->adminMenu);
    }

    public function testInvokeAddsMenuItems(): void
    {
        $mainItem = $this->createMock(ItemInterface::class);
        $mailMenu = $this->createMock(ItemInterface::class);
        $smtpConfigItem = $this->createMock(ItemInterface::class);
        $mailTaskItem = $this->createMock(ItemInterface::class);

        /** @var LinkGeneratorInterface&\PHPUnit\Framework\MockObject\MockObject $linkGenerator */
        $linkGenerator = $this->linkGenerator;

        // 模拟LinkGenerator行为
        $linkGenerator->expects($this->exactly(2))
            ->method('getCurdListPage')
            ->willReturnMap([
                ['Tourze\SMTPMailerBundle\Entity\SMTPConfig', '/admin/smtpconfig/list'],
                ['Tourze\SMTPMailerBundle\Entity\MailTask', '/admin/mailtask/list'],
            ])
        ;

        // 第一次调用getChild返回null，第二次返回已创建的菜单项
        $mainItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('邮件管理')
            ->willReturnOnConsecutiveCalls(null, $mailMenu)
        ;

        // 创建邮件管理父菜单
        $mainItem->expects($this->once())
            ->method('addChild')
            ->with('邮件管理')
            ->willReturn($mailMenu)
        ;

        // 添加两个子菜单
        $mailMenu->expects($this->exactly(2))
            ->method('addChild')
            ->with(self::logicalOr('SMTP配置', '邮件任务'))
            ->willReturnOnConsecutiveCalls($smtpConfigItem, $mailTaskItem)
        ;

        // 设置SMTP配置菜单的URI和图标
        $smtpConfigItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/smtpconfig/list')
            ->willReturn($smtpConfigItem)
        ;

        $smtpConfigItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-server')
            ->willReturn($smtpConfigItem)
        ;

        // 设置邮件任务菜单的URI和图标
        $mailTaskItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/mailtask/list')
            ->willReturn($mailTaskItem)
        ;

        $mailTaskItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-tasks')
            ->willReturn($mailTaskItem)
        ;

        // 设置邮件管理菜单的图标
        $mailMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-envelope')
            ->willReturn($mailMenu)
        ;

        $this->adminMenu->__invoke($mainItem);
    }
}
