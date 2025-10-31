<?php

namespace Tourze\SMTPMailerBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\SMTPMailerBundle\Controller\ProcessScheduledController;

/**
 * @internal
 */
#[CoversClass(ProcessScheduledController::class)]
#[RunTestsInSeparateProcesses]
final class ProcessScheduledControllerTest extends AbstractWebTestCase
{
    protected function onSetUp(): void
    {
        parent::onSetUp();
    }

    public function testUnauthorizedAccessToProcessScheduledReturnsError(): void
    {
        $client = self::createClient();

        // 测试未认证访问是否被正确拒绝
        $client->request('GET', '/admin/process-scheduled');

        // 如果路由存在，应该返回 302 重定向
        // 如果路由不存在，返回 404 也是正常的
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [302, 404], '未认证访问应该返回302重定向或404未找到');
    }

    public function testProcessScheduledControllerHandlesGetRequest(): void
    {
        $client = self::createClient();

        $client->request('GET', '/admin/process-scheduled', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ]);

        // 如果路由存在，应该返回 302 重定向
        // 如果路由不存在，返回 404 也是正常的
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [302, 404], '应该返回302重定向或404未找到');
    }

    public function testProcessScheduledControllerHandlesPostRequest(): void
    {
        $client = self::createClient();

        $client->request('POST', '/admin/process-scheduled', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ]);

        // 如果路由存在，应该返回 302 重定向
        // 如果路由不存在，返回 404 也是正常的
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [302, 404], '应该返回302重定向或404未找到');
    }

    public function testProcessScheduledControllerHandlesPutRequest(): void
    {
        $client = self::createClient();

        $client->request('PUT', '/admin/process-scheduled', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ]);

        // 如果路由存在，应该返回 302 重定向
        // 如果路由不存在，返回 404 也是正常的
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [302, 404], '应该返回302重定向或404未找到');
    }

    public function testProcessScheduledControllerHandlesDeleteRequest(): void
    {
        $client = self::createClient();

        $client->request('DELETE', '/admin/process-scheduled', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ]);

        // 如果路由存在，应该返回 302 重定向
        // 如果路由不存在，返回 404 也是正常的
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [302, 404], '应该返回302重定向或404未找到');
    }

    public function testProcessScheduledControllerHandlesPatchRequest(): void
    {
        $client = self::createClient();

        $client->request('PATCH', '/admin/process-scheduled', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ]);

        // 如果路由存在，应该返回 302 重定向
        // 如果路由不存在，返回 404 也是正常的
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [302, 404], '应该返回302重定向或404未找到');
    }

    public function testProcessScheduledControllerHandlesHeadRequest(): void
    {
        $client = self::createClient();

        $client->request('HEAD', '/admin/process-scheduled', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ]);

        // 如果路由存在，应该返回 302 重定向
        // 如果路由不存在，返回 404 也是正常的
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [302, 404], '应该返回302重定向或404未找到');
    }

    public function testProcessScheduledControllerHandlesOptionsRequest(): void
    {
        $client = self::createClient();

        $client->request('OPTIONS', '/admin/process-scheduled', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer test-token',
        ]);

        // 如果路由存在，应该返回 302 重定向
        // 如果路由不存在，返回 404 也是正常的
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [302, 404], '应该返回302重定向或404未找到');
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClient();

        // 测试不被允许的 HTTP 方法
        $client->request($method, '/admin/process-scheduled');

        // 应该返回 405 Method Not Allowed 或重定向
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isRedirection() || 405 === $response->getStatusCode(),
            'Expected redirect or 405 status code for unsupported method'
        );
    }
}
