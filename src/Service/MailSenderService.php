<?php

namespace Tourze\SMTPMailerBundle\Service;

use Monolog\Attribute\WithMonologChannel;
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
#[WithMonologChannel(channel: 'smtp_mailer')]
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

        $this->setFromAddress($email, $mailTask);
        $this->setToAddress($email, $mailTask);
        $this->setCcRecipients($email, $mailTask);
        $this->setBccRecipients($email, $mailTask);
        $this->setSubjectAndBody($email, $mailTask);
        $this->addAttachments($email, $mailTask);

        return $email;
    }

    /**
     * 设置发件人地址
     */
    private function setFromAddress(Email $email, MailTask $mailTask): void
    {
        $fromName = $mailTask->getFromName();
        if (null !== $fromName && '' !== $fromName) {
            $email->from(new Address($mailTask->getFromEmail(), $fromName));
        } else {
            $email->from($mailTask->getFromEmail());
        }
    }

    /**
     * 设置收件人地址
     */
    private function setToAddress(Email $email, MailTask $mailTask): void
    {
        $toName = $mailTask->getToName();
        if (null !== $toName && '' !== $toName) {
            $email->to(new Address($mailTask->getToEmail(), $toName));
        } else {
            $email->to($mailTask->getToEmail());
        }
    }

    /**
     * 设置抄送收件人
     */
    private function setCcRecipients(Email $email, MailTask $mailTask): void
    {
        $ccList = $mailTask->getCc();
        if (null === $ccList || [] === $ccList) {
            return;
        }

        foreach ($ccList as $cc) {
            $email->addCc($cc);
        }
    }

    /**
     * 设置密送收件人
     */
    private function setBccRecipients(Email $email, MailTask $mailTask): void
    {
        $bccList = $mailTask->getBcc();
        if (null === $bccList || [] === $bccList) {
            return;
        }

        foreach ($bccList as $bcc) {
            $email->addBcc($bcc);
        }
    }

    /**
     * 设置主题和内容
     */
    private function setSubjectAndBody(Email $email, MailTask $mailTask): void
    {
        $email->subject($mailTask->getSubject());

        if ($mailTask->isHtml()) {
            $email->html($mailTask->getBody());
        } else {
            $email->text($mailTask->getBody());
        }
    }

    /**
     * 添加附件
     */
    private function addAttachments(Email $email, MailTask $mailTask): void
    {
        $attachments = $mailTask->getAttachments();
        if (null === $attachments || [] === $attachments) {
            return;
        }

        foreach ($attachments as $attachment) {
            $this->addSingleAttachment($email, $attachment);
        }
    }

    /**
     * 添加单个附件
     *
     * @param array<string, mixed> $attachment
     */
    private function addSingleAttachment(Email $email, array $attachment): void
    {
        if ($this->hasValidPathAttachment($attachment)) {
            $this->attachFromPath($email, $attachment);

            return;
        }

        if ($this->hasValidDataAttachment($attachment)) {
            $this->attachFromData($email, $attachment);
        }
    }

    /**
     * 检查是否有有效的路径附件
     *
     * @param array<string, mixed> $attachment
     */
    private function hasValidPathAttachment(array $attachment): bool
    {
        return isset($attachment['path'])
            && is_string($attachment['path'])
            && file_exists($attachment['path']);
    }

    /**
     * 检查是否有有效的数据附件
     *
     * @param array<string, mixed> $attachment
     */
    private function hasValidDataAttachment(array $attachment): bool
    {
        return isset($attachment['data']) && is_string($attachment['data']);
    }

    /**
     * 从路径添加附件
     *
     * @param array<string, mixed> $attachment
     */
    private function attachFromPath(Email $email, array $attachment): void
    {
        $path = is_string($attachment['path']) ? $attachment['path'] : '';
        $name = $this->extractStringValue($attachment, 'name', null);
        $mime = $this->extractStringValue($attachment, 'mime', null);

        $email->attachFromPath($path, $name, $mime);
    }

    /**
     * 从数据添加附件
     *
     * @param array<string, mixed> $attachment
     */
    private function attachFromData(Email $email, array $attachment): void
    {
        $data = is_string($attachment['data']) ? $attachment['data'] : '';
        $decodedData = base64_decode($data, true);
        if (false === $decodedData) {
            throw new \InvalidArgumentException('Invalid base64 data in attachment');
        }

        $name = $this->extractStringValue($attachment, 'name', 'attachment');
        $mime = $this->extractStringValue($attachment, 'mime', 'application/octet-stream');

        $email->attach($decodedData, $name, $mime);
    }

    /**
     * 提取字符串值
     *
     * @param array<string, mixed> $data
     */
    private function extractStringValue(array $data, string $key, ?string $default): ?string
    {
        if (!isset($data[$key])) {
            return $default;
        }

        return is_string($data[$key]) ? $data[$key] : $default;
    }
}
