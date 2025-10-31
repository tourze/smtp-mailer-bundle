<?php

namespace Tourze\SMTPMailerBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Repository\SMTPConfigRepository;

/**
 * @internal
 */
#[CoversClass(SMTPConfigRepository::class)]
#[RunTestsInSeparateProcesses]
final class SMTPConfigRepositoryTest extends AbstractRepositoryTestCase
{
    private SMTPConfigRepository $repository;

    protected function onSetUp(): void
    {
        // 清理数据库
        self::cleanDatabase();
    }

    public function testRepositoryInitialization(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;
    }

    public function testFindAllEnabled(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $enabledConfig = new SMTPConfig();
        $enabledConfig->setName('enabled-config');
        $enabledConfig->setHost('smtp.example.com');
        $enabledConfig->setPort(587);
        $enabledConfig->setUsername('test@example.com');
        $enabledConfig->setPassword('password');
        $enabledConfig->setValid(true);
        self::getEntityManager()->persist($enabledConfig);

        $disabledConfig = new SMTPConfig();
        $disabledConfig->setName('disabled-config');
        $disabledConfig->setHost('smtp2.example.com');
        $disabledConfig->setPort(587);
        $disabledConfig->setUsername('test2@example.com');
        $disabledConfig->setPassword('password');
        $disabledConfig->setValid(false);
        self::getEntityManager()->persist($disabledConfig);

        self::getEntityManager()->flush();

        // 执行查询
        $enabledConfigs = $this->repository->findAllEnabled();

        // 验证结果
        $this->assertCount(1, $enabledConfigs);
        $this->assertTrue($enabledConfigs[0]->isValid());
        $this->assertEquals('smtp.example.com', $enabledConfigs[0]->getHost());
    }

    public function testFindAllEnabledWithWeight(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $config1 = new SMTPConfig();
        $config1->setName('weight-config-1');
        $config1->setHost('smtp1.example.com');
        $config1->setPort(587);
        $config1->setUsername('test1@example.com');
        $config1->setPassword('password');
        $config1->setValid(true);
        $config1->setWeight(10);
        self::getEntityManager()->persist($config1);

        $config2 = new SMTPConfig();
        $config2->setName('weight-config-2');
        $config2->setHost('smtp2.example.com');
        $config2->setPort(587);
        $config2->setUsername('test2@example.com');
        $config2->setPassword('password');
        $config2->setValid(true);
        $config2->setWeight(20);
        self::getEntityManager()->persist($config2);

        self::getEntityManager()->flush();

        // 执行查询
        $configs = $this->repository->findAllEnabledWithWeight();

        // 验证结果 - 应该按权重降序排列
        $this->assertCount(2, $configs);
        $this->assertEquals(20, $configs[0]->getWeight());
        $this->assertEquals(10, $configs[1]->getWeight());
    }

    public function testFindAllEnabledByPriority(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $config1 = new SMTPConfig();
        $config1->setName('priority-config-1');
        $config1->setHost('smtp1.example.com');
        $config1->setPort(587);
        $config1->setUsername('test1@example.com');
        $config1->setPassword('password');
        $config1->setValid(true);
        $config1->setPriority(5);
        self::getEntityManager()->persist($config1);

        $config2 = new SMTPConfig();
        $config2->setName('priority-config-2');
        $config2->setHost('smtp2.example.com');
        $config2->setPort(587);
        $config2->setUsername('test2@example.com');
        $config2->setPassword('password');
        $config2->setValid(true);
        $config2->setPriority(10);
        self::getEntityManager()->persist($config2);

        self::getEntityManager()->flush();

        // 执行查询
        $configs = $this->repository->findAllEnabledByPriority();

        // 验证结果 - 应该按优先级降序排列
        $this->assertCount(2, $configs);
        $this->assertEquals(10, $configs[0]->getPriority());
        $this->assertEquals(5, $configs[1]->getPriority());
    }

