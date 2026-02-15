<?php

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('userName', TextType::class, [
                'label' => 'booking.form.name',
            ])
            ->add('userEmail', EmailType::class, [
                'label' => 'booking.form.email',
            ])
            ->add('userPhone', TelType::class, [
                'label' => 'booking.form.phone',
            ])
            // These are hidden because they are handled by the slot selection logic
            ->add('slot_id', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('date', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('start_time', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('end_time', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('embed', HiddenType::class, [
                'mapped' => false,
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
