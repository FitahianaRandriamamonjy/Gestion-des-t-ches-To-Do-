<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->createUser($manager, 'admin@todo.local', 'Administrateur', ['ROLE_ADMIN']);
        $alice = $this->createUser($manager, 'alice@todo.local', 'Alice Rakoto');
        $bob = $this->createUser($manager, 'bob@todo.local', 'Bob Andria');
        $chloe = $this->createUser($manager, 'chloe@todo.local', 'Chloé Randria');

        $samples = [
            ['Préparer la présentation client', TaskPriority::HIGH, TaskStatus::IN_PROGRESS, $alice, $admin, -2, null],
            ['Corriger le bug de connexion', TaskPriority::HIGH, TaskStatus::TODO, $bob, $admin, -1, null],
            ['Rédiger le rapport hebdomadaire', TaskPriority::MEDIUM, TaskStatus::TODO, null, $admin, -3, null],
            ['Mettre à jour la documentation technique', TaskPriority::LOW, TaskStatus::DONE, $alice, $admin, -20, -18],
            ['Revoir la maquette du site', TaskPriority::MEDIUM, TaskStatus::IN_PROGRESS, $bob, $chloe, -4, null],
            ['Répondre aux tickets support', TaskPriority::HIGH, TaskStatus::TODO, $chloe, $admin, -1, null],
            ['Migrer la base de données vers PostgreSQL', TaskPriority::HIGH, TaskStatus::TODO, $bob, $admin, -6, null],
            ['Écrire les tests unitaires du module paiement', TaskPriority::MEDIUM, TaskStatus::IN_PROGRESS, $alice, $bob, -5, null],
            ['Optimiser les requêtes SQL lentes', TaskPriority::MEDIUM, TaskStatus::DONE, $bob, $admin, -30, -25],
            ['Planifier le sprint suivant', TaskPriority::LOW, TaskStatus::DONE, $chloe, $admin, -15, -14],
            ['Déployer la version 2.3 en production', TaskPriority::HIGH, TaskStatus::DONE, $admin, $admin, -10, -9],
            ['Auditer la sécurité de l\'API', TaskPriority::HIGH, TaskStatus::TODO, null, $admin, -8, null],
            ['Créer les maquettes de la page profil', TaskPriority::LOW, TaskStatus::IN_PROGRESS, $chloe, $chloe, -7, null],
            ['Former les nouveaux membres de l\'équipe', TaskPriority::MEDIUM, TaskStatus::TODO, $alice, $admin, -2, null],
            ['Nettoyer les dépendances obsolètes', TaskPriority::LOW, TaskStatus::DONE, $bob, $bob, -45, -40],
            ['Rédiger la charte graphique', TaskPriority::LOW, TaskStatus::DONE, $chloe, $admin, -35, -32],
            ['Préparer la démo pour le comité de direction', TaskPriority::HIGH, TaskStatus::IN_PROGRESS, $admin, $admin, -3, null],
            ['Analyser les retours des utilisateurs beta', TaskPriority::MEDIUM, TaskStatus::TODO, $alice, $bob, -60, null],
        ];

        foreach ($samples as [$title, $priority, $status, $assignedTo, $createdBy, $createdOffset, $doneOffset]) {
            $task = new Task();
            $task->setTitle($title);
            $task->setDescription('Description de démonstration pour : '.$title);
            $task->setPriority($priority);
            $task->setStatus($status);
            $task->setCreatedBy($createdBy);
            $task->setAssignedTo($assignedTo);
            $task->setCreatedAt(new \DateTimeImmutable($createdOffset.' days'));

            if ($status === TaskStatus::DONE) {
                $task->setUpdatedAt(new \DateTimeImmutable(($doneOffset ?? $createdOffset + 1).' days'));
                $task->setDueDate(new \DateTimeImmutable(($doneOffset ?? $createdOffset + 1).' days'));
            } else {
                $dueOffset = random_int(-4, 12);
                $task->setDueDate(new \DateTimeImmutable($dueOffset.' days'));
            }

            $manager->persist($task);
        }

        $manager->flush();
    }

    private function createUser(ObjectManager $manager, string $email, string $fullName, array $roles = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFullName($fullName);
        $user->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $manager->persist($user);

        return $user;
    }
}
