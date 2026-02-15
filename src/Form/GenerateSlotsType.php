<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GenerateSlotsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'admin.generate_slots.start_date',
                'required' => true,
            ])
            ->add('end_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'admin.generate_slots.end_date',
                'required' => true,
            ])
            ->add('force', CheckboxType::class, [
                'label' => 'admin.generate_slots.force_label',
                'required' => false,
                'help' => 'admin.generate_slots.force_help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
