<?php

namespace Tourze\SMTPMailerBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;
use Tourze\SMTPMailerBundle\Repository\MailTaskRepository;

/**
 * @internal
 */
#[CoversClass(MailTaskRepository::class)]
#[RunTestsInSeparateProcesses]
final class MailTaskRepositoryTest extends AbstractRepositoryTestCase
{
    /**
     * @var MailTaskRepository
     * @phpstan-ignore doctrine.noGetRepositoryOutsideService,assign.propertyType
     */
    private MailTaskRepository $repository;

    protected function onSetUp(): void
    {
        // 清理数据库
        self::cleanDatabase();

        // 初始化repository
        // @phpstan-ignore doctrine.noGetRepositoryOutsideService,assign.propertyType
        $this->repository = self::getEntityManager()->getRepository(MailTask::class);
    }

    public function testRepositoryInitialization(): void
    {
        // 验证repository已在onSetUp中正确初始化
        $this->assertInstanceOf(MailTaskRepository::class, $this->repository);
    }

    public function testFindPendingTasks(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $pendingTask = new MailTask();
        $pendingTask->setFromEmail('test@example.com');
        $pendingTask->setToEmail('recipient@example.com');
        $pendingTask->setSubject('Test Subject');
        $pendingTask->setBody('Test Body');
        $pendingTask->setStatus(MailTaskStatus::PENDING);
        self::getEntityManager()->persist($pendingTask);

        $sentTask = new MailTask();
        $sentTask->setFromEmail('test@example.com');
        $sentTask->setToEmail('recipient@example.com');
        $sentTask->setSubject('Test Subject 2');
        $sentTask->setBody('Test Body 2');
        $sentTask->setStatus(MailTaskStatus::SENT);
        self::getEntityManager()->persist($sentTask);

        self::getEntityManager()->flush();

        // 执行查询
        $pendingTasks = $this->getRepository()->findPendingTasks();

        // 验证结果
        $this->assertCount(1, $pendingTasks);
        $this->assertEquals(MailTaskStatus::PENDING, $pendingTasks[0]->getStatus());
    }

    public function testFindByStatus(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 执行查询
        $pendingTasks = $this->getRepository()->findByStatus(MailTaskStatus::PENDING->value);
        $sentTasks = $this->getRepository()->findByStatus(MailTaskStatus::SENT->value);

        // 验证结果
        $this->assertCount(1, $pendingTasks);
        $this->assertCount(1, $sentTasks);
        $this->assertEquals(MailTaskStatus::PENDING, $pendingTasks[0]->getStatus());
        $this->assertEquals(MailTaskStatus::SENT, $sentTasks[0]->getStatus());
    }

    public function testFindByDateRange(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $taskInRange = new MailTask();
        $taskInRange->setFromEmail('test@example.com');
        $taskInRange->setToEmail('recipient@example.com');
        $taskInRange->setSubject('Test Subject');
        $taskInRange->setBody('Test Body');
        $taskInRange->setStatus(MailTaskStatus::PENDING);
        $taskInRange->setCreateTime(new \DateTimeImmutable('2024-01-15'));
        self::getEntityManager()->persist($taskInRange);

        $taskOutOfRange = new MailTask();
        $taskOutOfRange->setFromEmail('test2@example.com');
        $taskOutOfRange->setToEmail('recipient@example.com');
        $taskOutOfRange->setSubject('Test Subject 2');
        $taskOutOfRange->setBody('Test Body 2');
        $taskOutOfRange->setStatus(MailTaskStatus::PENDING);
        $taskOutOfRange->setCreateTime(new \DateTimeImmutable('2023-12-15'));
        self::getEntityManager()->persist($taskOutOfRange);

        self::getEntityManager()->flush();

        // 执行查询
        $tasksInRange = $this->getRepository()->findByDateRange($startDate, $endDate);

        // 验证结果
        $this->assertCount(1, $tasksInRange);
        $createTime = $tasksInRange[0]->getCreateTime();
        $this->assertNotNull($createTime);
        $this->assertEquals('2024-01-15', $createTime->format('Y-m-d'));
    }

    public function testSave(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        $task = new MailTask();
        $task->setFromEmail('test@example.com');
        $task->setToEmail('recipient@example.com');
        $task->setSubject('Test Subject');
        $task->setBody('Test Body');
        $task->setStatus(MailTaskStatus::PENDING);

        // 测试保存
        $this->getRepository()->save($task);

        // 验证实体已保存
        $this->assertNotNull($task->getId());

        // 清除EntityManager缓存，从数据库重新查询
        self::getEntityManager()->clear();
        $savedTask = $this->getRepository()->find($task->getId());

        $this->assertNotNull($savedTask);
        $this->assertEquals('test@example.com', $savedTask->getFromEmail());
    }

