<?php

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;

class NotificationService
{
    public function __construct(private readonly TaskRepository $taskRepository)
    {
    }

    public function getNotifications(?User $user = null): array
    {
        $tasks = $this->taskRepository->findBy([], ['updatedAt' => 'DESC']);
        $notifications = [];
        $now = new \DateTimeImmutable();
        $soon = $now->modify('+3 days');
        $recentThreshold = $now->modify('-3 days');

        $isAdmin = $user !== null && in_array('ROLE_ADMIN', $user->getRoles(), true);

        foreach ($tasks as $task) {
            if ($user !== null && !$isAdmin) {
                $concernsUser = $task->getAssignedTo()?->getId() === $user->getId()
                    || $task->getCreatedBy()?->getId() === $user->getId();

                if (!$concernsUser) {
                    continue;
                }
            }

            if ($task->isOverdue()) {
                $notifications[] = [
                    'type' => 'overdue',
                    'icon' => 'bi-exclamation-triangle-fill',
                    'title' => 'Tâche en retard',
                    'message' => sprintf('« %s » devait être terminée le %s.', $task->getTitle(), $task->getDueDate()->format('d/m/Y')),
                    'date' => $task->getDueDate(),
                    'taskId' => $task->getId(),
                ];

                continue;
            }

            if ($task->getDueDate() !== null && $task->getDueDate() >= $now && $task->getDueDate() <= $soon && $task->getStatus()->value !== 'done') {
                $notifications[] = [
                    'type' => 'due_soon',
                    'icon' => 'bi-clock-fill',
                    'title' => 'Échéance proche',
                    'message' => sprintf('« %s » est à rendre le %s.', $task->getTitle(), $task->getDueDate()->format('d/m/Y')),
                    'date' => $task->getDueDate(),
                    'taskId' => $task->getId(),
                ];
            }

            if ($task->getCreatedAt() >= $recentThreshold) {
                $notifications[] = [
                    'type' => 'created',
                    'icon' => 'bi-plus-circle-fill',
                    'title' => 'Nouvelle tâche',
                    'message' => sprintf('« %s » a été créée%s.', $task->getTitle(), $task->getAssignedTo() ? ' et affectée à '.$task->getAssignedTo()->getFullName() : ''),
                    'date' => $task->getCreatedAt(),
                    'taskId' => $task->getId(),
                ];
            } elseif ($task->getAssignedTo() !== null && $task->getUpdatedAt() >= $recentThreshold && $task->getUpdatedAt() != $task->getCreatedAt()) {
                $notifications[] = [
                    'type' => 'assigned',
                    'icon' => 'bi-person-check-fill',
                    'title' => 'Tâche affectée',
                    'message' => sprintf('« %s » a été affectée à %s.', $task->getTitle(), $task->getAssignedTo()->getFullName()),
                    'date' => $task->getUpdatedAt(),
                    'taskId' => $task->getId(),
                ];
            }
        }

        usort($notifications, static fn (array $a, array $b) => $b['date'] <=> $a['date']);

        return array_slice($notifications, 0, 20);
    }

    public function countUnread(?User $user = null): int
    {
        return count($this->getNotifications($user));
    }

    public function computeProductivity(User $user): int
    {
        $tasks = $user->getAssignedTasks();
        $total = count($tasks);

        if ($total === 0) {
            return 0;
        }

        $done = 0;
        foreach ($tasks as $task) {
            if ($task->getStatus()->value === 'done') {
                $done++;
            }
        }

        return (int) round(($done / $total) * 100);
    }
}
