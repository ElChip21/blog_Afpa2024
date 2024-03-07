<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArticleCrudController extends AbstractCrudController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title'),
            AssociationField::new('category')
                ->setFormTypeOptions([
                    'choices' => $this->getCategoriesChoices(),
                    'choice_label' => 'title',
                ]),
            ImageField::new('picture', 'picture')
                ->setBasePath('./public/build/images') // chemin de base pour les images
                ->setUploadDir('./public/build/images'), // répertoire de téléchargement pour les nouvelles images
            TextField::new('description'),
        ];
    }

    private function getCategoriesChoices(): array
    {
        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        $choices = [];

        foreach ($categories as $category) {
            $choices[$category->getTitle()] = $category;
        }

        return $choices;
    }
}