<?php

namespace App\Controller;

use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class StatsController extends AbstractController
{
    #[Route('/statistiques', name: 'stats_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository, UserRepository $userRepository): Response
    {
        $allTasks = $taskRepository->findBy([]);

        $weekLabels = [];
        $weekCreated = [];
        $weekDone = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = new \DateTimeImmutable("monday this week -{$i} weeks");
            $weekEnd = $weekStart->modify('+6 days')->setTime(23, 59, 59);
            $weekLabels[] = 'S'.$weekStart->format('W');

            $createdCount = 0;
            $doneCount = 0;
            foreach ($allTasks as $task) {
                if ($task->getCreatedAt() >= $weekStart && $task->getCreatedAt() <= $weekEnd) {
                    $createdCount++;
                }
                if ($task->getStatus() === TaskStatus::DONE && $task->getUpdatedAt() >= $weekStart && $task->getUpdatedAt() <= $weekEnd) {
                    $doneCount++;
                }
            }
            $weekCreated[] = $createdCount;
            $weekDone[] = $doneCount;
        }

        $priorityCounts = ['low' => 0, 'medium' => 0, 'high' => 0];
        $statusCounts = ['todo' => 0, 'in_progress' => 0, 'done' => 0];

        foreach ($allTasks as $task) {
            $priorityCounts[$task->getPriority()->value]++;
            $statusCounts[$task->getStatus()->value]++;
        }

        $users = $userRepository->findAllOrderedByName();
        $ranking = [];
        foreach ($users as $user) {
            $done = 0;
            foreach ($user->getAssignedTasks() as $task) {
                if ($task->getStatus() === TaskStatus::DONE) {
                    $done++;
                }
            }
            $ranking[] = ['user' => $user, 'done' => $done];
        }
        usort($ranking, static fn ($a, $b) => $b['done'] <=> $a['done']);

        return $this->render('stats/index.html.twig', [
            'trend' => [
                'labels' => $weekLabels,
                'created' => $weekCreated,
                'done' => $weekDone,
            ],
            'priorityCounts' => $priorityCounts,
            'statusCounts' => $statusCounts,
            'ranking' => array_slice($ranking, 0, 8),
            'totalTasks' => count($allTasks),
        ]);
    }
}
