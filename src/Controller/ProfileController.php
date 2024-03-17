<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
        ]);
    }



    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function modify(Request $request, EntityManagerInterface $entityManager): Response
    {
            $user = $this->getUser();
            $form = $this->createForm(UserType::class, $this->getUser());
            $form->handleRequest($request);
 
            if ($form->isSubmitted() && $form->isValid()) {
                // Handle avatar upload
                $avatarFile = $form->get('avatar')->getData();
                if ($avatarFile) {
                    // Generate a unique filename for the file
                    $newFilename = uniqid().'.'.$avatarFile->guessExtension();
        
                    // Move the file to the desired directory
                    try {
                        $avatarFile->move(
                            $this->getParameter('avatar_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // Handle file upload error
                        // For example, return a flash message to the user
                        $this->addFlash('error', 'An error occurred while uploading the avatar.');
                        return $this->redirectToRoute('app_register');
                    }
        
                    // Set the avatar path in the user entity
                    $user->setAvatar($newFilename);
                }
               $entityManager->persist($this->getUser()); // insérer en base
               $entityManager->flush(); // fermer la transaction executée par la bdd

               $this->addFlash('success', 'Votre profil a bien été mis à jour !');
               return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);


              }
            


               return $this->render('profile/modify-profile.html.twig', [
                   'profileForm' => $form,
        ]);
    }


    #[Route('/profile/password/edit', name: 'app_profile_password_edit')]
    public function modifyPassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordEncoder): Response
    {

        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordFormType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() ) {
            if ($passwordEncoder->isPasswordValid($user, $form['oldPassword']->getData())){

          if ($form->isValid()) {
         $newEncodePassword = $passwordEncoder->hashPassword($user, $form->get('password')->getData());
         $user->setPassword($newEncodePassword);

           $entityManager->persist($this->getUser()); // insérer en base
           $entityManager->flush(); // fermer la transaction executée par la bdd

           $this->addFlash('success', 'Votre mot de passe a bien été mis à jour !');


           return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        
          }
    }
}
            return $this->render('profile/modify-password.html.twig', [
                'passwordForm' => $form,
            ]);
    }



}