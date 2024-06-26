<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Entity\Comments;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Service\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;



#[Route('/article')] // /article s'ajoutera pour chauqe route
class ArticleController extends AbstractController
{



    #[Route('/', name: 'app_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository, PaginatorInterface $paginator,Request $request): Response
    {
        
        $articles = $paginator->paginate(
            $articleRepository->findAll(),
            $request->query->getInt('page', 1),
            2

        );
        return $this->render('article/index.html.twig', [
            'articles' => $articles
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, ImageService $imageService): Response
{
    $article = new Article();
    $form = $this->createForm(ArticleType::class, $article);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      
        $fileName = $imageService->copyImage("picture", $this->getParameter("article_picture_directory"), $form);
        $article->setPicture($fileName);
        $entityManager->persist($article);
        $entityManager->flush();


        $this->addFlash(
            'success',
            'Votre article a bien été ajouté'
        );

        
        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
     
    }

   

    return $this->render('article/new.html.twig', [
        'article' => $article,
        'form' => $form->createView(),
    ]);
}




    #[Route('/{id}', name: 'app_article_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function show(Article $article, Request $request, EntityManagerInterface $entityManager, int $id = 1): Response
    {
     
        
        $comment = new Comments();
        $user = $this->getUser();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUserId($this->getUser());
            $comment->setAuthor($user->getFirstName());
            $comment->setArticleId($article);
            $comment->setDateCreation(new \DateTime());
            $comment->setIsVerified(false);

            $entityManager->persist($comment);
            $entityManager->flush();


            $this->addFlash('success', 'Votre commentaire a bien été ajouté.');

        }

         return $this->render('article/show.html.twig', [
           'article' => $article,
           'commentForm' => $form,
        ]);       
        
    }
        
       
    




    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager, ImageService $imageService): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle picture upload
            
        $fileName = $imageService->copyImage("picture", $this->getParameter("article_picture_directory"), $form);
        $article->setPicture($fileName);
        $entityManager->persist($article);
        $entityManager->flush();


        $this->addFlash(
            'success',
            'Votre article a bien été ajouté'
        );

        
        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    
          
        }
    
        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
            
        $this->addFlash(
            'success',
            'Votre article a bien été supprimé'
        );
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }



    #[Route('/category/{id_category}', name: 'app_get_article_by_category', methods: ['GET'])]
    public function getArticleByCategory(PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager, int $id_category): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findBy(array("category" => $id_category));

        
    $articles = $paginator->paginate(
        $articles,
        $request->query->getInt('page', 1),
        2 
    );

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
        ]);
    }

}
