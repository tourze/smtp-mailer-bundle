<?php

namespace Tourze\SMTPMailerBundle\Tests\Service\SMTPSelector;

use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Service\SMTPSelector\PriorityStrategy;

class PriorityStrategyTest extends TestCase
{
    private PriorityStrategy $strategy;
    
    protected function setUp(): void
    {
        $this->strategy = new PriorityStrategy();
    }
    
    public function testGetName(): void
    {
        $this->assertEquals('priority', $this->strategy->getName());
    }
    
    public function testSelect_EmptyConfigs(): void
    {
        $result = $this->strategy->select([]);
        $this->assertNull($result);
    }
    
    public function testSelect_SingleConfig(): void
    {
        $config = new SMTPConfig();
        $config->setName('Config 1');
        $config->setPriority(5);
        
        $result = $this->strategy->select([$config]);
        $this->assertSame($config, $result);
    }
    
    public function testSelect_MultipleConfigs_HighestPrioritySelected(): void
    {
        // 创建优先级不同的多个配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        $config1->setPriority(10); // 优先级 10
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        $config2->setPriority(20); // 优先级 20
        
        $config3 = new SMTPConfig();
        $config3->setName('Config 3');
        $config3->setPriority(5);  // 优先级 5
        
        // 无序添加配置
        $configs = [$config1, $config3, $config2];
        
        // 应该选择优先级最高的配置（Config 2）
        $result = $this->strategy->select($configs);
        $this->assertSame($config2, $result);
    }
    
    public function testSelect_MultipleConfigs_EqualPriorities(): void
    {
        // 由于具有相同优先级的配置会使用随机策略选择，这个测试可能不稳定
        // 我们可以使用 mock 来控制随机性
        $randomStrategy = $this->getMockBuilder('Tourze\SMTPMailerBundle\Service\SMTPSelector\RandomStrategy')
            ->getMock();
        
        // 创建反射类来替换 PriorityStrategy 内部的 RandomStrategy 实例
        $reflectionClass = new \ReflectionClass(PriorityStrategy::class);
        $method = $reflectionClass->getMethod('select');
        
        // 创建优先级相同的多个配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        $config1->setPriority(10);
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        $config2->setPriority(10);
        
        $config3 = new SMTPConfig();
        $config3->setName('Config 3');
        $config3->setPriority(10);
        
        $configs = [$config1, $config2, $config3];
        
        // 此处我们只能验证它确实从具有最高优先级的配置中选择了一个
        $result = $this->strategy->select($configs);
        $this->assertContains($result, $configs);
    }
    
    public function testSelect_MixedPriorities(): void
    {
        // 创建混合优先级的配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        $config1->setPriority(10);
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        $config2->setPriority(20);
        
        $config3 = new SMTPConfig();
        $config3->setName('Config 3');
        $config3->setPriority(20); // 与 Config 2 相同的优先级
        
        $config4 = new SMTPConfig();
        $config4->setName('Config 4');
        $config4->setPriority(5);
        
        // 无序添加配置
        $configs = [$config1, $config4, $config3, $config2];
        
        // 由于有两个具有相同最高优先级的配置，结果将是随机的
        // 我们验证选择的是优先级为20的配置之一
        $result = $this->strategy->select($configs);
        $this->assertTrue(
            $result === $config2 || $result === $config3,
            '应该选择优先级最高的配置之一'
        );
    }
    
    public function testSelect_NegativePriorities(): void
    {
        // 创建包含负优先级的配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        $config1->setPriority(-10);
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        $config2->setPriority(0);
        
        $config3 = new SMTPConfig();
        $config3->setName('Config 3');
        $config3->setPriority(5);
        
        // 无序添加配置
        $configs = [$config1, $config2, $config3];
        
        // 应该选择优先级最高的配置（Config 3）
        $result = $this->strategy->select($configs);
        $this->assertSame($config3, $result);
    }
    
    public function testSelect_AllNegativePriorities(): void
    {
        // 创建所有都是负优先级的配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        $config1->setPriority(-30);
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        $config2->setPriority(-10); // 最高优先级
        
        $config3 = new SMTPConfig();
        $config3->setName('Config 3');
        $config3->setPriority(-20);
        
        // 无序添加配置
        $configs = [$config1, $config3, $config2];
        
        // 应该选择优先级最高的配置（Config 2）
        $result = $this->strategy->select($configs);
        $this->assertSame($config2, $result);
    }
} 