    public function testRemove(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        $task = new MailTask();
        $task->setFromEmail('test@example.com');
        $task->setToEmail('recipient@example.com');
        $task->setSubject('Test Subject');
        $task->setBody('Test Body');
        $task->setStatus(MailTaskStatus::PENDING);

        self::getEntityManager()->persist($task);
        self::getEntityManager()->flush();

        $taskId = $task->getId();

        // 测试删除
        $this->getRepository()->remove($task);

        // 验证实体已删除
        $this->assertNull($this->getRepository()->find($taskId));
    }

    public function testFindBySmtpConfig(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::PENDING);
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 执行查询 - 测试不存在的SMTP配置ID
        $results = $this->getRepository()->findBySmtpConfig(999);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(0, $results);
    }

    public function testFindScheduledTasks(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $pastTime = new \DateTimeImmutable('-1 hour');
        $futureTime = new \DateTimeImmutable('+1 hour');

        $scheduledTask = new MailTask();
        $scheduledTask->setFromEmail('scheduled@example.com');
        $scheduledTask->setToEmail('recipient@example.com');
        $scheduledTask->setSubject('Scheduled Subject');
        $scheduledTask->setBody('Scheduled Body');
        $scheduledTask->setStatus(MailTaskStatus::PENDING);
        $scheduledTask->setScheduledTime($pastTime);
        self::getEntityManager()->persist($scheduledTask);

        $futureTask = new MailTask();
        $futureTask->setFromEmail('future@example.com');
        $futureTask->setToEmail('recipient@example.com');
        $futureTask->setSubject('Future Subject');
        $futureTask->setBody('Future Body');
        $futureTask->setStatus(MailTaskStatus::PENDING);
        $futureTask->setScheduledTime($futureTime);
        self::getEntityManager()->persist($futureTask);

        $immediateTask = new MailTask();
        $immediateTask->setFromEmail('immediate@example.com');
        $immediateTask->setToEmail('recipient@example.com');
        $immediateTask->setSubject('Immediate Subject');
        $immediateTask->setBody('Immediate Body');
        $immediateTask->setStatus(MailTaskStatus::PENDING);
        self::getEntityManager()->persist($immediateTask);

        self::getEntityManager()->flush();

        // 执行查询
        $results = $this->getRepository()->findScheduledTasks();

        // 验证结果 - 应该只返回过去时间的计划任务
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('scheduled@example.com', $results[0]->getFromEmail());
    }

    public function testCountAssociationQueries(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task = new MailTask();
        $task->setFromEmail('test@example.com');
        $task->setToEmail('recipient@example.com');
        $task->setSubject('Test Subject');
        $task->setBody('Test Body');
        $task->setStatus(MailTaskStatus::PENDING);
        self::getEntityManager()->persist($task);

        self::getEntityManager()->flush();

        // 测试关联查询 - smtpConfig为null的记录数量
        $count = $this->getRepository()->count(['smtpConfig' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindByAssociationQueries(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试关联查询 - 查找smtpConfig为null的记录
        $results = $this->getRepository()->findBy(['smtpConfig' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }

    public function testFindByNullableFields(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setFromName('Test Name');
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // fromName 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询
        $results = $this->getRepository()->findBy(['fromName' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('test2@example.com', $results[0]->getFromEmail());
    }

    public function testCountNullableFields(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setFromName('Test Name');
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // fromName 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询
        $count = $this->getRepository()->count(['fromName' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindByWithMoreNullableFields(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setToName('Test To Name');
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // toName 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询 - toName
        $results = $this->getRepository()->findBy(['toName' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('test2@example.com', $results[0]->getFromEmail());
    }

    public function testCountWithMoreNullableFields(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setToName('Test To Name');
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // toName 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询 - toName
        $count = $this->getRepository()->count(['toName' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindByWithScheduledTimeNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setScheduledTime(new \DateTimeImmutable('+1 hour'));
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // scheduledTime 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询 - scheduledTime
        $results = $this->getRepository()->findBy(['scheduledTime' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('test2@example.com', $results[0]->getFromEmail());
    }

    public function testCountWithScheduledTimeNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setScheduledTime(new \DateTimeImmutable('+1 hour'));
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // scheduledTime 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询 - scheduledTime
        $count = $this->getRepository()->count(['scheduledTime' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindByWithCcNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setCc(['cc@example.com']);
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // cc 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询 - cc
        $results = $this->getRepository()->findBy(['cc' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('test2@example.com', $results[0]->getFromEmail());
    }

    public function testCountWithCcNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setCc(['cc@example.com']);
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // cc 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询 - cc
        $count = $this->getRepository()->count(['cc' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindByWithBccNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setBcc(['bcc@example.com']);
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // bcc 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询 - bcc
        $results = $this->getRepository()->findBy(['bcc' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('test2@example.com', $results[0]->getFromEmail());
    }

    public function testCountWithBccNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setBcc(['bcc@example.com']);
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // bcc 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询 - bcc
        $count = $this->getRepository()->count(['bcc' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindByWithAttachmentsNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setAttachments([['filename' => 'file1.txt', 'content_type' => 'text/plain']]);
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // attachments 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询 - attachments
        $results = $this->getRepository()->findBy(['attachments' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('test2@example.com', $results[0]->getFromEmail());
    }

    public function testCountWithAttachmentsNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setAttachments([['filename' => 'file1.txt', 'content_type' => 'text/plain']]);
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // attachments 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询 - attachments
        $count = $this->getRepository()->count(['attachments' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindByWithStatusMessageNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setStatusMessage('Some status message');
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // statusMessage 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询 - statusMessage
        $results = $this->getRepository()->findBy(['statusMessage' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('test2@example.com', $results[0]->getFromEmail());
    }

    public function testCountWithStatusMessageNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setStatusMessage('Some status message');
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // statusMessage 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询 - statusMessage
        $count = $this->getRepository()->count(['statusMessage' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindByWithSelectorStrategyNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setSelectorStrategy('round_robin');
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // selectorStrategy 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询 - selectorStrategy
        $results = $this->getRepository()->findBy(['selectorStrategy' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('test2@example.com', $results[0]->getFromEmail());
    }

    public function testCountWithSelectorStrategyNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        $task1->setSelectorStrategy('round_robin');
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::SENT);
        // selectorStrategy 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询 - selectorStrategy
        $count = $this->getRepository()->count(['selectorStrategy' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindByWithSentTimeNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::SENT);
        $task1->setSentTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::PENDING);
        // sentTime 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 IS NULL 查询 - sentTime
        $results = $this->getRepository()->findBy(['sentTime' => null]);

        // 验证结果
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('test2@example.com', $results[0]->getFromEmail());
    }

    public function testCountWithSentTimeNull(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('test1@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::SENT);
        $task1->setSentTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('test2@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::PENDING);
        // sentTime 保持 null
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 count IS NULL 查询 - sentTime
        $count = $this->getRepository()->count(['sentTime' => null]);

        // 验证结果
        $this->assertEquals(1, $count);
    }

    public function testFindOneByWithSortingLogic(): void
    {
        // 确保每个测试开始时数据库是干净的
        self::getEntityManager()->createQuery('DELETE FROM ' . MailTask::class)->execute();

        // 创建测试数据
        $task1 = new MailTask();
        $task1->setFromEmail('a@example.com');
        $task1->setToEmail('recipient1@example.com');
        $task1->setSubject('Test Subject 1');
        $task1->setBody('Test Body 1');
        $task1->setStatus(MailTaskStatus::PENDING);
        self::getEntityManager()->persist($task1);

        $task2 = new MailTask();
        $task2->setFromEmail('z@example.com');
        $task2->setToEmail('recipient2@example.com');
        $task2->setSubject('Test Subject 2');
        $task2->setBody('Test Body 2');
        $task2->setStatus(MailTaskStatus::PENDING);
        self::getEntityManager()->persist($task2);

        self::getEntityManager()->flush();

        // 测试 findOneBy 排序逻辑
        $result = $this->getRepository()->findOneBy(['status' => MailTaskStatus::PENDING], ['fromEmail' => 'ASC']);
        $this->assertInstanceOf(MailTask::class, $result);
        $this->assertEquals('a@example.com', $result->getFromEmail());

        $result = $this->getRepository()->findOneBy(['status' => MailTaskStatus::PENDING], ['fromEmail' => 'DESC']);
        $this->assertInstanceOf(MailTask::class, $result);
        $this->assertEquals('z@example.com', $result->getFromEmail());
    }

    protected function createNewEntity(): object
    {
        $entity = new MailTask();
        $entity->setFromEmail('test' . uniqid() . '@example.com');
        $entity->setToEmail('recipient@example.com');
        $entity->setSubject('Test Subject ' . uniqid());
        $entity->setBody('Test Body ' . uniqid());
        $entity->setStatus(MailTaskStatus::PENDING);

        return $entity;
    }

    protected function getRepository(): MailTaskRepository
    {
        return $this->repository;
    }
}
