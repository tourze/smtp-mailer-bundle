<?php

namespace Tourze\SMTPMailerBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;

/**
 * @internal
 */
#[CoversClass(MailTaskStatus::class)]
final class MailTaskStatusTest extends AbstractEnumTestCase
{
    /**
     * 测试所有枚举值是否定义正确
     */
    public function testAllCasesExist(): void
    {
        $expectedCases = ['PENDING', 'PROCESSING', 'SENT', 'FAILED'];
        $actualCases = array_map(fn ($case) => $case->name, MailTaskStatus::cases());

        $this->assertCount(4, MailTaskStatus::cases());
        $this->assertEquals($expectedCases, $actualCases);
    }

    /**
     * 测试枚举值对应的字符串值
     */
    public function testEnumValues(): void
    {
        $this->assertSame('pending', MailTaskStatus::PENDING->value);
        $this->assertSame('processing', MailTaskStatus::PROCESSING->value);
        $this->assertSame('sent', MailTaskStatus::SENT->value);
        $this->assertSame('failed', MailTaskStatus::FAILED->value);
    }

    /**
     * 测试 getLabel 方法返回正确的中文标签
     */
    public function testGetLabel(): void
    {
        $this->assertSame('等待发送', MailTaskStatus::PENDING->getLabel());
        $this->assertSame('发送中', MailTaskStatus::PROCESSING->getLabel());
        $this->assertSame('已发送', MailTaskStatus::SENT->getLabel());
        $this->assertSame('发送失败', MailTaskStatus::FAILED->getLabel());
    }

    /**
     * 测试 getBadge 方法返回正确的徽章样式
     */
    public function testGetBadge(): void
    {
        $this->assertSame(MailTaskStatus::PRIMARY, MailTaskStatus::PENDING->getBadge());
        $this->assertSame(MailTaskStatus::INFO, MailTaskStatus::PROCESSING->getBadge());
        $this->assertSame(MailTaskStatus::SUCCESS, MailTaskStatus::SENT->getBadge());
        $this->assertSame(MailTaskStatus::DARK, MailTaskStatus::FAILED->getBadge());
    }

    /**
     * 测试徽章常量值是否正确
     */
    public function testBadgeConstants(): void
    {
        $this->assertSame('primary', MailTaskStatus::PRIMARY);
        $this->assertSame('info', MailTaskStatus::INFO);
        $this->assertSame('success', MailTaskStatus::SUCCESS);
        $this->assertSame('dark', MailTaskStatus::DARK);
    }

    /**
     * 使用数据提供器测试状态、标签和徽章的对应关系
     */
    #[DataProvider('provideStatusLabelData')]
    public function testStatusLabelAndBadgeMapping(MailTaskStatus $status, string $expectedLabel, string $expectedBadge): void
    {
        $this->assertSame($expectedLabel, $status->getLabel());
        $this->assertSame($expectedBadge, $status->getBadge());
    }

    /**
     * 数据提供器：所有状态和对应的标签
     */
    /**
     * @return array<string, array{MailTaskStatus, string, string}>
     */
    public static function provideStatusLabelData(): array
    {
        return [
            'pending status' => [MailTaskStatus::PENDING, '等待发送', MailTaskStatus::PRIMARY],
            'processing status' => [MailTaskStatus::PROCESSING, '发送中', MailTaskStatus::INFO],
            'sent status' => [MailTaskStatus::SENT, '已发送', MailTaskStatus::SUCCESS],
            'failed status' => [MailTaskStatus::FAILED, '发送失败', MailTaskStatus::DARK],
        ];
    }

    /**
     * 使用 #[TestWith] 注解合并对 value 和 label 的验证
     */
    #[TestWith(['pending', '等待发送'])]
    #[TestWith(['processing', '发送中'])]
    #[TestWith(['sent', '已发送'])]
    #[TestWith(['failed', '发送失败'])]
    public function testValueAndLabelConsistency(string $expectedValue, string $expectedLabel): void
    {
        $status = MailTaskStatus::from($expectedValue);
        $this->assertSame($expectedValue, $status->value);
        $this->assertSame($expectedLabel, $status->getLabel());
    }

    /**
     * 测试标签唯一性
     */
    public function testLabelUniqueness(): void
    {
        $labels = [];
        foreach (MailTaskStatus::cases() as $status) {
            $label = $status->getLabel();
            $this->assertNotContains($label, $labels, "标签 '{$label}' 重复");
            $labels[] = $label;
        }
    }

