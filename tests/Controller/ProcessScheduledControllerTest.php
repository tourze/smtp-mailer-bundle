<?php

namespace Tourze\SMTPMailerBundle\Tests\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\SMTPMailerBundle\Controller\ProcessScheduledController;
use Tourze\SMTPMailerBundle\Service\SMTPMailerService;

class ProcessScheduledControllerTest extends TestCase
{
    private ProcessScheduledController $controller;
    private SMTPMailerService&MockObject $mailerService;

    protected function setUp(): void
    {
        $this->mailerService = $this->createMock(SMTPMailerService::class);
        $this->controller = new ProcessScheduledController();
    }

    public function testControllerExists(): void
    {
        $this->assertInstanceOf(ProcessScheduledController::class, $this->controller);
        $this->assertInstanceOf(AbstractController::class, $this->controller);
    }

    public function testInvokeMethodExists(): void
    {
        // __invoke 方法必然存在于控制器中
        
        $reflection = new \ReflectionClass(ProcessScheduledController::class);
        $method = $reflection->getMethod('__invoke');
        $this->assertTrue($method->isPublic());
        
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('request', $parameters[0]->getName());
        $this->assertEquals('mailerService', $parameters[1]->getName());
    }





    public function testRouteAttributes(): void
    {
        $reflection = new \ReflectionClass(ProcessScheduledController::class);
        $method = $reflection->getMethod('__invoke');
        
        $attributes = $method->getAttributes(Route::class);
        $this->assertCount(1, $attributes);
        
        $route = $attributes[0]->newInstance();
        $this->assertEquals('/admin/process-scheduled', $route->getPath());
        $this->assertEquals('smtp_mailer_process_scheduled', $route->getName());
    }

    public function testMethodReturnType(): void
    {
        $reflection = new \ReflectionClass(ProcessScheduledController::class);
        $method = $reflection->getMethod('__invoke');
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('Symfony\Component\HttpFoundation\Response', (string) $returnType);
    }

    public function testParameterTypes(): void
    {
        $reflection = new \ReflectionClass(ProcessScheduledController::class);
        $method = $reflection->getMethod('__invoke');
        
        $parameters = $method->getParameters();
        
        // 检查Request参数
        $requestParam = $parameters[0];
        $this->assertEquals('request', $requestParam->getName());
        $requestType = $requestParam->getType();
        $this->assertNotNull($requestType);
        $this->assertEquals('Symfony\Component\HttpFoundation\Request', (string) $requestType);
        
        // 检查SMTPMailerService参数
        $serviceParam = $parameters[1];
        $this->assertEquals('mailerService', $serviceParam->getName());
        $serviceType = $serviceParam->getType();
        $this->assertNotNull($serviceType);
        $this->assertEquals('Tourze\SMTPMailerBundle\Service\SMTPMailerService', (string) $serviceType);
    }

    public function testServiceDependency(): void
    {
        // 测试服务依赖是否正确注入
        // mailerService 的 processScheduledTasks 方法已经移除冗余检查
        
        $reflection = new \ReflectionMethod($this->mailerService::class, 'processScheduledTasks');
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('int', (string) $returnType);
    }
}