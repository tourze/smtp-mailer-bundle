<?php

namespace Tourze\SMTPMailerBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;
use Tourze\SMTPMailerBundle\Service\SMTPSelector\SMTPSelectorStrategyInterface;
use Tourze\SMTPMailerBundle\Service\SMTPSelectorService;

class SMTPSelectorServiceTest extends TestCase
{
    private SMTPConfigRepository|MockObject $smtpConfigRepository;
    
    protected function setUp(): void
    {
        $this->smtpConfigRepository = $this->createMock(SMTPConfigRepository::class);
    }
    
    private function createSelectorStrategy(string $key, string $name): SMTPSelectorStrategyInterface
    {
        $strategy = $this->createMock(SMTPSelectorStrategyInterface::class);
        $strategy->method('getName')->willReturn($name);
        return $strategy;
    }
    
    public function testSelectConfig_WithDefaultStrategy(): void
    {
        // 创建模拟的策略
        $roundRobinStrategy = $this->createMock(SMTPSelectorStrategyInterface::class);
        $strategies = ['round_robin' => $roundRobinStrategy];
        
        // 创建模拟的配置
        $config = new SMTPConfig();
        $config->setName('Test SMTP');
        $configs = [$config];
        
        // 配置存储库返回模拟配置
        $this->smtpConfigRepository->method('findAllEnabled')->willReturn($configs);
        
        // 配置策略会选择第一个配置
        $roundRobinStrategy->method('select')->with($configs)->willReturn($config);
        
        // 创建服务实例
        $service = new SMTPSelectorService($this->smtpConfigRepository, $strategies);
        
        // 验证默认策略（round_robin）被使用
        $result = $service->selectConfig();
        $this->assertSame($config, $result);
    }
    
    public function testSelectConfig_WithSpecificStrategy(): void
    {
        // 创建模拟的策略
        $roundRobinStrategy = $this->createMock(SMTPSelectorStrategyInterface::class);
        $randomStrategy = $this->createMock(SMTPSelectorStrategyInterface::class);
        $strategies = [
            'round_robin' => $roundRobinStrategy,
            'random' => $randomStrategy
        ];
        
        // 创建模拟的配置
        $config1 = new SMTPConfig();
        $config1->setName('SMTP 1');
        
        $config2 = new SMTPConfig();
        $config2->setName('SMTP 2');
        
        $configs = [$config1, $config2];
        
        // 配置存储库返回模拟配置
        $this->smtpConfigRepository->method('findAllEnabled')->willReturn($configs);
        
        // 配置策略会返回不同的结果
        $roundRobinStrategy->method('select')->with($configs)->willReturn($config1);
        $randomStrategy->method('select')->with($configs)->willReturn($config2);
        
        // 创建服务实例
        $service = new SMTPSelectorService($this->smtpConfigRepository, $strategies);
        
        // 测试指定策略
        $result = $service->selectConfig('random');
        $this->assertSame($config2, $result);
    }
    
    public function testSelectConfig_WithNonExistentStrategy(): void
    {
        // 创建模拟的策略
        $roundRobinStrategy = $this->createMock(SMTPSelectorStrategyInterface::class);
        $strategies = ['round_robin' => $roundRobinStrategy];
        
        // 创建模拟的配置
        $config = new SMTPConfig();
        $config->setName('Test SMTP');
        $configs = [$config];
        
        // 配置存储库返回模拟配置
        $this->smtpConfigRepository->method('findAllEnabled')->willReturn($configs);
        
        // 配置策略会选择第一个配置
        $roundRobinStrategy->method('select')->with($configs)->willReturn($config);
        
        // 创建服务实例
        $service = new SMTPSelectorService($this->smtpConfigRepository, $strategies);
        
        // 使用不存在的策略应该回落到默认策略
        $result = $service->selectConfig('non_existent');
        $this->assertSame($config, $result);
    }
    
    public function testSelectConfig_WithNoAvailableConfigs(): void
    {
        // 创建模拟的策略
        $roundRobinStrategy = $this->createMock(SMTPSelectorStrategyInterface::class);
        $strategies = ['round_robin' => $roundRobinStrategy];
        
        // 配置存储库返回空数组
        $this->smtpConfigRepository->method('findAllEnabled')->willReturn([]);
        
        // 创建服务实例
        $service = new SMTPSelectorService($this->smtpConfigRepository, $strategies);
        
        // 应该返回null因为没有可用配置
        $result = $service->selectConfig();
        $this->assertNull($result);
    }
    
    public function testSelectConfig_WithNoStrategies(): void
    {
        // 创建模拟的配置
        $config = new SMTPConfig();
        $config->setName('Test SMTP');
        $configs = [$config];
        
        // 配置存储库返回模拟配置
        $this->smtpConfigRepository->method('findAllEnabled')->willReturn($configs);
        
        // 创建没有策略的服务实例
        $service = new SMTPSelectorService($this->smtpConfigRepository, []);
        
        // 没有策略应该返回第一个配置
        $result = $service->selectConfig();
        $this->assertSame($config, $result);
    }
    
    public function testGetAvailableStrategies(): void
    {
        // 创建模拟的策略
        $roundRobinStrategy = $this->createSelectorStrategy('round_robin', 'Round Robin');
        $randomStrategy = $this->createSelectorStrategy('random', 'Random');
        
        $strategies = [
            'round_robin' => $roundRobinStrategy,
            'random' => $randomStrategy
        ];
        
        // 创建服务实例
        $service = new SMTPSelectorService($this->smtpConfigRepository, $strategies);
        
        // 验证可用策略列表
        $expected = [
            'round_robin' => 'Round Robin',
            'random' => 'Random'
        ];
        
        $this->assertSame($expected, $service->getAvailableStrategies());
    }
    
    public function testGetDefaultStrategy(): void
    {
        // 创建模拟的策略
        $roundRobinStrategy = $this->createMock(SMTPSelectorStrategyInterface::class);
        $strategies = ['round_robin' => $roundRobinStrategy];
        
        // 创建服务实例
        $service = new SMTPSelectorService($this->smtpConfigRepository, $strategies);
        
        // 验证默认策略
        $this->assertSame('round_robin', $service->getDefaultStrategy());
    }
    
    public function testDefaultStrategyFallback_WhenSpecifiedStrategyNotAvailable(): void
    {
        // 创建模拟的策略
        $randomStrategy = $this->createMock(SMTPSelectorStrategyInterface::class);
        $strategies = ['random' => $randomStrategy];
        
        // 创建服务实例 - 指定了不存在的默认策略 'round_robin'
        $service = new SMTPSelectorService($this->smtpConfigRepository, $strategies);
        
        // 验证默认策略应该回落到第一个可用策略
        $this->assertSame('random', $service->getDefaultStrategy());
    }
} 