    /**
     * 测试值唯一性
     */
    public function testValueUniqueness(): void
    {
        $values = [];
        foreach (MailTaskStatus::cases() as $status) {
            $value = $status->value;
            $this->assertNotContains($value, $values, "值 '{$value}' 重复");
            $values[] = $value;
        }
    }

    /**
     * 测试是否实现了预期的接口
     */
    public function testImplementsInterfaces(): void
    {
        $reflection = new \ReflectionEnum(MailTaskStatus::class);

        $interfaceNames = array_map(fn ($interface) => $interface->getName(), $reflection->getInterfaces());

        $this->assertContains('Tourze\EnumExtra\Labelable', $interfaceNames);
        $this->assertContains('Tourze\EnumExtra\Itemable', $interfaceNames);
        $this->assertContains('Tourze\EnumExtra\Selectable', $interfaceNames);
        $this->assertContains('Tourze\EnumExtra\BadgeInterface', $interfaceNames);
    }

    /**
     * 测试枚举可以正确序列化和反序列化
     */
    public function testSerialization(): void
    {
        foreach (MailTaskStatus::cases() as $status) {
            $serialized = serialize($status);
            $unserialized = unserialize($serialized);

            $this->assertInstanceOf(MailTaskStatus::class, $unserialized);
            $this->assertSame($status->value, $unserialized->value);
            $this->assertSame($status->getLabel(), $unserialized->getLabel());
            $this->assertSame($status->getBadge(), $unserialized->getBadge());
        }
    }

    // 移除枚举比较测试 - PHPStan 认为这些测试太明显

    /**
     * 测试从字符串值创建枚举实例
     */
    public function testFromValue(): void
    {
        $this->assertSame(MailTaskStatus::PENDING, MailTaskStatus::from('pending'));
        $this->assertSame(MailTaskStatus::PROCESSING, MailTaskStatus::from('processing'));
        $this->assertSame(MailTaskStatus::SENT, MailTaskStatus::from('sent'));
        $this->assertSame(MailTaskStatus::FAILED, MailTaskStatus::from('failed'));
    }

    /**
     * 测试尝试从无效字符串值创建枚举实例会抛出异常
     */
    public function testFromInvalidValueThrowsException(): void
    {
        $this->expectException(\ValueError::class);
        MailTaskStatus::from('invalid_status');
    }

    /**
     * 测试 tryFrom 方法
     */
    public function testTryFrom(): void
    {
        $this->assertSame(MailTaskStatus::PENDING, MailTaskStatus::tryFrom('pending'));
        $this->assertSame(MailTaskStatus::PROCESSING, MailTaskStatus::tryFrom('processing'));
        $this->assertSame(MailTaskStatus::SENT, MailTaskStatus::tryFrom('sent'));
        $this->assertSame(MailTaskStatus::FAILED, MailTaskStatus::tryFrom('failed'));
        $this->assertNull(MailTaskStatus::tryFrom('invalid_status'));
    }

    /**
     * 测试枚举在数组中的使用
     */
    // 移除冗余的 in_array 测试 - PHPStan 能静态分析出结果

    /**
     * 测试枚举的字符串表示
     */
    public function testStringRepresentation(): void
    {
        // 测试枚举是否可以转换为字符串（通过 value 属性）
        $this->assertSame('pending', (string) MailTaskStatus::PENDING->value);
        $this->assertSame('processing', (string) MailTaskStatus::PROCESSING->value);
        $this->assertSame('sent', (string) MailTaskStatus::SENT->value);
        $this->assertSame('failed', (string) MailTaskStatus::FAILED->value);
    }

    /**
     * 测试 toArray 方法返回正确的数组格式
     */
    public function testToArray(): void
    {
        // 测试 PENDING 状态
        $pendingArray = MailTaskStatus::PENDING->toArray();
        $this->assertIsArray($pendingArray);
        $this->assertEquals(['value' => 'pending', 'label' => '等待发送'], $pendingArray);

        // 测试 PROCESSING 状态
        $processingArray = MailTaskStatus::PROCESSING->toArray();
        $this->assertIsArray($processingArray);
        $this->assertEquals(['value' => 'processing', 'label' => '发送中'], $processingArray);

        // 测试 SENT 状态
        $sentArray = MailTaskStatus::SENT->toArray();
        $this->assertIsArray($sentArray);
        $this->assertEquals(['value' => 'sent', 'label' => '已发送'], $sentArray);

        // 测试 FAILED 状态
        $failedArray = MailTaskStatus::FAILED->toArray();
        $this->assertIsArray($failedArray);
        $this->assertEquals(['value' => 'failed', 'label' => '发送失败'], $failedArray);
    }
}
