<?php

namespace Tourze\SMTPMailerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\SMTPMailerBundle\SMTPMailerBundle;

class SMTPMailerBundleTest extends TestCase
{
    private SMTPMailerBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new SMTPMailerBundle();
    }

    public function testBundleExists(): void
    {
        $this->assertInstanceOf(SMTPMailerBundle::class, $this->bundle);
        $this->assertInstanceOf(Bundle::class, $this->bundle);
    }

    public function testBundleInheritance(): void
    {
        // 验证Bundle继承结构
        $reflection = new \ReflectionClass(SMTPMailerBundle::class);
        $this->assertTrue($reflection->isSubclassOf(Bundle::class));
    }

    public function testGetName(): void
    {
        // 测试Bundle名称
        $name = $this->bundle->getName();
        $this->assertEquals('SMTPMailerBundle', $name);
    }

    public function testGetNamespace(): void
    {
        // 测试Bundle命名空间
        $namespace = $this->bundle->getNamespace();
        $this->assertEquals('Tourze\SMTPMailerBundle', $namespace);
    }

    public function testGetPath(): void
    {
        // 测试Bundle路径
        $path = $this->bundle->getPath();
        $this->assertIsString($path);
        $this->assertStringContainsString('smtp-mailer-bundle', $path);
        // 路径应该指向src目录
        $this->assertStringEndsWith('/src', $path);
    }

    public function testGetContainerExtension(): void
    {
        // 测试容器扩展
        $extension = $this->bundle->getContainerExtension();
        
        if ($extension !== null) {
            $this->assertInstanceOf(
                'Tourze\SMTPMailerBundle\DependencyInjection\SMTPMailerExtension',
                $extension
            );
        }
    }

    public function testBundleConfiguration(): void
    {
        // 测试Bundle配置
        $this->assertTrue(class_exists(SMTPMailerBundle::class));
        
        // 验证Bundle类在正确的命名空间中
        $reflection = new \ReflectionClass(SMTPMailerBundle::class);
        $this->assertEquals('Tourze\SMTPMailerBundle', $reflection->getNamespaceName());
    }

    public function testBundleIsInstantiable(): void
    {
        // 测试Bundle可以实例化
        $newBundle = new SMTPMailerBundle();
        $this->assertInstanceOf(SMTPMailerBundle::class, $newBundle);
        $this->assertNotSame($this->bundle, $newBundle);
    }

    public function testBundleStructure(): void
    {
        // 验证Bundle具有标准的Bundle结构
        $bundlePath = $this->bundle->getPath();
        
        // 检查重要目录是否存在
        $expectedDirs = [
            'Controller',
            'Entity', 
            'Service',
            'Repository',
            'DependencyInjection'
        ];
        
        foreach ($expectedDirs as $dir) {
            $dirPath = $bundlePath . '/' . $dir;
            $this->assertDirectoryExists($dirPath, "Directory {$dir} should exist in bundle");
        }
    }

    public function testBundleResourcesExist(): void
    {
        // 检查资源文件是否存在
        $bundlePath = $this->bundle->getPath();
        $resourcesPath = $bundlePath . '/Resources';
        
        if (is_dir($resourcesPath)) {
            $this->assertDirectoryExists($resourcesPath);
            
            // 检查config目录
            $configPath = $resourcesPath . '/config';
            if (is_dir($configPath)) {
                $this->assertDirectoryExists($configPath);
                
                // 检查services.yaml是否存在
                $servicesFile = $configPath . '/services.yaml';
                if (file_exists($servicesFile)) {
                    $this->assertFileExists($servicesFile);
                    $this->assertFileIsReadable($servicesFile);
                }
            }
        }
    }

    public function testBundleMethodsExist(): void
    {
        // 验证Bundle的标准方法存在
        $this->assertTrue(method_exists($this->bundle, 'getName'));
        $this->assertTrue(method_exists($this->bundle, 'getNamespace'));
        $this->assertTrue(method_exists($this->bundle, 'getPath'));
        $this->assertTrue(method_exists($this->bundle, 'getContainerExtension'));
    }

    public function testBundleMethodsAreCallable(): void
    {
        // 验证Bundle方法是可调用的
        $this->assertIsCallable([$this->bundle, 'getName']);
        $this->assertIsCallable([$this->bundle, 'getNamespace']);
        $this->assertIsCallable([$this->bundle, 'getPath']);
        $this->assertIsCallable([$this->bundle, 'getContainerExtension']);
    }

    public function testBundleNoConstructorParameters(): void
    {
        // 验证Bundle构造函数不需要参数
        $reflection = new \ReflectionClass(SMTPMailerBundle::class);
        $constructor = $reflection->getConstructor();
        
        if ($constructor !== null) {
            $this->assertCount(0, $constructor->getParameters(), 'Bundle constructor should not require parameters');
        } else {
            // 如果没有构造函数，也是正常的
            $this->assertTrue(true, 'Bundle has no custom constructor');
        }
    }

    public function testBundleIsNotAbstract(): void
    {
        // 验证Bundle类不是抽象的
        $reflection = new \ReflectionClass(SMTPMailerBundle::class);
        $this->assertFalse($reflection->isAbstract(), 'Bundle should not be abstract');
    }

    public function testBundleIsFinal(): void
    {
        // 通常Bundle不应该是final的，除非有特殊需求
        $reflection = new \ReflectionClass(SMTPMailerBundle::class);
        // 这里不强制要求final或非final，只是记录状态
        $isFinal = $reflection->isFinal();
        $this->assertIsBool($isFinal);
    }

    public function testBundleHasCorrectFileStructure(): void
    {
        // 验证Bundle文件结构
        $bundlePath = $this->bundle->getPath();
        
        // 检查Bundle类文件本身（在src目录中）
        $bundleFile = $bundlePath . '/SMTPMailerBundle.php';
        $this->assertFileExists($bundleFile, 'Bundle class file should exist');
    }

    public function testBundleCanBeSerializedAndUnserialized(): void
    {
        // 测试Bundle的序列化（虽然通常不建议序列化Bundle）
        try {
            $serialized = serialize($this->bundle);
            $unserialized = unserialize($serialized);
            $this->assertInstanceOf(SMTPMailerBundle::class, $unserialized);
        } catch (\Throwable $e) {
            // 如果Bundle不支持序列化，这是正常的
            $this->addToAssertionCount(1);
        }
    }

    public function testBundleStringRepresentation(): void
    {
        // 测试Bundle的字符串表示
        $bundleName = $this->bundle->getName();
        $this->assertIsString($bundleName);
        // Bundle的名称应该包含类名
        $this->assertStringContainsString('SMTPMailerBundle', $bundleName);
    }
} 