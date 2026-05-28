<?php

namespace App\Form;

use App\Entity\SlotConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Positive;

class SlotConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enabled', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'admin.enabled',
            ])
            ->add('startTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'admin.start_time',
            ])
            ->add('endTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'admin.end_time',
            ])
            ->add('slotInterval', IntegerType::class, [
                'label' => 'admin.interval_min',
                'constraints' => [new Positive()],
                'attr' => ['min' => 1],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SlotConfig::class,
        ]);
    }
}
