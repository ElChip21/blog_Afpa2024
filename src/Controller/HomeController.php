<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;

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

        $message = new TranslatableMessage('Symfony is great!');

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


    #[Route('/filter/{filter}', name: 'app_home_filter')]

    public function getArticleByFilter(ArticleRepository $articleRepository, CategoryRepository $categoryRepository, Request $request, string $filter): JsonResponse
    {
        $articlesData = [];

        foreach ($articleRepository->findArticlesByFilter($filter) as $article) {



            $articleData = [
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'description' => $article->getDescription(),
            'picture' => $article->getPicture(),
            'date' => $article->getDate()->format('Y-m-d'),
            'category_id' => $article->getCategory() ? $article->getCategory()->getId() : null,
            'category_name' => $article->getCategory() ? $article->getCategory()->getTitle() : null,
            'url' => $this->generateUrl('app_article_show', ['id' => $article->getId()], UrlGeneratorInterface::ABSOLUTE_URL),



            ];       
            
            $articlesData[] = $articleData;

            }

        return new JsonResponse($articlesData);
    }




}
