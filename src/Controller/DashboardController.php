<?php

namespace App\Controller;

use App\Enum\TaskStatus;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        $allTasks = $taskRepository->findBy([], ['createdAt' => 'DESC']);

        $total = count($allTasks);
        $todo = 0;
        $inProgress = 0;
        $done = 0;
        $overdue = 0;

        foreach ($allTasks as $task) {
            match ($task->getStatus()) {
                TaskStatus::TODO => $todo++,
                TaskStatus::IN_PROGRESS => $inProgress++,
                TaskStatus::DONE => $done++,
            };
            if ($task->isOverdue()) {
                $overdue++;
            }
        }

        $productivity = $total > 0 ? (int) round(($done / $total) * 100) : 0;

        $distribution = [
            'todo' => $total > 0 ? (int) round(($todo / $total) * 100) : 0,
            'in_progress' => $total > 0 ? (int) round(($inProgress / $total) * 100) : 0,
            'done' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
            'overdue' => $total > 0 ? (int) round(($overdue / $total) * 100) : 0,
        ];

        $recentTasks = array_slice($allTasks, 0, 5);

        $upcomingTasks = array_values(array_filter($allTasks, static fn ($t) => $t->getDueDate() !== null && $t->getDueDate() >= new \DateTimeImmutable('today')));
        usort($upcomingTasks, static fn ($a, $b) => $a->getDueDate() <=> $b->getDueDate());
        $upcomingTasks = array_slice($upcomingTasks, 0, 3);

        $labels = [];
        $created = [];
        $doneByDay = [];
        $days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTimeImmutable("-{$i} days");
            $dayLabel = $days[(int) $date->format('N') - 1];
            $labels[] = $dayLabel;

            $createdCount = 0;
            $doneCount = 0;
            foreach ($allTasks as $task) {
                if ($task->getCreatedAt()->format('Y-m-d') === $date->format('Y-m-d')) {
                    $createdCount++;
                }
                if ($task->getStatus() === TaskStatus::DONE && $task->getUpdatedAt()->format('Y-m-d') === $date->format('Y-m-d')) {
                    $doneCount++;
                }
            }
            $created[] = $createdCount;
            $doneByDay[] = $doneCount;
        }

        $monthLabels = [];
        $monthCreated = [];
        $monthDone = [];
        for ($i = 5; $i >= 0; $i--) {
            $refDate = new \DateTimeImmutable("first day of -{$i} months");
            $monthLabels[] = ucfirst($refDate->format('M'));

            $createdCount = 0;
            $doneCount = 0;
            foreach ($allTasks as $task) {
                if ($task->getCreatedAt()->format('Y-m') === $refDate->format('Y-m')) {
                    $createdCount++;
                }
                if ($task->getStatus() === TaskStatus::DONE && $task->getUpdatedAt()->format('Y-m') === $refDate->format('Y-m')) {
                    $doneCount++;
                }
            }
            $monthCreated[] = $createdCount;
            $monthDone[] = $doneCount;
        }

        return $this->render('dashboard/index.html.twig', [
            'stats' => [
                'total' => $total,
                'todo' => $todo,
                'inProgress' => $inProgress,
                'done' => $done,
                'overdue' => $overdue,
            ],
            'distribution' => $distribution,
            'productivity' => $productivity,
            'recentTasks' => $recentTasks,
            'upcomingTasks' => $upcomingTasks,
            'activity' => [
                'labels' => $labels,
                'created' => $created,
                'done' => $doneByDay,
            ],
            'activityMonthly' => [
                'labels' => $monthLabels,
                'created' => $monthCreated,
                'done' => $monthDone,
            ],
        ]);
    }
}
