<?php

namespace App\Controller;

use App\Enum\TaskStatus;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class TeamController extends AbstractController
{
    #[Route('/equipe', name: 'team_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAllOrderedByName();

        $members = [];
        foreach ($users as $user) {
            $tasks = $user->getAssignedTasks();
            $total = count($tasks);
            $done = 0;
            $inProgress = 0;
            $overdue = 0;

            foreach ($tasks as $task) {
                if ($task->getStatus() === TaskStatus::DONE) {
                    $done++;
                } elseif ($task->getStatus() === TaskStatus::IN_PROGRESS) {
                    $inProgress++;
                }
                if ($task->isOverdue()) {
                    $overdue++;
                }
            }

            $members[] = [
                'user' => $user,
                'total' => $total,
                'done' => $done,
                'inProgress' => $inProgress,
                'overdue' => $overdue,
                'productivity' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
            ];
        }

        usort($members, static fn ($a, $b) => $b['productivity'] <=> $a['productivity']);

        return $this->render('team/index.html.twig', [
            'members' => $members,
        ]);
    }
}
