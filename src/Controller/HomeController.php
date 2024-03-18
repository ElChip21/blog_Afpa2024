<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{


    
    #[Route('/search', name: 'app_search_articles', methods: ['GET'])]
    public function getArticlesBySearch(ArticleRepository $articleRepository, PaginatorInterface $paginator, Request $request): Response
    {


        if ($request->query->has("search")) {

          $search = strtolower($request->query->get("search"));
          $query = $articleRepository->createQueryBuilder('a');
          $articles = $articleRepository->findArticlesBySearch($search);

            $articles = $paginator->paginate(
            $articleRepository->findArticlesBySearch($search),
            $request->query->getInt('page', 1),
            10 // Nombre d'articles par page
        );

          return $this->render('article/index.html.twig', [
            'articles' => $articles,
        ]);

    

        }else{


            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);

        }
       

        
 

      
    }

    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository, CategoryRepository $categoryRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $articles = $paginator->paginate(
            $articleRepository->findAll(),
            $request->query->getInt('page', 1),
            2

        );


        return $this->render('home/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
            'articles' => $articles,

        ]);
    }





}
