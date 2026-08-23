<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\TaskStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        $tasks = $user->getAssignedTasks();
        $total = count($tasks);
        $done = 0;
        $inProgress = 0;
        $todo = 0;
        $overdue = 0;

        foreach ($tasks as $task) {
            match ($task->getStatus()) {
                TaskStatus::DONE => $done++,
                TaskStatus::IN_PROGRESS => $inProgress++,
                TaskStatus::TODO => $todo++,
            };
            if ($task->isOverdue()) {
                $overdue++;
            }
        }

        return $this->render('profile/index.html.twig', [
            'stats' => [
                'total' => $total,
                'done' => $done,
                'inProgress' => $inProgress,
                'todo' => $todo,
                'overdue' => $overdue,
                'productivity' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
            ],
            'tasks' => $tasks,
        ]);
    }
}
