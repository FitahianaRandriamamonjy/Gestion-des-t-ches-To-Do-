<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex : Préparer la soutenance'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('priority', EnumType::class, [
                'class' => TaskPriority::class,
                'label' => 'Priorité',
                'choice_label' => static fn (TaskPriority $priority) => $priority->label(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('status', EnumType::class, [
                'class' => TaskStatus::class,
                'label' => 'Statut',
                'choice_label' => static fn (TaskStatus $status) => $status->label(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('dueDate', DateTimeType::class, [
                'label' => 'Échéance',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'label' => 'Assignée à',
                'required' => false,
                'placeholder' => 'Non assignée',
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
