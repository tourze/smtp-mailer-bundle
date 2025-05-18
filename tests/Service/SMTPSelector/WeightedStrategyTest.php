<?php

namespace Tourze\SMTPMailerBundle\Tests\Service\SMTPSelector;

use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Service\SMTPSelector\WeightedStrategy;

class WeightedStrategyTest extends TestCase
{
    private WeightedStrategy $strategy;
    
    protected function setUp(): void
    {
        $this->strategy = new WeightedStrategy();
    }
    
    public function testGetName(): void
    {
        $this->assertEquals('weighted', $this->strategy->getName());
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
        $config->setWeight(5);
        
        $result = $this->strategy->select([$config]);
        $this->assertSame($config, $result);
    }
    
    public function testSelect_MultipleConfigs_EqualWeights(): void
    {
        // 创建权重相同的多个配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        $config1->setWeight(1);
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        $config2->setWeight(1);
        
        $config3 = new SMTPConfig();
        $config3->setName('Config 3');
        $config3->setWeight(1);
        
        $configs = [$config1, $config2, $config3];
        
        // 执行大量的选择，应该有概率选到每个配置
        $counts = [
            'Config 1' => 0,
            'Config 2' => 0,
            'Config 3' => 0
        ];
        
        $iterations = 1000;
        for ($i = 0; $i < $iterations; $i++) {
            $result = $this->strategy->select($configs);
            $counts[$result->getName()]++;
        }
        
        // 由于权重相同，每个配置的选择比例应该接近 1/3
        foreach ($counts as $name => $count) {
            $probability = $count / $iterations;
            $this->assertGreaterThan(0.25, $probability, "配置 $name 的选择概率异常低");
            $this->assertLessThan(0.40, $probability, "配置 $name 的选择概率异常高");
        }
    }
    
    public function testSelect_MultipleConfigs_DifferentWeights(): void
    {
        // 创建权重不同的多个配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        $config1->setWeight(1); // 权重 1
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        $config2->setWeight(2); // 权重 2
        
        $config3 = new SMTPConfig();
        $config3->setName('Config 3');
        $config3->setWeight(7); // 权重 7
        
        $configs = [$config1, $config2, $config3];
        $totalWeight = 10; // 1 + 2 + 7 = 10
        
        // 执行大量的选择
        $counts = [
            'Config 1' => 0,
            'Config 2' => 0,
            'Config 3' => 0
        ];
        
        $iterations = 10000;
        for ($i = 0; $i < $iterations; $i++) {
            $result = $this->strategy->select($configs);
            $counts[$result->getName()]++;
        }
        
        // 计算理论概率和实际概率
        $expectedProbabilities = [
            'Config 1' => 1 / $totalWeight,  // 0.1
            'Config 2' => 2 / $totalWeight,  // 0.2
            'Config 3' => 7 / $totalWeight   // 0.7
        ];
        
        $actualProbabilities = [];
        foreach ($counts as $name => $count) {
            $actualProbabilities[$name] = $count / $iterations;
        }
        
        // 验证实际概率是否接近理论概率（允许10%的误差）
        foreach ($expectedProbabilities as $name => $expected) {
            $actual = $actualProbabilities[$name];
            $this->assertGreaterThan($expected - 0.05, $actual, "配置 $name 的选择概率异常低");
            $this->assertLessThan($expected + 0.05, $actual, "配置 $name 的选择概率异常高");
        }
    }
    
    public function testSelect_MultipleConfigs_ZeroWeights(): void
    {
        // 创建权重为零的配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        $config1->setWeight(0); // 权重为 0
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        $config2->setWeight(5); // 权重为 5
        
        $configs = [$config1, $config2];
        
        // 执行多次选择，权重为0的配置不应该被选中
        for ($i = 0; $i < 100; $i++) {
            $result = $this->strategy->select($configs);
            $this->assertSame($config2, $result, '权重为0的配置被错误地选中');
        }
    }
    
    public function testSelect_AllZeroWeights(): void
    {
        // 创建所有配置权重都为零的情况
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        $config1->setWeight(0);
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        $config2->setWeight(0);
        
        $configs = [$config1, $config2];
        
        // 由于所有权重都为0，选择会使用随机策略
        $result = $this->strategy->select($configs);
        $this->assertContains($result, $configs, '返回的配置不在配置列表中');
    }
} 