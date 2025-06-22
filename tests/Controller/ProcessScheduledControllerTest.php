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

    public function testProcessScheduledWithTasksProcessed(): void
    {
        // 创建请求mock
        $request = $this->createMock(Request::class);
        $headerBag = $this->createMock(\Symfony\Component\HttpFoundation\HeaderBag::class);
        
        $request->headers = $headerBag;
        $headerBag->expects($this->once())
            ->method('get')
            ->with('referer')
            ->willReturn('http://example.com/admin/some-page');
        
        // 配置mailer service返回处理了5个任务
        $this->mailerService->expects($this->once())
            ->method('processScheduledTasks')
            ->willReturn(5);
        
        // 创建部分mock的controller以测试flash消息和重定向
        $controller = $this->getMockBuilder(ProcessScheduledController::class)
            ->onlyMethods(['addFlash', 'redirect'])
            ->getMock();
        
        $controller->expects($this->once())
            ->method('addFlash')
            ->with('success', '已处理 5 封计划发送的邮件');

        $controller->expects($this->once())
            ->method('redirect')
            ->with('http://example.com/admin/some-page')
            ->willReturn(new RedirectResponse('http://example.com/admin/some-page'));

        // 调用方法
        $result = $controller($request, $this->mailerService);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testProcessScheduledWithNoTasks(): void
    {
        // 创建请求mock
        $request = $this->createMock(Request::class);
        $headerBag = $this->createMock(\Symfony\Component\HttpFoundation\HeaderBag::class);
        
        $request->headers = $headerBag;
        $headerBag->expects($this->once())
            ->method('get')
            ->with('referer')
            ->willReturn('http://example.com/admin/dashboard');
        
        // 配置mailer service返回处理了0个任务
        $this->mailerService->expects($this->once())
            ->method('processScheduledTasks')
            ->willReturn(0);
        
        // 创建部分mock的controller以测试flash消息和重定向
        $controller = $this->getMockBuilder(ProcessScheduledController::class)
            ->onlyMethods(['addFlash', 'redirect'])
            ->getMock();
        
        $controller->expects($this->once())
            ->method('addFlash')
            ->with('info', '没有需要处理的计划邮件');
        
        $controller->expects($this->once())
            ->method('redirect')
            ->with('http://example.com/admin/dashboard')
            ->willReturn(new RedirectResponse('http://example.com/admin/dashboard'));

        // 调用方法
        $result = $controller($request, $this->mailerService);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testProcessScheduledWithNonAdminReferer(): void
    {
        // 创建请求mock
        $request = $this->createMock(Request::class);
        $headerBag = $this->createMock(\Symfony\Component\HttpFoundation\HeaderBag::class);
        
        $request->headers = $headerBag;
        $headerBag->expects($this->once())
            ->method('get')
            ->with('referer')
            ->willReturn('http://example.com/public/page');
        
        // 配置mailer service返回处理了1个任务
        $this->mailerService->expects($this->once())
            ->method('processScheduledTasks')
            ->willReturn(1);
        
        // 创建部分mock的controller以测试flash消息和重定向
        $controller = $this->getMockBuilder(ProcessScheduledController::class)
            ->onlyMethods(['addFlash', 'redirectToRoute'])
            ->getMock();
        
        $controller->expects($this->once())
            ->method('addFlash')
            ->with('success', '已处理 1 封计划发送的邮件');
        
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('smtp_mailer_admin')
            ->willReturn(new RedirectResponse('/admin'));

        // 调用方法
        $result = $controller($request, $this->mailerService);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testProcessScheduledWithNoReferer(): void
    {
        // 创建请求mock
        $request = $this->createMock(Request::class);
        $headerBag = $this->createMock(\Symfony\Component\HttpFoundation\HeaderBag::class);
        
        $request->headers = $headerBag;
        $headerBag->expects($this->once())
            ->method('get')
            ->with('referer')
            ->willReturn(null);
        
        // 配置mailer service返回处理了0个任务
        $this->mailerService->expects($this->once())
            ->method('processScheduledTasks')
            ->willReturn(0);
        
        // 创建部分mock的controller以测试flash消息和重定向
        $controller = $this->getMockBuilder(ProcessScheduledController::class)
            ->onlyMethods(['addFlash', 'redirectToRoute'])
            ->getMock();
        
        $controller->expects($this->once())
            ->method('addFlash')
            ->with('info', '没有需要处理的计划邮件');
        
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('smtp_mailer_admin')
            ->willReturn(new RedirectResponse('/admin'));

        // 调用方法
        $result = $controller($request, $this->mailerService);

        $this->assertInstanceOf(RedirectResponse::class, $result);
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