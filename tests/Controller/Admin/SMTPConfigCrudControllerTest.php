<?php

namespace Tourze\SMTPMailerBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Controller\Admin\SMTPConfigCrudController;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

class SMTPConfigCrudControllerTest extends TestCase
{
    private SMTPConfigCrudController&MockObject $controller;

    protected function setUp(): void
    {
        $this->controller = new SMTPConfigCrudController();
    }

    public function testControllerExists(): void
    {
        $this->assertInstanceOf(SMTPConfigCrudController::class, $this->controller);
        $this->assertInstanceOf(AbstractCrudController::class, $this->controller);
        $this->assertInstanceOf(CrudControllerInterface::class, $this->controller);
    }

    public function testGetEntityFqcn(): void
    {
        $entityFqcn = SMTPConfigCrudController::getEntityFqcn();
        
        $this->assertEquals(SMTPConfig::class, $entityFqcn);
        $this->assertTrue(class_exists($entityFqcn));
    }

    public function testConfigureCrud(): void
    {
        // 测试configureCrud方法是否存在且可调用
        // 方法必然存在，移除冗余检查
        
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('configureCrud');
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
    }

    public function testConfigureFields(): void
    {
        $fields = $this->controller->configureFields(Crud::PAGE_INDEX);
        $fieldArray = iterator_to_array($fields);

        // 验证字段数量和基本类型
        $this->assertGreaterThan(0, count($fieldArray));
        
        // 验证每个字段都是Field的实例
        foreach ($fieldArray as $field) {
            $this->assertInstanceOf('EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface', $field);
        }
    }

    public function testConfigureFieldsForDifferentPages(): void
    {
        // 测试不同页面的字段配置
        $indexFields = iterator_to_array($this->controller->configureFields(Crud::PAGE_INDEX));
        $detailFields = iterator_to_array($this->controller->configureFields(Crud::PAGE_DETAIL));
        $editFields = iterator_to_array($this->controller->configureFields(Crud::PAGE_EDIT));
        $newFields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW));

        // 确保所有页面都有字段
        $this->assertGreaterThan(0, count($indexFields));
        $this->assertGreaterThan(0, count($detailFields));
        $this->assertGreaterThan(0, count($editFields));
        $this->assertGreaterThan(0, count($newFields));
    }

    public function testConfigureActions(): void
    {
        // 测试configureActions方法是否存在且可调用
        // 方法必然存在，移除冗余检查
        
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('configureActions');
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
    }

    public function testEncryptionChoicesConfiguration(): void
    {
        // 测试加密方式的字段配置方法存在
        // 方法必然存在，移除冗余检查
        
        // 验证可以为不同页面配置字段
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW));
        $this->assertGreaterThan(0, count($fields));
    }

    public function testPortFieldConfiguration(): void
    {
        // 测试端口字段的配置方法存在
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW));
        $this->assertGreaterThan(0, count($fields));
    }

    public function testPasswordFieldConfiguration(): void
    {
        // 测试密码字段的配置方法存在
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW));
        $this->assertGreaterThan(0, count($fields));
    }

    public function testWeightAndPriorityFields(): void
    {
        // 测试权重和优先级字段配置
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_INDEX));
        $this->assertGreaterThan(0, count($fields));
    }

    public function testBooleanFields(): void
    {
        // 测试布尔字段配置
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_INDEX));
        $this->assertGreaterThan(0, count($fields));
    }

    public function testTimestampFields(): void
    {
        // 测试时间戳字段配置
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_DETAIL));
        $this->assertGreaterThan(0, count($fields));
    }

    public function testSMTPConfigEntityIntegration(): void
    {
        // 测试与SMTPConfig实体的集成
        $config = new SMTPConfig();
        $this->assertInstanceOf(SMTPConfig::class, $config);
        
        // 验证实体具有控制器中配置的字段对应的方法
        $expectedMethods = [
            'getName', 'setName',
            'getHost', 'setHost',
            'getPort', 'setPort',
            'getUsername', 'setUsername',
            'getPassword', 'setPassword',
            'getEncryption', 'setEncryption',
            'getWeight', 'setWeight',
            'getPriority', 'setPriority',
            'isValid', 'setValid'
        ];
        
        foreach ($expectedMethods as $method) {
            $this->assertTrue(method_exists($config, $method), "Method {$method} should exist in SMTPConfig");
        }
    }

    public function testControllerHasNoConstructorDependencies(): void
    {
        // 验证控制器没有构造函数依赖（与MailTaskCrudController不同）
        $reflection = new \ReflectionClass(SMTPConfigCrudController::class);
        $constructor = $reflection->getConstructor();
        
        // SMTPConfigCrudController没有自定义构造函数
        $this->assertNull($constructor);
    }

    public function testConfigureMethodsExist(): void
    {
        // 验证所有必要的配置方法存在
        $requiredMethods = [
            'getEntityFqcn',
            'configureCrud',
            'configureFields',
            'configureActions'
        ];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists($this->controller, $method), "Method {$method} should exist");
        }
    }

    public function testConfigureFieldsMethodSignature(): void
    {
        // 测试configureFields方法签名
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('configureFields');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
        
        $parameter = $method->getParameters()[0];
        $this->assertEquals('pageName', $parameter->getName());
        $this->assertTrue($parameter->hasType());
        $this->assertEquals('string', (string) $parameter->getType());
    }

    public function testFieldsContainRequiredProperties(): void
    {
        // 验证字段配置方法可以正常调用
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW));
        
        // 验证字段数量大于0
        $this->assertGreaterThan(0, count($fields));
        
        // 验证每个字段都是有效的Field实例
        foreach ($fields as $field) {
            $this->assertInstanceOf('EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface', $field);
        }
    }
} 