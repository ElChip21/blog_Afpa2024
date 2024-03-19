<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageService
{
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function copyImage($name, $directory, $form)
    {
        $pictureFile = $form->get($name)->getData();

        if ($pictureFile) {
            $originalFileName = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFileName = $this->slugger->slug($originalFileName);

            $newFilename = $safeFileName.'-'.uniqid().'.'.$pictureFile->guessExtension();

            try {
                $pictureFile->move(
                    $directory,
                    $newFilename
                );
            } catch (FileException $e) {
                // Gérer l'erreur d'upload de fichier ici
                return null;
            }

            return $newFilename;
        }

        return null;
    }
}
