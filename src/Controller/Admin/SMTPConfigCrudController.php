<?php

namespace Tourze\SMTPMailerBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\SMTPMailerBundle\Entity\SMTPConfig;

/**
 * SMTP配置管理控制器
 */
class SMTPConfigCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SMTPConfig::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SMTP配置')
            ->setEntityLabelInPlural('SMTP配置')
            ->setSearchFields(['name', 'host', 'username'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', '名称');
        yield TextField::new('host', '服务器地址');
        yield IntegerField::new('port', '端口')->setFormTypeOption('attr', ['min' => 1, 'max' => 65535]);
        yield TextField::new('username', '用户名')->hideOnIndex();
        yield TextField::new('password', '密码')
            ->onlyOnForms()
            ->setFormTypeOption('attr', ['autocomplete' => 'new-password']);
        yield ChoiceField::new('encryption', '加密方式')
            ->setChoices([
                '无' => 'none',
                'SSL' => 'ssl',
                'TLS' => 'tls',
            ]);
        yield IntegerField::new('timeout', '超时时间（秒）')->hideOnIndex();
        yield TextField::new('authMode', '认证模式')->hideOnIndex();
        yield IntegerField::new('weight', '权重')->setHelp('用于权重选择策略，值越大优先级越高');
        yield IntegerField::new('priority', '优先级')->setHelp('用于优先级选择策略，值越大优先级越高');
        yield BooleanField::new('enabled', '启用状态');
        yield DateTimeField::new('createdAt', '创建时间')->hideOnForm();
        yield DateTimeField::new('updatedAt', '更新时间')->hideOnForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }
}
