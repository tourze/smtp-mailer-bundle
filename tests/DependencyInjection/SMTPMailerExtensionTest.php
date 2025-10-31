<?php

declare(strict_types=1);

namespace Tourze\SMTPMailerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SMTPMailerBundle\DependencyInjection\SMTPMailerExtension;

/**
 * @internal
 */
#[CoversClass(SMTPMailerExtension::class)]
final class SMTPMailerExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testCanBeInstantiated(): void
    {
        $extension = new SMTPMailerExtension();
        $this->assertInstanceOf(SMTPMailerExtension::class, $extension);
    }

    public function testLoadLoadsServicesConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $extension = new SMTPMailerExtension();

        $configs = [];
        $extension->load($configs, $container);

        $this->assertTrue($container->isTrackingResources());
    }
}