    public function testCountEnabled(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $config1 = new SMTPConfig();
        $config1->setName('count-enabled-config-1');
        $config1->setHost('smtp1.example.com');
        $config1->setPort(587);
        $config1->setUsername('test1@example.com');
        $config1->setPassword('password');
        $config1->setValid(true);
        self::getEntityManager()->persist($config1);

        $config2 = new SMTPConfig();
        $config2->setName('count-disabled-config-2');
        $config2->setHost('smtp2.example.com');
        $config2->setPort(587);
        $config2->setUsername('test2@example.com');
        $config2->setPassword('password');
        $config2->setValid(false);
        self::getEntityManager()->persist($config2);

        $config3 = new SMTPConfig();
        $config3->setName('count-enabled-config-3');
        $config3->setHost('smtp3.example.com');
        $config3->setPort(587);
        $config3->setUsername('test3@example.com');
        $config3->setPassword('password');
        $config3->setValid(true);
        self::getEntityManager()->persist($config3);

        self::getEntityManager()->flush();

        // 执行查询
        $count = $this->repository->countEnabled();

        // 验证结果
        $this->assertEquals(2, $count);
    }

    public function testSave(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        $config = new SMTPConfig();
        $config->setName('save-test-config');
        $config->setHost('smtp.example.com');
        $config->setPort(587);
        $config->setUsername('test@example.com');
        $config->setPassword('password');
        $config->setValid(true);

        // 测试保存
        $this->repository->save($config);

        // 验证实体已保存
        $this->assertNotNull($config->getId());

        // 清除EntityManager缓存，从数据库重新查询
        self::getEntityManager()->clear();
        $savedConfig = $this->repository->find($config->getId());

        $this->assertNotNull($savedConfig);
        $this->assertEquals('smtp.example.com', $savedConfig->getHost());
    }

    public function testRemove(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        $config = new SMTPConfig();
        $config->setName('remove-test-config');
        $config->setHost('smtp.example.com');
        $config->setPort(587);
        $config->setUsername('test@example.com');
        $config->setPassword('password');
        $config->setValid(true);

        self::getEntityManager()->persist($config);
        self::getEntityManager()->flush();

        $configId = $config->getId();

        // 测试删除
        $this->repository->remove($config);

        // 验证实体已删除
        $this->assertNull($this->repository->find($configId));
    }

