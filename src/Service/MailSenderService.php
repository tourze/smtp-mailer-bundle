<?php

namespace Tourze\SMTPMailerBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * 邮件发送服务
 */
class MailSenderService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 发送邮件任务
     */
    public function sendMailTask(MailTask $mailTask): bool
    {
        try {
            $email = $this->createEmailFromTask($mailTask);
            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('邮件发送失败', [
                'error' => $e->getMessage(),
                'task_id' => $mailTask->getId(),
            ]);
            return false;
        }
    }

    /**
     * 使用指定的SMTP配置发送邮件任务
     */
    public function sendMailTaskWithConfig(MailTask $mailTask, SMTPConfig $smtpConfig): bool
    {
        try {
            $email = $this->createEmailFromTask($mailTask);

            // 创建临时 Transport
            $dsn = $smtpConfig->getDsn();
            $transport = Transport::fromDsn($dsn);

            // 使用临时 Transport 发送邮件
            $transport->send($email);

            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('邮件发送失败', [
                'error' => $e->getMessage(),
                'task_id' => $mailTask->getId(),
                'smtp_config_id' => $smtpConfig->getId(),
            ]);
            return false;
        }
    }

    /**
     * 从任务创建Email对象
     */
    private function createEmailFromTask(MailTask $mailTask): Email
    {
        $email = new Email();

        // 设置发件人
        if (null !== $mailTask->getFromName() && '' !== $mailTask->getFromName()) {
            $email->from(new Address($mailTask->getFromEmail(), $mailTask->getFromName()));
        } else {
            $email->from($mailTask->getFromEmail());
        }

        // 设置收件人
        if (null !== $mailTask->getToName() && '' !== $mailTask->getToName()) {
            $email->to(new Address($mailTask->getToEmail(), $mailTask->getToName()));
        } else {
            $email->to($mailTask->getToEmail());
        }

        // 设置抄送
        if (null !== $mailTask->getCc() && [] !== $mailTask->getCc()) {
            foreach ($mailTask->getCc() as $cc) {
                $email->addCc($cc);
            }
        }

        // 设置密送
        if (null !== $mailTask->getBcc() && [] !== $mailTask->getBcc()) {
            foreach ($mailTask->getBcc() as $bcc) {
                $email->addBcc($bcc);
            }
        }

        // 设置主题
        $email->subject($mailTask->getSubject());

        // 设置内容
        if ($mailTask->isHtml()) {
            $email->html($mailTask->getBody());
        } else {
            $email->text($mailTask->getBody());
        }

        // 添加附件
        if (null !== $mailTask->getAttachments() && [] !== $mailTask->getAttachments()) {
            foreach ($mailTask->getAttachments() as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    $email->attachFromPath(
                        $attachment['path'],
                        $attachment['name'] ?? null,
                        $attachment['mime'] ?? null
                    );
                } elseif (isset($attachment['data'])) {
                    $email->attach(
                        base64_decode($attachment['data']),
                        $attachment['name'] ?? 'attachment',
                        $attachment['mime'] ?? 'application/octet-stream'
                    );
                }
            }
        }

        return $email;
    }
} 