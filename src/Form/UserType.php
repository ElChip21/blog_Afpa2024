<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('phone', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your phone number',
                ]),
                new Length([
                    'max' => 10,
                    'maxMessage' => 'Le numéro de téléphone ne doit pas dépasser {{ limit }} caractères.',
                ]),
                new Length([
                    'min' => 10,
                    'maxMessage' => 'Le numéro de téléphone doit au minimum faire {{ limit }} caractères.',
                ]),
                new Regex([
                    'pattern' => '/^\d+$/',
                    'message' => 'Le numéro de téléphone doit contenir uniquement des chiffres.',
                ]),
            ],
        ])
        ->add('adress', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your address',
                ]),
            ],
        ])
        ->add('CodePostal', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your postal code',
                ]),
                new Length([
                    'min' => 5,
                    'minMessage' => 'Le code postal doit avoir au moins {{ limit }} chiffres.',
                ]),
                new Regex([
                    'pattern' => '/^\d+$/',
                    'message' => 'Le code postal doit contenir uniquement des chiffres.',
                ]),
            ],
        ])
        ->add('firstName', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your first name',
                ]),
            ],
        ])


        ->add('lastName', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your last name',
                ]),
            ],
        ])

        ->add('avatar', FileType::class, [
            'label' => 'Avatar',
            'required' => false,
            'mapped' => true,
        ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
