<?php

namespace Tourze\SMTPMailerBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

class SMTPConfigTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $config = new SMTPConfig();
        
        $config->setName('test-smtp');
        $this->assertSame('test-smtp', $config->getName());
        
        $config->setHost('smtp.example.com');
        $this->assertSame('smtp.example.com', $config->getHost());
        
        $config->setPort(25);
        $this->assertSame(25, $config->getPort());
        
        $config->setUsername('username');
        $this->assertSame('username', $config->getUsername());
        
        $config->setPassword('password');
        $this->assertSame('password', $config->getPassword());
        
        $config->setEncryption('tls');
        $this->assertSame('tls', $config->getEncryption());
        
        $config->setTimeout(60);
        $this->assertSame(60, $config->getTimeout());
        
        $config->setAuthMode('login');
        $this->assertSame('login', $config->getAuthMode());
        
        $config->setWeight(5);
        $this->assertSame(5, $config->getWeight());
        
        $config->setPriority(10);
        $this->assertSame(10, $config->getPriority());
        
        $config->setEnabled(false);
        $this->assertFalse($config->isEnabled());
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getCreatedAt());
        $this->assertNull($config->getUpdatedAt());
    }
    
    public function testPreUpdateLifecycleCallback(): void
    {
        $config = new SMTPConfig();
        $this->assertNull($config->getUpdatedAt());
        
        $config->setUpdatedAtValue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getUpdatedAt());
    }
    
    public function testGetDsn_WithBasicConfig(): void
    {
        $config = new SMTPConfig();
        $config->setHost('smtp.example.com');
        $config->setPort(25);
        
        $expected = 'smtp://smtp.example.com:25?encryption=tls';
        $this->assertSame($expected, $config->getDsn());
    }
    
    public function testGetDsn_WithAuthentication(): void
    {
        $config = new SMTPConfig();
        $config->setHost('smtp.example.com');
        $config->setPort(587);
        $config->setUsername('user@example.com');
        $config->setPassword('pass123');
        
        $expected = 'smtp://user%40example.com:pass123@smtp.example.com:587?encryption=tls';
        $this->assertSame($expected, $config->getDsn());
    }
    
    public function testGetDsn_WithSpecialChars(): void
    {
        $config = new SMTPConfig();
        $config->setHost('smtp.example.com');
        $config->setPort(587);
        $config->setUsername('user:special@example.com');
        $config->setPassword('pass with spaces');
        
        $expected = 'smtp://user%3Aspecial%40example.com:pass+with+spaces@smtp.example.com:587?encryption=tls';
        $this->assertSame($expected, $config->getDsn());
    }
    
    public function testGetDsn_WithAllParameters(): void
    {
        $config = new SMTPConfig();
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
    
    public function testGetDsn_WithNoEncryption(): void
    {
        $config = new SMTPConfig();
        $config->setHost('smtp.example.com');
        $config->setPort(25);
        $config->setEncryption('none');
        
        $expected = 'smtp://smtp.example.com:25';
        $this->assertSame($expected, $config->getDsn());
    }
    
    public function testToString(): void
    {
        $config = new SMTPConfig();
        $config->setName('Test SMTP');
        
        $this->assertSame('Test SMTP', $config->__toString());
    }
} 