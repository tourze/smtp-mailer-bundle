<?php

namespace Tourze\SMTPMailerBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 邮件任务状态枚举
 */
enum MailTaskStatus: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SENT = 'sent';
    case FAILED = 'failed';

    public const PRIMARY = 'primary';
    public const INFO = 'info';
    public const SUCCESS = 'success';
    public const DARK = 'dark';

    /**
     * 获取状态标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '等待发送',
            self::PROCESSING => '发送中',
            self::SENT => '已发送',
            self::FAILED => '发送失败',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::PENDING => self::PRIMARY,
            self::PROCESSING => self::INFO,
            self::SENT => self::SUCCESS,
            self::FAILED => self::DARK,
        };
    }
}
