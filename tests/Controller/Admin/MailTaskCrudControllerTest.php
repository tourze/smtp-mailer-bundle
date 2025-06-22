<?php

namespace Tourze\SMTPMailerBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Controller\Admin\MailTaskCrudController;
use Tourze\SMTPMailerBundle\Entity\MailTask;

class MailTaskCrudControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(MailTaskCrudController::class));
        
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        $this->assertTrue($reflection->isSubclassOf(AbstractCrudController::class));
        $this->assertTrue($reflection->implementsInterface(CrudControllerInterface::class));
    }

    public function testGetEntityFqcn(): void
    {
        $entityFqcn = MailTaskCrudController::getEntityFqcn();
        
        $this->assertEquals(MailTask::class, $entityFqcn);
        $this->assertTrue(class_exists($entityFqcn));
    }

    public function testConstructorSignature(): void
    {
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        
        $parameters = $constructor->getParameters();
        $this->assertCount(3, $parameters);
        
        $this->assertEquals('selectorService', $parameters[0]->getName());
        $this->assertEquals('mailerService', $parameters[1]->getName());
        $this->assertEquals('adminUrlGenerator', $parameters[2]->getName());
    }

    public function testConfigureCrudMethodExists(): void
    {
        // 方法必然存在，移除冗余检查
        
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        $method = $reflection->getMethod('configureCrud');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
        
        $parameter = $method->getParameters()[0];
        $this->assertEquals('crud', $parameter->getName());
    }

    public function testConfigureFieldsMethodExists(): void
    {
        // 方法必然存在，移除冗余检查
        
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        $method = $reflection->getMethod('configureFields');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
        
        $parameter = $method->getParameters()[0];
        $this->assertEquals('pageName', $parameter->getName());
        $this->assertTrue($parameter->hasType());
        $this->assertEquals('string', (string) $parameter->getType());
    }

    public function testConfigureActionsMethodExists(): void
    {
        // 方法必然存在，移除冗余检查
        
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        $method = $reflection->getMethod('configureActions');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
    }

    public function testResendActionMethodExists(): void
    {
        // 方法必然存在，移除冗余检查
        
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        $method = $reflection->getMethod('resendAction');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
        
        $parameter = $method->getParameters()[0];
        $this->assertEquals('context', $parameter->getName());
    }

    public function testControllerImplementsAllRequiredMethods(): void
    {
        $requiredMethods = [
            'getEntityFqcn',
            'configureCrud',
            'configureFields',
            'configureActions',
            'resendAction'
        ];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists(MailTaskCrudController::class, $method), "Method {$method} should exist");
        }
    }

    public function testControllerUsesCorrectNamespace(): void
    {
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        $this->assertEquals('Tourze\SMTPMailerBundle\Controller\Admin', $reflection->getNamespaceName());
    }

    public function testControllerIsInstantiable(): void
    {
        // 验证类可以实例化（虽然需要依赖）
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        $this->assertTrue($reflection->isInstantiable());
        $this->assertFalse($reflection->isAbstract());
    }

    public function testEntityMailTaskExists(): void
    {
        // 验证相关的实体类存在
        $this->assertTrue(class_exists(MailTask::class));
        
        $entityReflection = new \ReflectionClass(MailTask::class);
        $this->assertEquals('Tourze\SMTPMailerBundle\Entity', $entityReflection->getNamespaceName());
    }

    public function testControllerHasCorrectAttributes(): void
    {
        // 测试控制器类是否有正确的属性
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        $attributes = $reflection->getAttributes();
        
        // 检查是否有AdminCrud属性
        $hasAdminCrudAttribute = false;
        foreach ($attributes as $attribute) {
            if (str_contains($attribute->getName(), 'AdminCrud')) {
                $hasAdminCrudAttribute = true;
                break;
            }
        }
        
        $this->assertTrue($hasAdminCrudAttribute, 'Controller should have AdminCrud attribute');
    }

    public function testResendActionHasCorrectAttributes(): void
    {
        // 测试resendAction方法是否有正确的属性
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        $method = $reflection->getMethod('resendAction');
        
        $attributes = $method->getAttributes();
        $this->assertGreaterThan(0, count($attributes), 'resendAction should have attributes');
        
        // 检查是否有AdminAction属性
        $hasAdminActionAttribute = false;
        foreach ($attributes as $attribute) {
            if (str_contains($attribute->getName(), 'AdminAction')) {
                $hasAdminActionAttribute = true;
                break;
            }
        }
        
        $this->assertTrue($hasAdminActionAttribute, 'resendAction should have AdminAction attribute');
    }

    public function testControllerInheritanceChain(): void
    {
        // 测试继承链
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        
        // 应该继承自AbstractCrudController
        $this->assertTrue($reflection->isSubclassOf(AbstractCrudController::class));
        
        // AbstractCrudController应该实现CrudControllerInterface
        $abstractReflection = new \ReflectionClass(AbstractCrudController::class);
        $this->assertTrue($abstractReflection->implementsInterface(CrudControllerInterface::class));
    }

    public function testMethodReturnTypes(): void
    {
        $reflection = new \ReflectionClass(MailTaskCrudController::class);
        
        // getEntityFqcn应该返回string
        $getEntityMethod = $reflection->getMethod('getEntityFqcn');
        $this->assertTrue($getEntityMethod->hasReturnType());
        $this->assertEquals('string', (string) $getEntityMethod->getReturnType());
        
        // resendAction应该返回RedirectResponse
        $resendMethod = $reflection->getMethod('resendAction');
        $this->assertTrue($resendMethod->hasReturnType());
    }

    public function testControllerDependencies(): void
    {
        // 验证控制器依赖的类都存在
        $dependencies = [
            'Tourze\SMTPMailerBundle\Service\SMTPSelectorService',
            'Tourze\SMTPMailerBundle\Service\SMTPMailerService',
            'EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator'
        ];
        
        foreach ($dependencies as $dependency) {
            $this->assertTrue(class_exists($dependency), "Dependency {$dependency} should exist");
        }
    }
} 