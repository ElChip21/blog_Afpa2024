<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Comments;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', null, [
                'label' => 'Ajouter un commentaire',
            ])
        //     ->add('dateCreation', null, [
        //         'widget' => 'single_text',
        //         'mapped' => false
        //     ])
        //     ->add('author')
        //     ->add('isVerified')
        //     ->add('articleId', EntityType::class, [
        //         'class' => Article::class,
        //         'choice_label' => 'id',
        //     ])
        //     ->add('userId', EntityType::class, [
        //         'class' => User::class,
        //         'choice_label' => 'id',
        //     ])
        // 
;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comments::class,
        ]);
    }
}
