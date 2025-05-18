<?php

namespace Tourze\SMTPMailerBundle\Tests\Service\SMTPSelector;

use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Service\SMTPSelector\RandomStrategy;

class RandomStrategyTest extends TestCase
{
    private RandomStrategy $strategy;
    
    protected function setUp(): void
    {
        $this->strategy = new RandomStrategy();
    }
    
    public function testGetName(): void
    {
        $this->assertEquals('random', $this->strategy->getName());
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
        
        $result = $this->strategy->select([$config]);
        $this->assertSame($config, $result);
    }
    
    public function testSelect_MultipleConfigs_RandomSelection(): void
    {
        // 创建多个配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        
        $config3 = new SMTPConfig();
        $config3->setName('Config 3');
        
        $configs = [$config1, $config2, $config3];
        
        // 调用多次，确保至少有一次返回不同的结果
        // 注意：这是概率性测试，理论上有极小概率会失败
        $results = [];
        $differentResultsFound = false;
        
        for ($i = 0; $i < 100; $i++) {
            $result = $this->strategy->select($configs);
            $this->assertContains($result, $configs);
            
            $results[] = $result;
            
            // 检查是否有不同的结果
            if ($i > 0 && $results[$i] !== $results[0]) {
                $differentResultsFound = true;
            }
        }
        
        // 由于是随机的，在100次调用中应该至少有一次返回不同的结果
        // 如果都相同，那可能是随机算法有问题
        $this->assertTrue($differentResultsFound, '随机策略在100次调用中未产生不同的结果');
    }
    
    /**
     * 测试随机算法的分布是否相对均匀
     * 这是一个基于概率的测试，理论上有极小概率会失败
     */
    public function testSelect_Distribution(): void
    {
        // 创建多个配置
        $config1 = new SMTPConfig();
        $config1->setName('Config 1');
        
        $config2 = new SMTPConfig();
        $config2->setName('Config 2');
        
        $config3 = new SMTPConfig();
        $config3->setName('Config 3');
        
        $configs = [$config1, $config2, $config3];
        
        // 配置计数器
        $counts = [
            'Config 1' => 0,
            'Config 2' => 0,
            'Config 3' => 0
        ];
        
        // 执行大量的随机选择
        $iterations = 1000;
        for ($i = 0; $i < $iterations; $i++) {
            $result = $this->strategy->select($configs);
            $counts[$result->getName()]++;
        }
        
        // 计算每个配置的选择概率
        $probabilities = [];
        foreach ($counts as $name => $count) {
            $probabilities[$name] = $count / $iterations;
        }
        
        // 检查每个配置的选择概率是否接近 1/3 (允许一定误差)
        foreach ($probabilities as $name => $probability) {
            $this->assertGreaterThan(0.25, $probability, "配置 $name 的选择概率异常低");
            $this->assertLessThan(0.40, $probability, "配置 $name 的选择概率异常高");
        }
    }
} 