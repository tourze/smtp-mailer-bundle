<?php

namespace Tourze\SMTPMailerBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\SMTPMailerBundle\Service\SMTPMailerService;

/**
 * 处理计划发送的邮件任务
 */
#[AsCommand(
    name: self::NAME,
    description: '处理计划发送的邮件',
)]
class ProcessScheduledMailsCommand extends Command
{
    public const NAME = 'smtp-mailer:process-scheduled-mails';
    public function __construct(
        private readonly SMTPMailerService $mailerService,
    ) {
        parent::__construct();
    }

    /**
     * 获取处理间隔
     */
    private function getProcessInterval(): int
    {
        return (int) ($_ENV['SMTP_MAILER_PROCESS_INTERVAL'] ?? 60);
    }

    protected function configure(): void
    {
        $this
            ->addOption('daemon', 'd', InputOption::VALUE_NONE, '以守护进程模式运行')
            ->addOption('interval', 'i', InputOption::VALUE_REQUIRED, '轮询间隔（秒）', $this->getProcessInterval());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $daemon = $input->getOption('daemon');
        $interval = (int) $input->getOption('interval');

        if ($interval < 1) {
            $interval = $this->getProcessInterval();
        }

        if ((bool) $daemon) {
            $io->info('以守护进程模式启动，轮询间隔: ' . $interval . '秒');

            // @phpstan-ignore-next-line
            while (true) {
                $this->processScheduledMails($io);
                sleep($interval);
            }
        }
        
        return $this->processScheduledMails($io);
    }

    /**
     * 处理计划发送的邮件
     */
    private function processScheduledMails(SymfonyStyle $io): int
    {
        $count = $this->mailerService->processScheduledTasks();

        if ($count > 0) {
            $io->success(sprintf('已处理 %d 封计划发送的邮件', $count));
        } else {
            $io->info('没有需要处理的计划邮件');
        }

        return Command::SUCCESS;
    }
}
