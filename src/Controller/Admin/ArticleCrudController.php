<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ArticleCrudController extends AbstractCrudController
{
    private $entityManager;
    private $params;
    public function __construct(EntityManagerInterface $entityManager,ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
    }
 


    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // IdField::new('id')->hideOnForm(),
            TextField::new('title'),
            AssociationField::new('category')
                ->setFormTypeOptions([
                    'choices' => $this->getCategoriesChoices(),
                    'choice_label' => 'title',
                ]),

            ImageField::new('picture')
            ->setUploadDir('public/uploads/articles')
            ->setBasePath('uploads/articles')
            ->setUploadedFileNamePattern('[slug]-[contenthash].[extension]')
            ->setRequired(false),

            TextField::new('description'),
            DateField::new('date'),
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