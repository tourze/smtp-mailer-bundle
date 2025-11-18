<?php

namespace Tourze\SMTPMailerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouteCollection;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SMTPMailerBundle\Service\AttributeControllerLoader;

/**
 * AttributeControllerLoader 测试类
 *
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    private AttributeControllerLoader $loader;

    protected function onSetUp(): void
    {
        $this->loader = self::getService(AttributeControllerLoader::class);
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(AttributeControllerLoader::class, $this->loader);
    }

    /**
     * 测试 autoload 方法返回有效的 RouteCollection
     */
    public function testAutoload(): void
    {
        $routeCollection = $this->loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $routeCollection);

        // 验证返回的路由集合不为空（因为加载了 ProcessScheduledController）
        $this->assertGreaterThan(0, $routeCollection->count());

        $routes = $routeCollection->all();
        $this->assertNotEmpty($routes);
    }

    /**
     * 测试 load 方法通过调用 autoload 工作
     */
    public function testLoad(): void
    {
        $routeCollection = $this->loader->load('dummy_resource', 'dummy_type');

        // load 方法应该返回与 autoload 相同的结果
        $expectedCollection = $this->loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $routeCollection);
        $this->assertEquals($expectedCollection->count(), $routeCollection->count());
    }

    /**
     * 测试 supports 方法总是返回 false
     */
    public function testSupports(): void
    {
        // supports 方法根据实现总是返回 false
        $this->assertFalse($this->loader->supports('any_resource', 'any_type'));
        $this->assertFalse($this->loader->supports(null, null));
        $this->assertFalse($this->loader->supports('', ''));
        $this->assertFalse($this->loader->supports('test', 'annotation'));
    }
}
