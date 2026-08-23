<?php

namespace App\Controller;

use App\Entity\Task;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/taches')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    private const TASKS_PER_PAGE = 10;

    #[Route('/', name: 'task_index', methods: ['GET'])]
    public function index(Request $request, TaskRepository $taskRepository, UserRepository $userRepository): Response
    {
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');
        $assignedTo = $request->query->get('assignedTo');
        $q = $request->query->get('q');
        $page = max(1, $request->query->getInt('page', 1));

        $totalFiltered = $taskRepository->countByFilters($status, $priority, $assignedTo, $q);
        $totalPages = max(1, (int) ceil($totalFiltered / self::TASKS_PER_PAGE));
        $page = min($page, $totalPages);

        $tasks = $taskRepository->findByFilters($status, $priority, $assignedTo, $q, $page, self::TASKS_PER_PAGE);

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
            'users' => $userRepository->findAllOrderedByName(),
            'currentStatus' => $status,
            'currentPriority' => $priority,
            'currentAssignedTo' => $assignedTo,
            'currentQuery' => $q,
            'counts' => $taskRepository->countByStatus(),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalFiltered' => $totalFiltered,
        ]);
    }

    #[Route('/nouvelle', name: 'task_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();
        $task->setCreatedBy($this->getUser());

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'Tâche créée avec succès.');

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/new.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'task_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Task $task): Response
    {
        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/{id}/modifier', name: 'task_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Tâche mise à jour avec succès.');

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/statut/{status}', name: 'task_change_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeStatus(Request $request, Task $task, string $status, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('status'.$task->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('task_index');
        }

        $newStatus = TaskStatus::tryFrom($status);

        if ($newStatus !== null) {
            $task->setStatus($newStatus);
            $em->flush();
            $this->addFlash('success', sprintf('Statut mis à jour : %s', $newStatus->label()));
        } else {
            $this->addFlash('danger', 'Statut inconnu.');
        }

        return $this->redirectToRoute('task_index');
    }

    #[Route('/{id}/affecter', name: 'task_assign', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function assign(Request $request, Task $task, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('assign'.$task->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('task_index');
        }

        $userId = $request->request->get('userId');

        if ($userId === '' || $userId === null) {
            $task->setAssignedTo(null);
            $this->addFlash('success', 'Tâche désaffectée.');
        } else {
            $user = $userRepository->find($userId);
            if ($user) {
                $task->setAssignedTo($user);
                $this->addFlash('success', sprintf('Tâche affectée à %s.', $user->getFullName()));
            }
        }

        $em->flush();

        return $this->redirectToRoute('task_index');
    }

    #[Route('/{id}/supprimer', name: 'task_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $isOwner = $user && $task->getCreatedBy()?->getUserIdentifier() === $user->getUserIdentifier();

        if (!$isOwner && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', "Seul le créateur de la tâche ou un administrateur peut la supprimer.");

            return $this->redirectToRoute('task_index');
        }

        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
            $this->addFlash('success', 'Tâche supprimée.');
        } else {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('task_index');
    }
}
