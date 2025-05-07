<?php

namespace Tourze\SMTPMailerBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\JsonField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tourze\SMTPMailerBundle\Entity\MailTask;
use Tourze\SMTPMailerBundle\Service\SMTPMailerService;
use Tourze\SMTPMailerBundle\Service\SMTPSelectorService;

/**
 * 邮件任务管理控制器
 */
class MailTaskCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SMTPSelectorService $selectorService,
        private readonly SMTPMailerService $mailerService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    )
    {
    }

    public static function getEntityFqcn(): string
    {
        return MailTask::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('邮件任务')
            ->setEntityLabelInPlural('邮件任务')
            ->setSearchFields(['fromEmail', 'toEmail', 'subject', 'status'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('fromEmail', '发件人邮箱');
        yield TextField::new('fromName', '发件人名称');
        yield EmailField::new('toEmail', '收件人邮箱');
        yield TextField::new('toName', '收件人名称');
        yield JsonField::new('cc', '抄送')->hideOnIndex();
        yield JsonField::new('bcc', '密送')->hideOnIndex();
        yield TextField::new('subject', '邮件主题');

        if ($pageName === Crud::PAGE_DETAIL || $pageName === Crud::PAGE_EDIT) {
            yield CodeEditorField::new('body', '邮件内容')
                ->setLanguage('html')
                ->setNumOfRows(20);
        } else {
            yield TextareaField::new('body', '邮件内容')->hideOnIndex();
        }

        yield BooleanField::new('isHtml', 'HTML格式');
        yield JsonField::new('attachments', '附件')->hideOnIndex();
        yield DateTimeField::new('scheduledAt', '计划发送时间')->hideOnIndex();

        yield ChoiceField::new('status', '状态')
            ->setChoices([
                '待处理' => MailTask::STATUS_PENDING,
                '处理中' => MailTask::STATUS_PROCESSING,
                '已发送' => MailTask::STATUS_SENT,
                '失败' => MailTask::STATUS_FAILED,
            ])
            ->renderAsBadges([
                MailTask::STATUS_PENDING => 'warning',
                MailTask::STATUS_PROCESSING => 'info',
                MailTask::STATUS_SENT => 'success',
                MailTask::STATUS_FAILED => 'danger',
            ]);

        yield TextareaField::new('statusMessage', '状态信息')->hideOnIndex();
        yield AssociationField::new('smtpConfig', 'SMTP配置')->hideOnIndex();

        $strategies = $this->selectorService->getAvailableStrategies();
        $strategyChoices = [];
        foreach ($strategies as $key => $name) {
            $strategyChoices[$name] = $key;
        }

        yield ChoiceField::new('selectorStrategy', '选择策略')
            ->setChoices($strategyChoices)
            ->setHelp('用于选择SMTP配置的策略')
            ->hideOnIndex();

        yield DateTimeField::new('createdAt', '创建时间');
        yield DateTimeField::new('updatedAt', '更新时间')->hideOnIndex();
        yield DateTimeField::new('sentAt', '发送时间')->hideOnIndex();
    }

    public function configureActions(Actions $actions): Actions
    {
        // 添加重发按钮
        $resend = Action::new('resend', '重新发送')
            ->linkToCrudAction('resendAction')
            ->displayIf(fn(MailTask $entity) => $entity->getStatus() === MailTask::STATUS_FAILED);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $resend)
            ->add(Crud::PAGE_DETAIL, $resend);
    }

    /**
     * 重新发送邮件
     */
    #[AdminAction('{entityId}/resend', 'resend')]
    public function resendAction(AdminContext $context): RedirectResponse
    {
        /** @var MailTask $mailTask */
        $mailTask = $context->getEntity()->getInstance();
        $id = $mailTask->getId();

        try {
            $this->mailerService->resendFailedMail($id);
            $this->addFlash('success', sprintf('邮件 #%d 已加入发送队列', $id));
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('邮件 #%d 重发失败: %s', $id, $e->getMessage()));
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($id)
            ->generateUrl();

        return $this->redirect($url);
    }
}
