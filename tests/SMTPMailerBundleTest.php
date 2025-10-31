<?php

declare(strict_types=1);

namespace Tourze\SMTPMailerBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\SMTPMailerBundle\SMTPMailerBundle;

/**
 * @internal
 */
#[CoversClass(SMTPMailerBundle::class)]
#[RunTestsInSeparateProcesses]
final class SMTPMailerBundleTest extends AbstractBundleTestCase
{
}
