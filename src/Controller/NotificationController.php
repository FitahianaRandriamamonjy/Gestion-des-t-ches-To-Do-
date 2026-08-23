<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'notification_index', methods: ['GET'])]
    public function index(NotificationService $notificationService): Response
    {
        $user = $this->getUser();

        return $this->render('notification/index.html.twig', [
            'notifications' => $notificationService->getNotifications($user instanceof User ? $user : null),
        ]);
    }
}
