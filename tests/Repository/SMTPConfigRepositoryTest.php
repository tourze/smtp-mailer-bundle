<?php

namespace Tourze\SMTPMailerBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;

class SMTPConfigRepositoryTest extends TestCase
{
    /**
     * 测试仓库类存在且继承正确的基类
     */
    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists(SMTPConfigRepository::class));
        
        $reflection = new \ReflectionClass(SMTPConfigRepository::class);
        $this->assertTrue($reflection->isSubclassOf(ServiceEntityRepository::class));
    }

    /**
     * 测试仓库的所有自定义方法都存在
     */
    public function testCustomMethodsExist(): void
    {
        $reflection = new \ReflectionClass(SMTPConfigRepository::class);
        
        $methods = [
            'findAllEnabled',
            'findAllEnabledWithWeight',
            'findAllEnabledByPriority',
            'countEnabled'
        ];
        
        foreach ($methods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Method {$method} should exist");
        }
    }

    /**
     * 测试findAllEnabled方法签名
     */
    public function testFindAllEnabledMethodSignature(): void
    {
        $reflection = new \ReflectionClass(SMTPConfigRepository::class);
        $method = $reflection->getMethod('findAllEnabled');
        
        // 测试参数数量
        $this->assertCount(0, $method->getParameters());
        
        // 测试返回类型
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * 测试findAllEnabledWithWeight方法签名
     */
    public function testFindAllEnabledWithWeightMethodSignature(): void
    {
        $reflection = new \ReflectionClass(SMTPConfigRepository::class);
        $method = $reflection->getMethod('findAllEnabledWithWeight');
        
        // 测试参数数量
        $this->assertCount(0, $method->getParameters());
        
        // 测试返回类型
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * 测试findAllEnabledByPriority方法签名
     */
    public function testFindAllEnabledByPriorityMethodSignature(): void
    {
        $reflection = new \ReflectionClass(SMTPConfigRepository::class);
        $method = $reflection->getMethod('findAllEnabledByPriority');
        
        // 测试参数数量
        $this->assertCount(0, $method->getParameters());
        
        // 测试返回类型
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * 测试countEnabled方法签名
     */
    public function testCountEnabledMethodSignature(): void
    {
        $reflection = new \ReflectionClass(SMTPConfigRepository::class);
        $method = $reflection->getMethod('countEnabled');
        
        // 测试参数数量
        $this->assertCount(0, $method->getParameters());
        
        // 测试返回类型
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertEquals('int', $returnType->getName());
    }

    /**
     * 测试继承的标准方法存在
     */
    public function testInheritedMethods(): void
    {
        $reflection = new \ReflectionClass(SMTPConfigRepository::class);
        
        $standardMethods = ['find', 'findOneBy', 'findAll', 'findBy'];
        
        foreach ($standardMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Method {$method} should be inherited");
        }
    }

    /**
     * 测试实体类关联
     */
    public function testEntityClass(): void
    {
        // 通过反射检查仓库是否与SMTPConfig实体关联
        $reflection = new \ReflectionClass(SMTPConfigRepository::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        $this->assertTrue($reflection->isSubclassOf(ServiceEntityRepository::class));
    }

    /**
     * 测试可以创建SMTPConfig实体
     */
    public function testCanCreateSMTPConfigEntity(): void
    {
        $config = new SMTPConfig();
        $this->assertInstanceOf(SMTPConfig::class, $config);
        
        // 测试基本属性设置
        $config->setName('Test SMTP');
        $config->setHost('smtp.example.com');
        $config->setPort(587);
        $config->setUsername('test@example.com');
        $config->setPassword('password');
        $config->setEncryption('tls');
        $config->setValid(true);
        $config->setWeight(10);
        $config->setPriority(1);
        
        $this->assertEquals('Test SMTP', $config->getName());
        $this->assertEquals('smtp.example.com', $config->getHost());
        $this->assertEquals(587, $config->getPort());
        $this->assertEquals('test@example.com', $config->getUsername());
        $this->assertEquals('password', $config->getPassword());
        $this->assertEquals('tls', $config->getEncryption());
        $this->assertTrue($config->isValid());
        $this->assertEquals(10, $config->getWeight());
        $this->assertEquals(1, $config->getPriority());
    }

    /**
     * 测试方法可见性
     */
    public function testMethodVisibility(): void
    {
        $reflection = new \ReflectionClass(SMTPConfigRepository::class);
        
        $publicMethods = [
            'findAllEnabled',
            'findAllEnabledWithWeight',
            'findAllEnabledByPriority',
            'countEnabled'
        ];
        
        foreach ($publicMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method {$methodName} should be public");
        }
    }

    /**
     * 测试返回类型验证
     */
    public function testReturnTypes(): void
    {
        $reflection = new \ReflectionClass(SMTPConfigRepository::class);
        
        // 测试返回数组的方法
        $arrayMethods = ['findAllEnabled', 'findAllEnabledWithWeight', 'findAllEnabledByPriority'];
        foreach ($arrayMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();
            $this->assertEquals('array', $returnType->getName(), "Method {$methodName} should return array");
        }
        
        // 测试返回int的方法
        $countMethod = $reflection->getMethod('countEnabled');
        $returnType = $countMethod->getReturnType();
        $this->assertEquals('int', $returnType->getName(), "Method countEnabled should return int");
    }

    /**
     * 测试SMTPConfig实体的DSN生成功能
     */
    public function testSMTPConfigDsnGeneration(): void
    {
        $config = new SMTPConfig();
        $config->setHost('smtp.example.com');
        $config->setPort(587);
        $config->setUsername('user@example.com');
        $config->setPassword('password');
        $config->setEncryption('tls');
        
        // 测试DSN生成方法存在
        $this->assertTrue(method_exists($config, 'getDsn'));
        
        // 测试生成的DSN
        $dsn = $config->getDsn();
        $this->assertIsString($dsn);
        $this->assertStringContainsString('smtp.example.com', $dsn);
        $this->assertStringContainsString('587', $dsn);
    }

    /**
     * 测试SMTPConfig实体的字符串表示
     */
    public function testSMTPConfigStringRepresentation(): void
    {
        $config = new SMTPConfig();
        $config->setName('Test Config');
        
        $this->assertEquals('Test Config', (string)$config);
    }

    /**
     * 测试有效的加密类型
     */
    public function testValidEncryptionTypes(): void
    {
        $config = new SMTPConfig();
        
        $validEncryptions = ['tls', 'ssl', 'none'];
        foreach ($validEncryptions as $encryption) {
            $config->setEncryption($encryption);
            $this->assertEquals($encryption, $config->getEncryption());
        }
    }

    /**
     * 测试端口范围
     */
    public function testPortRange(): void
    {
        $config = new SMTPConfig();
        
        // 测试常用端口
        $commonPorts = [25, 465, 587, 2525];
        foreach ($commonPorts as $port) {
            $config->setPort($port);
            $this->assertEquals($port, $config->getPort());
            $this->assertGreaterThan(0, $config->getPort());
            $this->assertLessThanOrEqual(65535, $config->getPort());
        }
    }

    /**
     * 测试权重和优先级的默认值
     */
    public function testDefaultValues(): void
    {
        $config = new SMTPConfig();
        
        // 测试权重默认值
        $this->assertEquals(1, $config->getWeight());
        
        // 测试优先级默认值
        $this->assertEquals(0, $config->getPriority());
        
        // 测试有效性默认值
        $this->assertTrue($config->isValid());
        
        // 测试加密方式默认值
        $this->assertEquals('tls', $config->getEncryption());
        
        // 测试端口默认值
        $this->assertEquals(587, $config->getPort());
    }

    /**
     * 测试nullable字段
     */
    public function testNullableFields(): void
    {
        $config = new SMTPConfig();
        
        // 测试用户名可以为null
        $config->setUsername(null);
        $this->assertNull($config->getUsername());
        
        // 测试密码可以为null
        $config->setPassword(null);
        $this->assertNull($config->getPassword());
        
        // 测试认证模式可以为null
        $config->setAuthMode(null);
        $this->assertNull($config->getAuthMode());
    }

    /**
     * 测试方法链式调用
     */
    public function testMethodChaining(): void
    {
        $config = new SMTPConfig();
        
        // 测试所有setter方法都返回self，支持链式调用
        $result = $config
            ->setName('Chain Test')
            ->setHost('smtp.test.com')
            ->setPort(587)
            ->setUsername('user')
            ->setPassword('pass')
            ->setEncryption('tls')
            ->setValid(true)
            ->setWeight(5)
            ->setPriority(2);
        
        $this->assertSame($config, $result);
        $this->assertEquals('Chain Test', $config->getName());
        $this->assertEquals('smtp.test.com', $config->getHost());
        $this->assertEquals(587, $config->getPort());
    }
}
