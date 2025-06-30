<?php

namespace Tourze\SMTPMailerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\SMTPMailerBundle\Service\SMTPMailerService;

/**
 * 处理计划任务的控制器
 */
class ProcessScheduledController extends AbstractController
{
    #[Route(path: '/admin/process-scheduled', name: 'smtp_mailer_process_scheduled')]
    public function __invoke(Request $request, SMTPMailerService $mailerService): Response
    {
        $count = $mailerService->processScheduledTasks();

        if ($count > 0) {
            $this->addFlash('success', sprintf('已处理 %d 封计划发送的邮件', $count));
        } else {
            $this->addFlash('info', '没有需要处理的计划邮件');
        }

        // 如果是从后台点击，返回到后台
        $referer = $request->headers->get('referer');
        if (null !== $referer && str_contains($referer, '/admin')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('smtp_mailer_admin');
    }
}