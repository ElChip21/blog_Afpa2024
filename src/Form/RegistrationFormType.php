<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;


class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

        ->add('phone', TextType::class, [
            'required' => true,
            'constraints' => [
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
        ])
        ->add('CodePostal', TextType::class, [
            'required' => true,
            'constraints' => [
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
        ->add('avatar', FileType::class, [
            'label' => 'Avatar (image file)',
            'required' => false,
        ])
        ->add('first_name', TextType::class, [
            'required' => true,
        ])
        ->add('last_name', TextType::class, [
            'required' => true,
        ])
        ->add('email', TextType::class, [
            'constraints' => [
                new Regex([
                    'pattern' => '/^.+@.+\..+$/i',
                    'message' => 'L\'adresse email doit être au format valide.',
                ]),
            ],
        ])
            
           
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])


            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