    public function testFindByNullableFields(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $config1 = new SMTPConfig();
        $config1->setName('config-with-password');
        $config1->setHost('smtp1.example.com');
        $config1->setPort(587);
        $config1->setUsername('test1@example.com');
        $config1->setPassword('password');
        $config1->setValid(true);
        self::getEntityManager()->persist($config1);

        $config2 = new SMTPConfig();
        $config2->setName('config-without-password');
        $config2->setHost('smtp2.example.com');
        $config2->setPort(587);
        $config2->setUsername('test2@example.com');
        // password 保持 null
        $config2->setValid(true);
        self::getEntityManager()->persist($config2);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询
        $results = $this->repository->findBy(['password' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('config-without-password', $results[0]->getName());
    }

    public function testCountNullableFields(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $config1 = new SMTPConfig();
        $config1->setName('config-with-auth-mode');
        $config1->setHost('smtp1.example.com');
        $config1->setPort(587);
        $config1->setUsername('test1@example.com');
        $config1->setPassword('password');
        $config1->setAuthMode('login');
        $config1->setValid(true);
        self::getEntityManager()->persist($config1);

        $config2 = new SMTPConfig();
        $config2->setName('config-without-auth-mode');
        $config2->setHost('smtp2.example.com');
        $config2->setPort(587);
        $config2->setUsername('test2@example.com');
        $config2->setPassword('password');
        // authMode 保持 null
        $config2->setValid(true);
        self::getEntityManager()->persist($config2);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询
        $count = $this->repository->count(['authMode' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindOneByWithSortingLogic(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $config1 = new SMTPConfig();
        $config1->setName('Config A');
        $config1->setHost('smtp1.example.com');
        $config1->setPort(587);
        $config1->setUsername('user1@example.com');
        $config1->setPassword('password1');
        $config1->setValid(true);
        self::getEntityManager()->persist($config1);

        $config2 = new SMTPConfig();
        $config2->setName('Config Z');
        $config2->setHost('smtp2.example.com');
        $config2->setPort(587);
        $config2->setUsername('user2@example.com');
        $config2->setPassword('password2');
        $config2->setValid(true);
        self::getEntityManager()->persist($config2);

        self::getEntityManager()->flush();

        // 测试 findOneBy 排序逻辑
        $result = $this->repository->findOneBy(['valid' => true], ['name' => 'ASC']);
        $this->assertInstanceOf(SMTPConfig::class, $result);
        $this->assertEquals('Config A', $result->getName());

        $result = $this->repository->findOneBy(['valid' => true], ['name' => 'DESC']);
        $this->assertInstanceOf(SMTPConfig::class, $result);
        $this->assertEquals('Config Z', $result->getName());
    }

    public function testFindByUsernameNull(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $configWithUsername = new SMTPConfig();
        $configWithUsername->setName('Config With Username');
        $configWithUsername->setHost('smtp1.example.com');
        $configWithUsername->setPort(587);
        $configWithUsername->setUsername('user@example.com');
        $configWithUsername->setPassword('password');
        $configWithUsername->setValid(true);
        self::getEntityManager()->persist($configWithUsername);

        $configWithoutUsername = new SMTPConfig();
        $configWithoutUsername->setName('Config Without Username');
        $configWithoutUsername->setHost('smtp2.example.com');
        $configWithoutUsername->setPort(587);
        // username 保持 null
        $configWithoutUsername->setPassword('password');
        $configWithoutUsername->setValid(true);
        self::getEntityManager()->persist($configWithoutUsername);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询 - username
        $results = $this->repository->findBy(['username' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Config Without Username', $results[0]->getName());
    }

    public function testCountUsernameNull(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $configWithUsername = new SMTPConfig();
        $configWithUsername->setName('Config With Username');
        $configWithUsername->setHost('smtp1.example.com');
        $configWithUsername->setPort(587);
        $configWithUsername->setUsername('user@example.com');
        $configWithUsername->setPassword('password');
        $configWithUsername->setValid(true);
        self::getEntityManager()->persist($configWithUsername);

        $configWithoutUsername = new SMTPConfig();
        $configWithoutUsername->setName('Config Without Username');
        $configWithoutUsername->setHost('smtp2.example.com');
        $configWithoutUsername->setPort(587);
        // username 保持 null
        $configWithoutUsername->setPassword('password');
        $configWithoutUsername->setValid(true);
        self::getEntityManager()->persist($configWithoutUsername);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询 - username
        $count = $this->repository->count(['username' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindByPasswordNull(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $configWithPassword = new SMTPConfig();
        $configWithPassword->setName('Config With Password');
        $configWithPassword->setHost('smtp1.example.com');
        $configWithPassword->setPort(587);
        $configWithPassword->setUsername('user@example.com');
        $configWithPassword->setPassword('password');
        $configWithPassword->setValid(true);
        self::getEntityManager()->persist($configWithPassword);

        $configWithoutPassword = new SMTPConfig();
        $configWithoutPassword->setName('Config Without Password');
        $configWithoutPassword->setHost('smtp2.example.com');
        $configWithoutPassword->setPort(587);
        $configWithoutPassword->setUsername('user@example.com');
        // password 保持 null
        $configWithoutPassword->setValid(true);
        self::getEntityManager()->persist($configWithoutPassword);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询 - password
        $results = $this->repository->findBy(['password' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Config Without Password', $results[0]->getName());
    }

    public function testCountPasswordNull(): void
    {
        $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
        $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
        $this->repository = $repository;

        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . SMTPConfig::class)->execute();

        // 创建测试数据
        $configWithPassword = new SMTPConfig();
        $configWithPassword->setName('Config With Password');
        $configWithPassword->setHost('smtp1.example.com');
        $configWithPassword->setPort(587);
        $configWithPassword->setUsername('user@example.com');
        $configWithPassword->setPassword('password');
        $configWithPassword->setValid(true);
        self::getEntityManager()->persist($configWithPassword);

        $configWithoutPassword = new SMTPConfig();
        $configWithoutPassword->setName('Config Without Password');
        $configWithoutPassword->setHost('smtp2.example.com');
        $configWithoutPassword->setPort(587);
        $configWithoutPassword->setUsername('user@example.com');
        // password 保持 null
        $configWithoutPassword->setValid(true);
        self::getEntityManager()->persist($configWithoutPassword);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询 - password
        $count = $this->repository->count(['password' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    protected function createNewEntity(): object
    {
        $entity = new SMTPConfig();
        $entity->setName('test_config_' . uniqid());
        $entity->setHost('smtp' . uniqid() . '.example.com');
        $entity->setPort(587);
        $entity->setUsername('test' . uniqid() . '@example.com');
        $entity->setPassword('password' . uniqid());
        $entity->setValid(true);

        return $entity;
    }

    protected function getRepository(): SMTPConfigRepository
    {
        if (!isset($this->repository)) {
            $repository = self::getEntityManager()->getRepository(SMTPConfig::class);
            $this->assertInstanceOf(SMTPConfigRepository::class, $repository);
            $this->repository = $repository;
        }

        return $this->repository;
    }
}
