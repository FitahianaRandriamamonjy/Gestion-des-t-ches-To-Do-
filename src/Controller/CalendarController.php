<?php

namespace App\Controller;

use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CalendarController extends AbstractController
{
    #[Route('/calendrier', name: 'calendar_index', methods: ['GET'])]
    public function index(Request $request, TaskRepository $taskRepository): Response
    {
        $monthParam = $request->query->get('month');
        try {
            $current = $monthParam ? new \DateTimeImmutable($monthParam.'-01') : new \DateTimeImmutable('first day of this month');
        } catch (\Exception) {
            $current = new \DateTimeImmutable('first day of this month');
        }

        $firstOfMonth = $current->modify('first day of this month')->setTime(0, 0);
        $lastOfMonth = $current->modify('last day of this month')->setTime(23, 59, 59);

        $gridStart = $firstOfMonth->modify('monday this week');
        if ($gridStart > $firstOfMonth) {
            $gridStart = $gridStart->modify('-7 days');
        }
        $gridEnd = $lastOfMonth->modify('sunday this week');
        if ($gridEnd < $lastOfMonth) {
            $gridEnd = $gridEnd->modify('+7 days');
        }

        $allTasks = $taskRepository->findBy([], ['dueDate' => 'ASC']);
        $tasksByDay = [];
        foreach ($allTasks as $task) {
            if ($task->getDueDate() === null) {
                continue;
            }
            $key = $task->getDueDate()->format('Y-m-d');
            $tasksByDay[$key][] = $task;
        }

        $weeks = [];
        $cursor = $gridStart;
        $week = [];
        while ($cursor <= $gridEnd) {
            $key = $cursor->format('Y-m-d');
            $week[] = [
                'date' => $cursor,
                'inMonth' => $cursor->format('Y-m') === $current->format('Y-m'),
                'isToday' => $cursor->format('Y-m-d') === (new \DateTimeImmutable('today'))->format('Y-m-d'),
                'tasks' => $tasksByDay[$key] ?? [],
            ];
            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
            $cursor = $cursor->modify('+1 day');
        }

        return $this->render('calendar/index.html.twig', [
            'weeks' => $weeks,
            'current' => $current,
            'prevMonth' => $current->modify('-1 month')->format('Y-m'),
            'nextMonth' => $current->modify('+1 month')->format('Y-m'),
        ]);
    }
}
