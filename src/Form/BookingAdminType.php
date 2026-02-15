<?php

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'admin.date',
            ])
            ->add('startTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'admin.start_time',
            ])
            ->add('endTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'admin.end_time',
            ])
            ->add('userName', TextType::class, [
                'label' => 'admin.name',
            ])
            ->add('userEmail', EmailType::class, [
                'label' => 'admin.email',
            ])
            ->add('userPhone', TelType::class, [
                'label' => 'admin.phone',
            ])
            ->add('locale', ChoiceType::class, [
                'label' => 'admin.locale',
                'choices' => [
                    'English' => 'en',
                    'Bulgarian' => 'bg',
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
