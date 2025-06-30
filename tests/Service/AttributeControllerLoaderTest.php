<?php

namespace Tourze\SMTPMailerBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Service\AttributeControllerLoader;

/**
 * AttributeControllerLoader 测试类
 */
class AttributeControllerLoaderTest extends TestCase
{
    private AttributeControllerLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new AttributeControllerLoader();
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(AttributeControllerLoader::class, $this->loader);
    }
}
