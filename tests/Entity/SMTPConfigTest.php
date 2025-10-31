<?php

namespace Tourze\SMTPMailerBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * @internal
 */
#[CoversClass(SMTPConfig::class)]
final class SMTPConfigTest extends AbstractEntityTestCase
{
    protected function createEntity(): SMTPConfig
    {
        return new SMTPConfig();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'name' => ['name', 'test-smtp'],
            'host' => ['host', 'smtp.example.com'],
            'port' => ['port', 25],
            'username' => ['username', 'username'],
            'password' => ['password', 'password'],
            'encryption' => ['encryption', 'tls'],
            'timeout' => ['timeout', 60],
            'authMode' => ['authMode', 'login'],
            'weight' => ['weight', 5],
            'priority' => ['priority', 10],
            'valid' => ['valid', false],
        ];
    }

    public function testTimestampFields(): void
    {
        $config = $this->createEntity();
        $this->assertNull($config->getCreateTime());
        $this->assertNull($config->getUpdateTime());
    }

    public function testPreUpdateLifecycleCallback(): void
    {
        $config = $this->createEntity();
        $this->assertNull($config->getUpdateTime());

        $config->setName('Updated Name');
    }

    public function testGetDsnWithBasicConfig(): void
    {
        $config = $this->createEntity();
        $config->setHost('smtp.example.com');
        $config->setPort(25);

        $expected = 'smtp://smtp.example.com:25?encryption=tls';
        $this->assertSame($expected, $config->getDsn());
    }

    public function testGetDsnWithAuthentication(): void
    {
        $config = $this->createEntity();
        $config->setHost('smtp.example.com');
        $config->setPort(587);
        $config->setUsername('user@example.com');
        $config->setPassword('pass123');

        $expected = 'smtp://user%40example.com:pass123@smtp.example.com:587?encryption=tls';
        $this->assertSame($expected, $config->getDsn());
    }

    public function testGetDsnWithSpecialChars(): void
    {
        $config = $this->createEntity();
        $config->setHost('smtp.example.com');
        $config->setPort(587);
        $config->setUsername('user:special@example.com');
        $config->setPassword('pass with spaces');

        $expected = 'smtp://user%3Aspecial%40example.com:pass+with+spaces@smtp.example.com:587?encryption=tls';
        $this->assertSame($expected, $config->getDsn());
    }

    public function testGetDsnWithAllParameters(): void
    {
        $config = $this->createEntity();
        $config->setHost('smtp.example.com');
        $config->setPort(465);
        $config->setUsername('user@example.com');
        $config->setPassword('pass123');
        $config->setEncryption('ssl');
        $config->setAuthMode('login');
        $config->setTimeout(60);

        $expected = 'smtp://user%40example.com:pass123@smtp.example.com:465?encryption=ssl&auth_mode=login&timeout=60';
        $this->assertSame($expected, $config->getDsn());
    }

    public function testGetDsnWithNoEncryption(): void
    {
        $config = $this->createEntity();
        $config->setHost('smtp.example.com');
        $config->setPort(25);
        $config->setEncryption('none');

        $expected = 'smtp://smtp.example.com:25';
        $this->assertSame($expected, $config->getDsn());
    }

    public function testToString(): void
    {
        $config = $this->createEntity();
        $config->setName('Test SMTP');

        $this->assertSame('Test SMTP', $config->__toString());
    }
}
