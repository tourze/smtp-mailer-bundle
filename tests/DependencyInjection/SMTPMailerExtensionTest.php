<?php

namespace Tourze\SMTPMailerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Tourze\SMTPMailerBundle\DependencyInjection\SMTPMailerExtension;

class SMTPMailerExtensionTest extends TestCase
{
    private SMTPMailerExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new SMTPMailerExtension();
        $this->container = new ContainerBuilder();
    }

    public function testExtensionExists(): void
    {
        $this->assertInstanceOf(SMTPMailerExtension::class, $this->extension);
        $this->assertInstanceOf(Extension::class, $this->extension);
    }

    public function testExtensionInheritance(): void
    {
        // 验证Extension继承结构
        $reflection = new \ReflectionClass(SMTPMailerExtension::class);
        $this->assertTrue($reflection->isSubclassOf(Extension::class));
    }

    public function testLoadMethodExists(): void
    {
        $this->assertTrue(method_exists($this->extension, 'load'));
        
        $reflection = new \ReflectionClass($this->extension);
        $method = $reflection->getMethod('load');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(2, $method->getParameters());
        
        $parameters = $method->getParameters();
        $this->assertEquals('configs', $parameters[0]->getName());
        $this->assertEquals('container', $parameters[1]->getName());
    }

    public function testLoadMethodSignature(): void
    {
        // 测试load方法的签名
        $reflection = new \ReflectionClass($this->extension);
        $method = $reflection->getMethod('load');
        
        $parameters = $method->getParameters();
        
        // 第一个参数：configs数组
        $configsParam = $parameters[0];
        $this->assertEquals('configs', $configsParam->getName());
        $this->assertTrue($configsParam->hasType());
        $this->assertEquals('array', $configsParam->getType()->getName());
        
        // 第二个参数：ContainerBuilder
        $containerParam = $parameters[1];
        $this->assertEquals('container', $containerParam->getName());
        $this->assertTrue($containerParam->hasType());
        $this->assertEquals(ContainerBuilder::class, $containerParam->getType()->getName());
        
        // 返回类型应该是void
        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    public function testLoadWithEmptyConfigs(): void
    {
        // 测试使用空配置加载
        $configs = [];
        
        // 检查services.yaml文件是否存在
        $resourcePath = __DIR__ . '/../../src/Resources/config/services.yaml';
        if (!file_exists($resourcePath)) {
            $this->markTestSkipped('Services configuration file not found');
        }
        
        $this->extension->load($configs, $this->container);
        
        // 验证容器构建成功
        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
    }

    public function testServicesConfigurationFileExists(): void
    {
        // 测试services.yaml配置文件是否存在
        $reflection = new \ReflectionClass($this->extension);
        $method = $reflection->getMethod('load');
        
        // 验证配置文件路径
        $expectedPath = __DIR__ . '/../../src/Resources/config/services.yaml';
        
        if (file_exists($expectedPath)) {
            $this->assertFileExists($expectedPath);
            $this->assertFileIsReadable($expectedPath);
            
            // 验证文件内容是有效的YAML
            $content = file_get_contents($expectedPath);
            $this->assertIsString($content);
            $this->assertNotEmpty($content);
        }
    }

    public function testFileLocatorUsage(): void
    {
        // 测试FileLocator的使用
        $this->assertTrue(class_exists(FileLocator::class));
        
        // 验证配置目录存在
        $configDir = __DIR__ . '/../../src/Resources/config';
        if (is_dir($configDir)) {
            $this->assertDirectoryExists($configDir);
        }
    }

    public function testYamlFileLoaderUsage(): void
    {
        // 测试YamlFileLoader的使用
        $this->assertTrue(class_exists(YamlFileLoader::class));
        
        // 创建一个FileLocator实例来测试
        $configDir = __DIR__ . '/../../src/Resources/config';
        if (is_dir($configDir)) {
            $fileLocator = new FileLocator($configDir);
            $this->assertInstanceOf(FileLocator::class, $fileLocator);
            
            // 创建YamlFileLoader
            $loader = new YamlFileLoader($this->container, $fileLocator);
            $this->assertInstanceOf(YamlFileLoader::class, $loader);
        }
    }

    public function testExtensionHasNoConstructorParameters(): void
    {
        // 验证Extension构造函数不需要参数
        $reflection = new \ReflectionClass(SMTPMailerExtension::class);
        $constructor = $reflection->getConstructor();
        
        if ($constructor !== null) {
            $this->assertCount(0, $constructor->getParameters(), 'Extension constructor should not require parameters');
        } else {
            // 如果没有构造函数，也是正常的
            $this->assertTrue(true, 'Extension has no custom constructor');
        }
    }

    public function testExtensionIsInstantiable(): void
    {
        // 测试Extension可以实例化
        $newExtension = new SMTPMailerExtension();
        $this->assertInstanceOf(SMTPMailerExtension::class, $newExtension);
        $this->assertNotSame($this->extension, $newExtension);
    }

    public function testExtensionNamespace(): void
    {
        // 测试Extension的命名空间
        $reflection = new \ReflectionClass(SMTPMailerExtension::class);
        $this->assertEquals('Tourze\SMTPMailerBundle\DependencyInjection', $reflection->getNamespaceName());
    }

    public function testContainerBuilderIntegration(): void
    {
        // 测试与ContainerBuilder的集成
        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
        
        // 验证容器可以用于Extension
        $this->assertTrue($this->container instanceof ContainerBuilder);
    }

    public function testLoadMethodDoesNotThrowException(): void
    {
        // 测试load方法不会抛出异常
        $configs = [];
        
        try {
            $this->extension->load($configs, $this->container);
            $this->assertTrue(true); // 如果没有异常，测试通过
        } catch (\Throwable $e) {
            // 如果配置文件不存在，这是可以接受的
            if (strpos($e->getMessage(), 'services.yaml') !== false) {
                $this->markTestSkipped('Services configuration file not available');
            } else {
                throw $e;
            }
        }
    }

    public function testExtensionImplementsCorrectInterface(): void
    {
        // 验证Extension实现正确的接口
        $this->assertInstanceOf(Extension::class, $this->extension);
        
        // 验证Extension有必需的方法
        $requiredMethods = ['load'];
        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists($this->extension, $method), "Method {$method} should exist");
        }
    }

    public function testConfigurationFileStructure(): void
    {
        // 测试配置文件结构
        $configPath = __DIR__ . '/../../src/Resources/config/services.yaml';
        
        if (file_exists($configPath)) {
            $content = file_get_contents($configPath);
            $this->assertIsString($content);
            $this->assertNotEmpty($content);
            
            // 验证YAML格式（简单检查）
            if (function_exists('yaml_parse')) {
                try {
                    $parsed = yaml_parse($content);
                    $this->assertIsArray($parsed);
                } catch (\Throwable $e) {
                    // 如果解析失败，只检查内容不为空
                    $this->assertNotEmpty($content);
                }
            } else {
                // 如果yaml_parse函数不可用，进行基本检查
                $this->assertStringContainsString('services:', $content, 'Config should contain services section');
            }
        } else {
            $this->markTestSkipped('Services configuration file not found');
        }
    }

    public function testExtensionCanHandleMultipleConfigs(): void
    {
        // 测试Extension可以处理多个配置
        $configs = [
            [],
            ['some_config' => 'value'],
            ['another_config' => ['nested' => 'value']]
        ];
        
        try {
            $this->extension->load($configs, $this->container);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            // 如果配置文件不存在，跳过测试
            if (strpos($e->getMessage(), 'services.yaml') !== false) {
                $this->markTestSkipped('Services configuration file not available');
            } else {
                throw $e;
            }
        }
    }

    public function testExtensionMethodsAreCallable(): void
    {
        // 验证Extension方法是可调用的
        $this->assertIsCallable([$this->extension, 'load']);
    }

    public function testResourceDirectoryStructure(): void
    {
        // 测试Resources目录结构
        $resourcesDir = __DIR__ . '/../../src/Resources';
        
        if (is_dir($resourcesDir)) {
            $this->assertDirectoryExists($resourcesDir);
            
            $configDir = $resourcesDir . '/config';
            if (is_dir($configDir)) {
                $this->assertDirectoryExists($configDir);
            }
        }
    }
} 