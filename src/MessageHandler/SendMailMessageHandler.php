<?php

namespace Tourze\SMTPMailerBundle\MessageHandler;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Tourze\SMTPMailerBundle\Message\SendMailMessage;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;
use Tourze\SMTPMailerBundle\Service\MailSenderService;

/**
 * 处理异步邮件发送消息
 */
#[AsMessageHandler]
class SendMailMessageHandler
{
    public function __construct(
        private readonly MailTaskRepository $mailTaskRepository,
        private readonly MailSenderService $mailSenderService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 处理消息
     */
    public function __invoke(SendMailMessage $message): void
    {
        $mailTaskId = $message->getMailTaskId();
        $mailTask = $this->mailTaskRepository->find($mailTaskId);

        if ($mailTask === null) {
            $this->logger->error('邮件任务不存在', ['id' => $mailTaskId]);
            throw new UnrecoverableMessageHandlingException('邮件任务不存在: ' . $mailTaskId);
        }

        // 只处理待处理状态的任务
        if (!$mailTask->isReadyToSend()) {
            $this->logger->info('邮件任务不处于可发送状态', [
                'id' => $mailTaskId,
                'status' => $mailTask->getStatus()
            ]);
            return;
        }

        // 标记为处理中
        $mailTask->markAsProcessing();
        $this->mailTaskRepository->getEntityManager()->flush();

        try {
            $result = $this->mailSenderService->sendMailTask($mailTask);

            if ($result) {
                $mailTask->markAsSent();
            } else {
                $mailTask->markAsFailed('邮件发送失败');
            }
        } catch (\Exception $e) {
            $this->logger->error('邮件发送异常', [
                'id' => $mailTaskId,
                'error' => $e->getMessage()
            ]);

            $mailTask->markAsFailed($e->getMessage());
        }

        $this->mailTaskRepository->getEntityManager()->flush();
    }
}
