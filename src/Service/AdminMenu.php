<?php

declare(strict_types=1);

namespace Tourze\SMTPMailerBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * SMTP邮件管理后台菜单提供者
 */
#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('邮件管理')) {
            $item->addChild('邮件管理')
                ->setAttribute('icon', 'fas fa-envelope')
            ;
        }

        $mailMenu = $item->getChild('邮件管理');
        if (null === $mailMenu) {
            return;
        }

        $mailMenu->addChild('SMTP配置')
            ->setUri($this->linkGenerator->getCurdListPage(SMTPConfig::class))
            ->setAttribute('icon', 'fas fa-server')
        ;

        $mailMenu->addChild('邮件任务')
            ->setUri($this->linkGenerator->getCurdListPage(MailTask::class))
            ->setAttribute('icon', 'fas fa-tasks')
        ;
    }
}
