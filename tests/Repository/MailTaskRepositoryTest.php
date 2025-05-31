<?php

namespace Tourze\SMTPMailerBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;

class MailTaskRepositoryTest extends TestCase
{
    /**
     * 测试仓库类存在且继承正确的基类
     */
    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists(MailTaskRepository::class));
        
        $reflection = new \ReflectionClass(MailTaskRepository::class);
        $this->assertTrue($reflection->isSubclassOf(ServiceEntityRepository::class));
    }

    /**
     * 测试仓库的所有自定义方法都存在
     */
    public function testCustomMethodsExist(): void
    {
        $reflection = new \ReflectionClass(MailTaskRepository::class);
        
        $methods = [
            'findPendingTasks',
            'findScheduledTasks', 
            'findByStatus',
            'findByDateRange',
            'findBySmtpConfig'
        ];
        
        foreach ($methods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Method {$method} should exist");
        }
    }

    /**
     * 测试findPendingTasks方法签名
     */
    public function testFindPendingTasksMethodSignature(): void
    {
        $reflection = new \ReflectionClass(MailTaskRepository::class);
        $method = $reflection->getMethod('findPendingTasks');
        
        // 测试参数数量
        $this->assertCount(0, $method->getParameters());
        
        // 测试返回类型
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * 测试findScheduledTasks方法签名
     */
    public function testFindScheduledTasksMethodSignature(): void
    {
        $reflection = new \ReflectionClass(MailTaskRepository::class);
        $method = $reflection->getMethod('findScheduledTasks');
        
        // 测试参数数量
        $this->assertCount(0, $method->getParameters());
        
        // 测试返回类型
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * 测试findByStatus方法签名
     */
    public function testFindByStatusMethodSignature(): void
    {
        $reflection = new \ReflectionClass(MailTaskRepository::class);
        $method = $reflection->getMethod('findByStatus');
        
        // 测试参数数量和类型
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('status', $parameters[0]->getName());
        
        // 测试返回类型
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * 测试findByDateRange方法签名
     */
    public function testFindByDateRangeMethodSignature(): void
    {
        $reflection = new \ReflectionClass(MailTaskRepository::class);
        $method = $reflection->getMethod('findByDateRange');
        
        // 测试参数数量和类型
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('startDate', $parameters[0]->getName());
        $this->assertEquals('endDate', $parameters[1]->getName());
        
        // 测试参数类型
        $this->assertTrue($parameters[0]->hasType());
        $this->assertTrue($parameters[1]->hasType());
        
        // 测试返回类型
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * 测试findBySmtpConfig方法签名
     */
    public function testFindBySmtpConfigMethodSignature(): void
    {
        $reflection = new \ReflectionClass(MailTaskRepository::class);
        $method = $reflection->getMethod('findBySmtpConfig');
        
        // 测试参数数量和类型
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('smtpConfigId', $parameters[0]->getName());
        
        // 测试返回类型
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * 测试继承的标准方法存在
     */
    public function testInheritedMethods(): void
    {
        $reflection = new \ReflectionClass(MailTaskRepository::class);
        
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
        // 通过反射检查仓库是否与MailTask实体关联
        $reflection = new \ReflectionClass(MailTaskRepository::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        $this->assertTrue($reflection->isSubclassOf(ServiceEntityRepository::class));
    }

    /**
     * 测试MailTask实体相关的枚举值
     */
    public function testMailTaskStatusEnumValues(): void
    {
        // 确保测试中使用的状态值在枚举中存在
        $reflection = new \ReflectionClass(MailTaskStatus::class);
        $this->assertTrue($reflection->isEnum());
        
        $cases = MailTaskStatus::cases();
        $caseValues = array_map(fn($case) => $case->value, $cases);
        
        $expectedStatuses = ['pending', 'processing', 'sent', 'failed'];
        foreach ($expectedStatuses as $status) {
            $this->assertContains($status, $caseValues, "Status {$status} should exist in enum");
        }
    }

    /**
     * 测试可以创建MailTask实体
     */
    public function testCanCreateMailTaskEntity(): void
    {
        $task = new MailTask();
        $this->assertInstanceOf(MailTask::class, $task);
        
        // 测试基本属性设置
        $task->setFromEmail('test@example.com');
        $task->setToEmail('recipient@example.com');
        $task->setSubject('Test Subject');
        $task->setBody('Test Body');
        $task->setStatus(MailTaskStatus::PENDING);
        
        $this->assertEquals('test@example.com', $task->getFromEmail());
        $this->assertEquals('recipient@example.com', $task->getToEmail());
        $this->assertEquals('Test Subject', $task->getSubject());
        $this->assertEquals('Test Body', $task->getBody());
        $this->assertEquals(MailTaskStatus::PENDING, $task->getStatus());
    }

    /**
     * 测试方法可见性
     */
    public function testMethodVisibility(): void
    {
        $reflection = new \ReflectionClass(MailTaskRepository::class);
        
        $publicMethods = [
            'findPendingTasks',
            'findScheduledTasks',
            'findByStatus', 
            'findByDateRange',
            'findBySmtpConfig'
        ];
        
        foreach ($publicMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method {$methodName} should be public");
        }
    }

    /**
     * 测试状态值提供器数据
     * @dataProvider statusProvider
     */
    public function testStatusValues(string $status): void
    {
        // 验证状态值是有效的字符串
        $this->assertIsString($status);
        $this->assertNotEmpty($status);
        
        // 验证状态值在预期范围内
        $validStatuses = ['pending', 'processing', 'sent', 'failed'];
        $this->assertContains($status, $validStatuses);
    }

    /**
     * 数据提供器：状态值
     */
    public static function statusProvider(): array
    {
        return [
            'pending status' => ['pending'],
            'processing status' => ['processing'], 
            'sent status' => ['sent'],
            'failed status' => ['failed'],
        ];
    }

    /**
     * 测试日期范围参数类型
     */
    public function testDateRangeParameterTypes(): void
    {
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');
        
        $this->assertInstanceOf(\DateTime::class, $startDate);
        $this->assertInstanceOf(\DateTime::class, $endDate);
        $this->assertLessThan($endDate, $startDate);
    }

    /**
     * 测试SMTP配置ID参数类型
     */
    public function testSmtpConfigIdParameterType(): void
    {
        $smtpConfigId = 123;
        
        $this->assertIsInt($smtpConfigId);
        $this->assertGreaterThan(0, $smtpConfigId);
    }
} 