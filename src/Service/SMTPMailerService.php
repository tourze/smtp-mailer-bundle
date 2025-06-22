<?php

namespace Tourze\SMTPMailerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;
use Tourze\SMTPMailerBundle\Message\SendMailMessage;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;

/**
 * SMTP邮件服务
 */
class SMTPMailerService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SMTPConfigRepository $smtpConfigRepository,
        private readonly MailTaskRepository $mailTaskRepository,
        private readonly SMTPSelectorService $smtpSelectorService,
        private readonly MailSenderService $mailSenderService,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 检查是否启用异步发送
     */
    private function isAsyncEnabled(): bool
    {
        return filter_var($_ENV['SMTP_MAILER_ASYNC_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * 获取默认发件人邮箱
     */
    private function getDefaultFromEmail(): string
    {
        return $_ENV['SMTP_MAILER_DEFAULT_FROM_EMAIL'] ?? 'no-reply@example.com';
    }

    /**
     * 发送邮件
     *
     * @param string $to 收件人
     * @param string $subject 邮件主题
     * @param string $body 邮件内容
     * @param array $options 额外选项
     *   - from: 发件人邮箱
     *   - fromName: 发件人姓名
     *   - toName: 收件人姓名
     *   - cc: 抄送
     *   - bcc: 密送
     *   - isHtml: 是否HTML内容
     *   - attachments: 附件
     *   - scheduledAt: 计划发送时间
     *   - strategy: SMTP选择策略
     *   - async: 是否异步发送
     * @return int 邮件任务ID
     */
    public function send(string $to, string $subject, string $body, array $options = []): int
    {
        // 创建邮件任务
        $mailTask = new MailTask();
        $mailTask->setToEmail($to);
        $mailTask->setSubject($subject);
        $mailTask->setBody($body);

        // 设置发件人
        $mailTask->setFromEmail($options['from'] ?? $this->getDefaultFromEmail());

        if (isset($options['fromName'])) {
            $mailTask->setFromName($options['fromName']);
        }

        // 设置收件人姓名
        if (isset($options['toName'])) {
            $mailTask->setToName($options['toName']);
        }

        // 设置抄送
        if (isset($options['cc'])) {
            $mailTask->setCc((array)$options['cc']);
        }

        // 设置密送
        if (isset($options['bcc'])) {
            $mailTask->setBcc((array)$options['bcc']);
        }

        // 设置内容类型
        if (isset($options['isHtml'])) {
            $mailTask->setIsHtml((bool)$options['isHtml']);
        }

        // 设置附件
        if (isset($options['attachments'])) {
            $mailTask->setAttachments($options['attachments']);
        }

        // 设置计划发送时间
        if (isset($options['scheduledAt']) && $options['scheduledAt'] instanceof \DateTimeInterface) {
            if ($options['scheduledAt'] instanceof \DateTime) {
                $scheduledAt = \DateTimeImmutable::createFromMutable($options['scheduledAt']);
            } else {
                $scheduledAt = $options['scheduledAt'];
            }
            $mailTask->setScheduledTime($scheduledAt);
        }

        // 设置选择策略
        if (isset($options['strategy'])) {
            $mailTask->setSelectorStrategy($options['strategy']);
        }

        // 保存任务
        $this->entityManager->persist($mailTask);
        $this->entityManager->flush();

        // 如果没有计划发送时间，立即发送
        $shouldSendNow = $mailTask->getScheduledTime() === null;

        // 检查是否异步发送
        $async = $options['async'] ?? $this->isAsyncEnabled();

        if ($shouldSendNow) {
            if ($async) {
                // 异步发送
                $this->messageBus->dispatch(new SendMailMessage($mailTask->getId()));
            } else {
                // 同步发送
                $this->sendMailTaskNow($mailTask);
            }
        }

        return $mailTask->getId();
    }

    /**
     * 使用指定的SMTP配置发送邮件
     *
     * @param int $configId SMTP配置ID
     * @param string $to 收件人
     * @param string $subject 邮件主题
     * @param string $body 邮件内容
     * @param array $options 额外选项
     * @return int 邮件任务ID
     */
    public function sendWithConfig(int $configId, string $to, string $subject, string $body, array $options = []): int
    {
        // 获取SMTP配置
        $smtpConfig = $this->smtpConfigRepository->find($configId);

        if (null === $smtpConfig) {
            throw new \InvalidArgumentException('SMTP配置不存在: ' . $configId);
        }

        // 创建邮件任务
        $mailTask = new MailTask();
        $mailTask->setToEmail($to);
        $mailTask->setSubject($subject);
        $mailTask->setBody($body);
        $mailTask->setSmtpConfig($smtpConfig);

        // 设置发件人
        $mailTask->setFromEmail($options['from'] ?? $this->getDefaultFromEmail());

        if (isset($options['fromName'])) {
            $mailTask->setFromName($options['fromName']);
        }

        // 设置收件人姓名
        if (isset($options['toName'])) {
            $mailTask->setToName($options['toName']);
        }

        // 设置抄送
        if (isset($options['cc'])) {
            $mailTask->setCc((array)$options['cc']);
        }

        // 设置密送
        if (isset($options['bcc'])) {
            $mailTask->setBcc((array)$options['bcc']);
        }

        // 设置内容类型
        if (isset($options['isHtml'])) {
            $mailTask->setIsHtml((bool)$options['isHtml']);
        }

        // 设置附件
        if (isset($options['attachments'])) {
            $mailTask->setAttachments($options['attachments']);
        }

        // 设置计划发送时间
        if (isset($options['scheduledAt']) && $options['scheduledAt'] instanceof \DateTimeInterface) {
            if ($options['scheduledAt'] instanceof \DateTime) {
                $scheduledAt = \DateTimeImmutable::createFromMutable($options['scheduledAt']);
            } else {
                $scheduledAt = $options['scheduledAt'];
            }
            $mailTask->setScheduledTime($scheduledAt);
        }

        // 保存任务
        $this->entityManager->persist($mailTask);
        $this->entityManager->flush();

        // 如果没有计划发送时间，立即发送
        $shouldSendNow = $mailTask->getScheduledTime() === null;

        // 检查是否异步发送
        $async = $options['async'] ?? $this->isAsyncEnabled();

        if ($shouldSendNow) {
            if ($async) {
                // 异步发送
                $this->messageBus->dispatch(new SendMailMessage($mailTask->getId()));
            } else {
                // 同步发送
                $this->sendMailTaskNow($mailTask);
            }
        }

        return $mailTask->getId();
    }

    /**
     * 立即发送邮件任务
     */
    public function sendMailTaskNow(MailTask $mailTask): bool
    {
        try {
            // 标记为处理中
            $mailTask->markAsProcessing();
            $this->entityManager->flush();

            $result = false;

            // 如果指定了SMTP配置，使用该配置发送
            if (null !== $mailTask->getSmtpConfig()) {
                $result = $this->mailSenderService->sendMailTaskWithConfig(
                    $mailTask,
                    $mailTask->getSmtpConfig()
                );
            } else {
                // 使用策略选择SMTP配置
                $strategy = $mailTask->getSelectorStrategy();
                $smtpConfig = $this->smtpSelectorService->selectConfig($strategy);

                if (null !== $smtpConfig) {
                    $result = $this->mailSenderService->sendMailTaskWithConfig($mailTask, $smtpConfig);
                } else {
                    // 如果没有可用的SMTP配置，使用默认的邮件发送方式
                    $result = $this->mailSenderService->sendMailTask($mailTask);
                }
            }

            // 更新任务状态
            if ($result) {
                $mailTask->markAsSent();
            } else {
                $mailTask->markAsFailed('邮件发送失败');
            }

            $this->entityManager->flush();

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('邮件发送异常', [
                'id' => $mailTask->getId(),
                'error' => $e->getMessage()
            ]);

            $mailTask->markAsFailed($e->getMessage());
            $this->entityManager->flush();

            return false;
        }
    }

    /**
     * 重新发送失败的邮件
     */
    public function resendFailedMail(int $mailTaskId): bool
    {
        $mailTask = $this->mailTaskRepository->find($mailTaskId);

        if (null === $mailTask) {
            throw new \InvalidArgumentException('邮件任务不存在: ' . $mailTaskId);
        }

        // 重置状态
        $mailTask->setStatus(MailTaskStatus::PENDING);
        $this->entityManager->flush();

        // 异步发送
        if ($this->isAsyncEnabled()) {
            $this->messageBus->dispatch(new SendMailMessage($mailTask->getId()));
            return true;
        } else {
            // 同步发送
            return $this->sendMailTaskNow($mailTask);
        }
    }

    /**
     * 处理计划任务
     */
    public function processScheduledTasks(): int
    {
        $scheduledTasks = $this->mailTaskRepository->findScheduledTasks();
        $count = 0;

        foreach ($scheduledTasks as $task) {
            if ($task->isReadyToSend()) {
                if ($this->isAsyncEnabled()) {
                    // 异步发送
                    $this->messageBus->dispatch(new SendMailMessage($task->getId()));
                } else {
                    // 同步发送
                    $this->sendMailTaskNow($task);
                }
                $count++;
            }
        }

        return $count;
    }
}
