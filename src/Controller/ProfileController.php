<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use App\Form\UserType;
use App\Service\ImageService;
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
    public function modify(Request $request, EntityManagerInterface $entityManager, ImageService $imageService): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $fileName = $imageService->copyImage("avatar", $this->getParameter("avatar_directory"), $form);
            $user->setAvatar($fileName);
            $entityManager->persist($user);
            $entityManager->flush();

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

        if ($form->isSubmitted()) {
            if ($passwordEncoder->isPasswordValid($user, $form['oldPassword']->getData())) {

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