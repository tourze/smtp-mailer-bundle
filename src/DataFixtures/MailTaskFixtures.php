<?php

namespace Tourze\SMTPMailerBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;
use Tourze\SMTPMailerBundle\Enum\MailTaskStatus;

/**
 * 邮件任务数据填充
 * 用于创建测试和演示用的邮件发送任务
 */
#[When(env: 'dev')]
#[When(env: 'test')]
class MailTaskFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 获取SMTP配置引用
        $gmailConfig = $this->getReference(SMTPConfigFixtures::GMAIL_SMTP_REFERENCE, SMTPConfig::class);
        $outlookConfig = $this->getReference(SMTPConfigFixtures::OUTLOOK_SMTP_REFERENCE, SMTPConfig::class);
        $qqConfig = $this->getReference(SMTPConfigFixtures::QQ_SMTP_REFERENCE, SMTPConfig::class);

        // 创建已发送的邮件任务
        $sentTask = new MailTask();
        $sentTask->setFromEmail('noreply@test.unsplash.com');
        $sentTask->setFromName('系统通知');
        $sentTask->setToEmail('user@test.unsplash.com');
        $sentTask->setToName('张三');
        $sentTask->setSubject('欢迎注册我们的服务');
        $sentTask->setBody($this->getWelcomeEmailBody());
        $sentTask->setIsHtml(true);
        $sentTask->setSmtpConfig($gmailConfig);
        $sentTask->setStatus(MailTaskStatus::SENT);
        $sentTask->setSentTime(new \DateTimeImmutable('-2 hours'));

        $manager->persist($sentTask);

        // 创建待发送的邮件任务
        $pendingTask = new MailTask();
        $pendingTask->setFromEmail('marketing@test.unsplash.com');
        $pendingTask->setFromName('营销团队');
        $pendingTask->setToEmail('customer@test.unsplash.com');
        $pendingTask->setToName('李四');
        $pendingTask->setSubject('新产品发布通知');
        $pendingTask->setBody($this->getProductLaunchEmailBody());
        $pendingTask->setIsHtml(true);
        $pendingTask->setCc(['manager@test.unsplash.com', 'sales@test.unsplash.com']);
        $pendingTask->setSelectorStrategy('priority');
        $pendingTask->setStatus(MailTaskStatus::PENDING);

        $manager->persist($pendingTask);

        // 创建计划发送的邮件任务
        $scheduledTask = new MailTask();
        $scheduledTask->setFromEmail('newsletter@test.unsplash.com');
        $scheduledTask->setFromName('新闻通讯');
        $scheduledTask->setToEmail('subscriber@test.unsplash.com');
        $scheduledTask->setToName('王五');
        $scheduledTask->setSubject('每周新闻摘要');
        $scheduledTask->setBody($this->getNewsletterEmailBody());
        $scheduledTask->setIsHtml(true);
        $scheduledTask->setSmtpConfig($outlookConfig);
        $scheduledTask->setScheduledTime(new \DateTimeImmutable('+1 hour'));
        $scheduledTask->setStatus(MailTaskStatus::PENDING);

        $manager->persist($scheduledTask);

        // 创建失败的邮件任务
        $failedTask = new MailTask();
        $failedTask->setFromEmail('support@test.unsplash.com');
        $failedTask->setFromName('技术支持');
        $failedTask->setToEmail('invalid-email@nonexistent-domain.com');
        $failedTask->setToName('测试用户');
        $failedTask->setSubject('技术支持回复');
        $failedTask->setBody('感谢您联系我们的技术支持团队。我们已经收到您的问题，将在24小时内回复。');
        $failedTask->setIsHtml(false);
        $failedTask->setSmtpConfig($qqConfig);
        $failedTask->setStatus(MailTaskStatus::FAILED);
        $failedTask->setStatusMessage('SMTP Error: 域名不存在');

        $manager->persist($failedTask);

        // 创建带附件的邮件任务
        $attachmentTask = new MailTask();
        $attachmentTask->setFromEmail('hr@test.unsplash.com');
        $attachmentTask->setFromName('人力资源部');
        $attachmentTask->setToEmail('employee@test.unsplash.com');
        $attachmentTask->setToName('赵六');
        $attachmentTask->setSubject('员工手册和合同文件');
        $attachmentTask->setBody($this->getHrEmailBody());
        $attachmentTask->setIsHtml(true);
        $attachmentTask->setAttachments([
            [
                'name' => '员工手册.pdf',
                'mime' => 'application/pdf',
                'data' => base64_encode('这是模拟的PDF文件内容'),
            ],
            [
                'name' => '劳动合同.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'data' => base64_encode('这是模拟的Word文档内容'),
            ],
        ]);
        $attachmentTask->setSelectorStrategy('weighted');
        $attachmentTask->setStatus(MailTaskStatus::PENDING);

        $manager->persist($attachmentTask);

        // 创建处理中的邮件任务
        $processingTask = new MailTask();
        $processingTask->setFromEmail('system@test.unsplash.com');
        $processingTask->setFromName('系统自动化');
        $processingTask->setToEmail('admin@test.unsplash.com');
        $processingTask->setToName('管理员');
        $processingTask->setSubject('系统备份完成通知');
        $processingTask->setBody('系统备份已于今日凌晨完成，备份文件大小：2.5GB，备份状态：成功。');
        $processingTask->setIsHtml(false);
        $processingTask->setSelectorStrategy('round_robin');
        $processingTask->setStatus(MailTaskStatus::PROCESSING);

        $manager->persist($processingTask);

        // 创建群发邮件任务
        $massEmailTask = new MailTask();
        $massEmailTask->setFromEmail('promotion@test.unsplash.com');
        $massEmailTask->setFromName('促销活动');
        $massEmailTask->setToEmail('vip@test.unsplash.com');
        $massEmailTask->setToName('VIP客户');
        $massEmailTask->setSubject('🎉 双十一特惠活动开始啦！');
        $massEmailTask->setBody($this->getPromotionEmailBody());
        $massEmailTask->setIsHtml(true);
        $massEmailTask->setBcc([
            'customer1@test.unsplash.com',
            'customer2@test.unsplash.com',
            'customer3@test.unsplash.com',
        ]);
        $massEmailTask->setSelectorStrategy('random');
        $massEmailTask->setStatus(MailTaskStatus::PENDING);

        $manager->persist($massEmailTask);

        $manager->flush();
    }

    /**
     * 获取依赖的Fixture类
     */
    public function getDependencies(): array
    {
        return [
            SMTPConfigFixtures::class,
        ];
    }

    /**
     * 获取欢迎邮件内容
     */
    private function getWelcomeEmailBody(): string
    {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #2c3e50;">欢迎加入我们！</h2>
                <p>亲爱的用户，</p>
                <p>感谢您注册我们的服务！我们很高兴您能成为我们大家庭的一员。</p>
                <p>您现在可以：</p>
                <ul>
                    <li>浏览我们的产品目录</li>
                    <li>享受会员专属优惠</li>
                    <li>获得优先客服支持</li>
                </ul>
                <p>如果您有任何问题，请随时联系我们的客服团队。</p>
                <p style="margin-top: 30px;">
                    祝好，<br>
                    <strong>团队</strong>
                </p>
            </div>
        </body>
        </html>';
    }

    /**
     * 获取产品发布邮件内容
     */
    private function getProductLaunchEmailBody(): string
    {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #e74c3c;">🚀 新产品震撼发布！</h2>
                <p>尊敬的客户，</p>
                <p>我们激动地宣布，全新产品正式上线！</p>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #2c3e50;">产品亮点：</h3>
                    <ul>
                        <li>🔥 性能提升200%</li>
                        <li>💡 全新用户界面</li>
                        <li>🛡️ 企业级安全保障</li>
                        <li>📱 移动端完美适配</li>
                    </ul>
                </div>
                <p>现在购买享受早鸟优惠，限时8折！</p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="https://unsplash.com/new-product" style="background: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">立即查看</a>
                </p>
            </div>
        </body>
        </html>';
    }

    /**
     * 获取新闻通讯邮件内容
     */
    private function getNewsletterEmailBody(): string
    {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #34495e;">📰 本周新闻摘要</h2>
                <p>亲爱的订阅者，</p>
                <p>以下是本周的重要新闻和更新：</p>
                
                <div style="border-left: 4px solid #3498db; padding-left: 15px; margin: 20px 0;">
                    <h3 style="color: #2c3e50;">技术更新</h3>
                    <p>我们的系统进行了重大升级，响应速度提升了50%。</p>
                </div>
                
                <div style="border-left: 4px solid #e74c3c; padding-left: 15px; margin: 20px 0;">
                    <h3 style="color: #2c3e50;">新功能发布</h3>
                    <p>推出了智能推荐功能，为用户提供个性化体验。</p>
                </div>
                
                <div style="border-left: 4px solid #f39c12; padding-left: 15px; margin: 20px 0;">
                    <h3 style="color: #2c3e50;">社区动态</h3>
                    <p>用户社区新增1000+活跃成员，感谢大家的支持！</p>
                </div>
                
                <p style="margin-top: 30px; font-size: 12px; color: #7f8c8d;">
                    如不想继续接收此邮件，请<a href="https://unsplash.com/unsubscribe">点击退订</a>
                </p>
            </div>
        </body>
        </html>';
    }

    /**
     * 获取HR邮件内容
     */
    private function getHrEmailBody(): string
    {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #2c3e50;">📋 重要文件发送</h2>
                <p>亲爱的同事，</p>
                <p>请查收以下重要文件：</p>
                <ul>
                    <li>员工手册 - 包含公司政策和规章制度</li>
                    <li>劳动合同 - 请仔细阅读并签署</li>
                </ul>
                <p><strong>注意事项：</strong></p>
                <ul>
                    <li>请在收到邮件后3个工作日内完成文件审阅</li>
                    <li>如有疑问，请及时联系HR部门</li>
                    <li>签署后的合同请扫描发送至hr@test.unsplash.com</li>
                </ul>
                <p>感谢您的配合！</p>
                <p style="margin-top: 30px;">
                    此致，<br>
                    <strong>人力资源部</strong>
                </p>
            </div>
        </body>
        </html>';
    }

    /**
     * 获取促销邮件内容
     */
    private function getPromotionEmailBody(): string
    {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div style="background: white; padding: 30px; border-radius: 10px;">
                    <h1 style="color: #e74c3c; text-align: center; font-size: 28px;">🎉 双十一狂欢节</h1>
                    <h2 style="color: #2c3e50; text-align: center;">全场商品最低5折起！</h2>
                    
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <h3 style="color: #856404; margin-top: 0;">🔥 限时优惠</h3>
                        <ul style="color: #856404;">
                            <li>满299减50</li>
                            <li>满599减120</li>
                            <li>满999减200</li>
                        </ul>
                    </div>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <p style="font-size: 18px; color: #e74c3c; font-weight: bold;">活动时间：11月11日 00:00 - 23:59</p>
                        <a href="https://unsplash.com/sale" style="background: #e74c3c; color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; display: inline-block; font-size: 18px; font-weight: bold;">立即抢购</a>
                    </div>
                    
                    <p style="text-align: center; color: #7f8c8d; font-size: 12px;">
                        *活动最终解释权归本公司所有
                    </p>
                </div>
            </div>
        </body>
        </html>';
    }
}
