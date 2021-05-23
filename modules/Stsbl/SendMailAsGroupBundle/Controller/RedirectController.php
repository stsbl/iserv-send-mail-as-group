<?php

declare(strict_types=1);

namespace Stsbl\SendMailAsGroupBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/groupmails", name="group_mail_legacy_recirect")
 */
final class RedirectController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->redirectToRoute('group_mail_index', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
