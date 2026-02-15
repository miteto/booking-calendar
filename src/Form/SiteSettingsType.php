<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SiteSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reservation_details_en', TextareaType::class, [
                'label' => 'admin.settings.reservation_details_en',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'data-controller' => 'quill',
                ],
                'help' => 'admin.settings.html_hint',
            ])
            ->add('reservation_details_bg', TextareaType::class, [
                'label' => 'admin.settings.reservation_details_bg',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                    'data-controller' => 'quill',
                ],
                'help' => 'admin.settings.html_hint',
            ])
            ->add('email_template_en', TextareaType::class, [
                'label' => 'admin.settings.email_template_en',
                'required' => false,
                'attr' => [
                    'rows' => 15,
                    'data-controller' => 'quill',
                ],
                'help' => 'admin.settings.email_help',
            ])
            ->add('email_template_bg', TextareaType::class, [
                'label' => 'admin.settings.email_template_bg',
                'required' => false,
                'attr' => [
                    'rows' => 15,
                    'data-controller' => 'quill',
                ],
                'help' => 'admin.settings.email_help',
            ])
            ->add('reminder_email_template_en', TextareaType::class, [
                'label' => 'admin.settings.reminder_email_template_en',
                'required' => false,
                'attr' => [
                    'rows' => 15,
                    'data-controller' => 'quill',
                ],
                'help' => 'admin.settings.email_help',
            ])
            ->add('reminder_email_template_bg', TextareaType::class, [
                'label' => 'admin.settings.reminder_email_template_bg',
                'required' => false,
                'attr' => [
                    'rows' => 15,
                    'data-controller' => 'quill',
                ],
                'help' => 'admin.settings.email_help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
