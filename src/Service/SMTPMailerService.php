<?php

namespace Tourze\SMTPMailerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;
use Tourze\SMTPMailerBundle\Exception\MailTaskIdNullException;
use Tourze\SMTPMailerBundle\Exception\MailTaskNotFoundException;
use Tourze\SMTPMailerBundle\Exception\SMTPConfigNotFoundException;
use Tourze\SMTPMailerBundle\Message\SendMailMessage;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;

/**
 * SMTP邮件服务
 */
#[WithMonologChannel(channel: 'smtp_mailer')]
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
        $email = $_ENV['SMTP_MAILER_DEFAULT_FROM_EMAIL'] ?? 'no-reply@example.com';

        return is_string($email) ? $email : 'no-reply@example.com';
    }

    /**
     * 发送邮件
     *
     * @param string $to      收件人
     * @param string $subject 邮件主题
     * @param string $body    邮件内容
     * @param array<string, mixed>  $options 额外选项
     *                        - from: 发件人邮箱
     *                        - fromName: 发件人姓名
     *                        - toName: 收件人姓名
     *                        - cc: 抄送
     *                        - bcc: 密送
     *                        - isHtml: 是否HTML内容
     *                        - attachments: 附件
     *                        - scheduledAt: 计划发送时间
     *                        - strategy: SMTP选择策略
     *                        - async: 是否异步发送
     *
     * @return int 邮件任务ID
     */
    public function send(string $to, string $subject, string $body, array $options = []): int
    {
        $mailTask = $this->createMailTask($to, $subject, $body, $options);
        $this->applyOptions($mailTask, $options);

        $this->entityManager->persist($mailTask);
        $this->entityManager->flush();

        $this->handleImmediateSending($mailTask, $options);

        $id = $mailTask->getId();

        return null !== $id ? $id : throw new MailTaskIdNullException();
    }

    /**
     * 使用指定的SMTP配置发送邮件
     *
     * @param int    $configId SMTP配置ID
     * @param string $to       收件人
     * @param string $subject  邮件主题
     * @param string $body     邮件内容
     * @param array<string, mixed>  $options  额外选项
     *
     * @return int 邮件任务ID
     */
    public function sendWithConfig(int $configId, string $to, string $subject, string $body, array $options = []): int
    {
        $smtpConfig = $this->getSmtpConfig($configId);
        $mailTask = $this->createMailTask($to, $subject, $body, $options);
        $mailTask->setSmtpConfig($smtpConfig);
        $this->applyOptions($mailTask, $options);

        $this->entityManager->persist($mailTask);
        $this->entityManager->flush();

        $this->handleImmediateSending($mailTask, $options);

        $id = $mailTask->getId();

        return null !== $id ? $id : throw new MailTaskIdNullException();
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

            $result = $this->executeSendingProcess($mailTask);

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
                'error' => $e->getMessage(),
            ]);

            $mailTask->markAsFailed($e->getMessage());
            $this->entityManager->flush();

            return false;
        }
    }

    /**
     * 创建邮件任务基础对象
     *
     * @param array<string, mixed> $options
     */
    private function createMailTask(string $to, string $subject, string $body, array $options): MailTask
    {
        $mailTask = new MailTask();
        $mailTask->setToEmail($to);
        $mailTask->setSubject($subject);
        $mailTask->setBody($body);

        $fromEmail = $options['from'] ?? $this->getDefaultFromEmail();
        $mailTask->setFromEmail(is_string($fromEmail) ? $fromEmail : $this->getDefaultFromEmail());

        return $mailTask;
    }

    /**
     * 获取SMTP配置
     */
    private function getSmtpConfig(int $configId): SMTPConfig
    {
        $smtpConfig = $this->smtpConfigRepository->find($configId);
        if (null === $smtpConfig) {
            throw new SMTPConfigNotFoundException($configId);
        }

        return $smtpConfig;
    }

    /**
     * 应用额外选项到邮件任务
     *
     * @param array<string, mixed> $options
     */
    private function applyOptions(MailTask $mailTask, array $options): void
    {
        $this->setRecipientOptions($mailTask, $options);
        $this->setContentOptions($mailTask, $options);
        $this->setSchedulingOptions($mailTask, $options);
    }

    /**
     * 设置收件人相关选项
     *
     * @param array<string, mixed> $options
     */
    private function setRecipientOptions(MailTask $mailTask, array $options): void
    {
        if (isset($options['fromName']) && is_string($options['fromName'])) {
            $mailTask->setFromName($options['fromName']);
        }

        if (isset($options['toName']) && is_string($options['toName'])) {
            $mailTask->setToName($options['toName']);
        }

        if (isset($options['cc'])) {
            $cc = is_array($options['cc']) ? $options['cc'] : [$options['cc']];
            $mailTask->setCc(array_filter($cc, 'is_string'));
        }

        if (isset($options['bcc'])) {
            $bcc = is_array($options['bcc']) ? $options['bcc'] : [$options['bcc']];
            $mailTask->setBcc(array_filter($bcc, 'is_string'));
        }
    }

    /**
     * 设置内容相关选项
     *
     * @param array<string, mixed> $options
     */
    private function setContentOptions(MailTask $mailTask, array $options): void
    {
        if (isset($options['isHtml'])) {
            $mailTask->setIsHtml((bool) $options['isHtml']);
        }

        if (isset($options['attachments']) && is_array($options['attachments'])) {
            $validatedAttachments = $this->validateAttachments($options['attachments']);
            $mailTask->setAttachments($validatedAttachments);
        }
    }

    /**
     * 验证并规范化附件数组
     *
     * @param array<mixed, mixed> $attachments
     * @return array<int, array<string, mixed>>|null
     */
    private function validateAttachments(array $attachments): ?array
    {
        if ([] === $attachments) {
            return null;
        }

        /** @var array<int, array<string, mixed>> $validated */
        $validated = [];
        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }

            /** @var array<string, mixed> $typedAttachment */
            $typedAttachment = $attachment;
            $validated[] = $typedAttachment;
        }

        return [] === $validated ? null : $validated;
    }

    /**
     * 设置计划发送相关选项
     *
     * @param array<string, mixed> $options
     */
    private function setSchedulingOptions(MailTask $mailTask, array $options): void
    {
        if (isset($options['scheduledAt']) && $options['scheduledAt'] instanceof \DateTimeInterface) {
            $scheduledAt = $options['scheduledAt'] instanceof \DateTime
                ? \DateTimeImmutable::createFromMutable($options['scheduledAt'])
                : $options['scheduledAt'];
            $mailTask->setScheduledTime($scheduledAt);
        }

        if (isset($options['strategy']) && is_string($options['strategy'])) {
            $mailTask->setSelectorStrategy($options['strategy']);
        }
    }

    /**
     * 处理立即发送逻辑
     *
     * @param array<string, mixed> $options
     */
    private function handleImmediateSending(MailTask $mailTask, array $options): void
    {
        $shouldSendNow = null === $mailTask->getScheduledTime();
        if (!$shouldSendNow) {
            return;
        }

        $async = $options['async'] ?? $this->isAsyncEnabled();
        if (true === $async) {
            $id = $mailTask->getId();
            if (null === $id) {
                throw new MailTaskIdNullException();
            }
            $this->messageBus->dispatch(new SendMailMessage($id));
        } else {
            $this->sendMailTaskNow($mailTask);
        }
    }

    /**
     * 执行发送过程
     */
    private function executeSendingProcess(MailTask $mailTask): bool
    {
        // 如果指定了SMTP配置，使用该配置发送
        if (null !== $mailTask->getSmtpConfig()) {
            return $this->mailSenderService->sendMailTaskWithConfig(
                $mailTask,
                $mailTask->getSmtpConfig()
            );
        }

        // 使用策略选择SMTP配置
        $strategy = $mailTask->getSelectorStrategy();
        $smtpConfig = $this->smtpSelectorService->selectConfig($strategy);

        if (null !== $smtpConfig) {
            return $this->mailSenderService->sendMailTaskWithConfig($mailTask, $smtpConfig);
        }

        // 如果没有可用的SMTP配置，使用默认的邮件发送方式
        return $this->mailSenderService->sendMailTask($mailTask);
    }

    /**
     * 重新发送失败的邮件
     */
    public function resendFailedMail(int $mailTaskId): bool
    {
        $mailTask = $this->mailTaskRepository->find($mailTaskId);

        if (null === $mailTask) {
            throw new MailTaskNotFoundException($mailTaskId);
        }

        // 重置状态
        $mailTask->setStatus(MailTaskStatus::PENDING);
        $this->entityManager->flush();

        // 异步发送
        if ($this->isAsyncEnabled()) {
            $id = $mailTask->getId();
            if (null === $id) {
                throw new MailTaskIdNullException();
            }
            $this->messageBus->dispatch(new SendMailMessage($id));

            return true;
        }

        // 同步发送
        return $this->sendMailTaskNow($mailTask);
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
                $this->processReadyTask($task);
                ++$count;
            }
        }

        return $count;
    }

    /**
     * 处理准备发送的任务
     */
    private function processReadyTask(MailTask $task): void
    {
        if ($this->isAsyncEnabled()) {
            $this->dispatchTaskAsync($task);
        } else {
            $this->sendMailTaskNow($task);
        }
    }

    /**
     * 异步派发任务
     */
    private function dispatchTaskAsync(MailTask $task): void
    {
        $id = $task->getId();
        if (null === $id) {
            throw new MailTaskIdNullException();
        }
        $this->messageBus->dispatch(new SendMailMessage($id));
    }
}
