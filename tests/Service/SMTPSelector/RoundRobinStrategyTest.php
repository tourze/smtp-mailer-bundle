<?php

namespace Tourze\SMTPMailerBundle\Tests\Service\SMTPSelector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Service\SMTPSelector\RoundRobinStrategy;

/**
 * @internal
 */
#[CoversClass(RoundRobinStrategy::class)]
final class RoundRobinStrategyTest extends TestCase
{
    private RoundRobinStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new RoundRobinStrategy();
    }

    public function testGetName(): void
    {
        $this->assertEquals('round_robin', $this->strategy->getName());
    }

    public function testSelectEmptyConfigs(): void
    {
        $result = $this->strategy->select([]);
        $this->assertNull($result);
    }

    public function testSelectSingleConfig(): void
    {
        $config = new SMTPConfig();
        $config->setName('Config 1');

        $result = $this->strategy->select([$config]);
        $this->assertSame($config, $result);
    }

    public function testSelectMultipleConfigsRoundRobin(): void
    {
        // 创建多个配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');

        $config2 = new SMTPConfig();
        $config2->setName('Config 2');

        $config3 = new SMTPConfig();
        $config3->setName('Config 3');

        $configs = [$config1, $config2, $config3];

        // 第一次调用应该返回第一个配置
        $result1 = $this->strategy->select($configs);
        $this->assertSame($config1, $result1);

        // 第二次调用应该返回第二个配置
        $result2 = $this->strategy->select($configs);
        $this->assertSame($config2, $result2);

        // 第三次调用应该返回第三个配置
        $result3 = $this->strategy->select($configs);
        $this->assertSame($config3, $result3);

        // 第四次调用应该再次从第一个配置开始
        $result4 = $this->strategy->select($configs);
        $this->assertSame($config1, $result4);
    }
}
