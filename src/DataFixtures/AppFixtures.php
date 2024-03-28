<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comments;
use App\Entity\Contact;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $faker = \Faker\Factory::create("fr_FR");

        // Créer des produits
        for($i = 0; $i < 5; $i++) {
            $product = new Product;
            $product->setName($faker->name);
            $product->setDescription($faker->firstName);
            $product->setPicture($faker->imageUrl);
            $product->setPrice($faker->numberBetween(1, 90));
            $product->setStock($faker->numberBetween(1, 20));
            $manager->persist($product);

            // créer des catégories
            $category = new Category;
            $category->setDescription($faker->text);
            $category->setTitle($faker->title);
            $manager->persist($category);
    
            // créer des articles
            $article = new Article;
            $article->setCategory($category);
            $article->setDate($faker->dateTime);
            $article->setDescription($faker->text);
            $article->setTitle($faker->title);
            $article->setPicture($faker->imageUrl);
            $manager->persist($article);

            // créer des users
            $user = new User;
            $user->setAdress($faker->address);
            $user->setEmail($faker->email);
            $user->setFirstName($faker->firstName);
            $user->setLastName($faker->lastName);
            $user->setPassword($faker->password);
            $user->setPhone("0" . $i . "01" . $i . "10". $i . "01");
            $user->setIsVerified($faker->boolean);
            $user->setCodePostal($faker->postcode);
            $manager->persist($user);

            // créer des orders
            $order = new Order;
            $order->setAmount($faker->numberBetween(1, 200));
            $order->setDate($faker->dateTime);
            $order->setPdf($faker->boolean);
            $order->setStatus("delivered");
            $order->setUser($user);
            $manager->persist($order);

            // créer des orderDetails
            $orderDetail = new OrderDetails;
            $orderDetail->setOrderNumber($order->getId());
            $orderDetail->setProduct($product);
            $orderDetail->setQuantity($faker->numberBetween(1, 10));
            $manager->persist($orderDetail);

            // créer des commentaires
            $comment = new Comments;
            $comment->setArticleId($article);
            $comment->setDateCreation($faker->dateTime);
            $comment->setContenu($faker->text);
            $comment->setIsVerified($faker->boolean);
            $comment->setUserId($user);
            $manager->persist($comment);
    
            // créer des messages contacts

            $contact = new Contact;
            $contact->setDate($faker->dateTime);
            $contact->setEmail($faker->email);
            $contact->setFirstName($faker->firstName);
            $contact->setLastName($faker->lastName);
            $contact->setMessage($faker->text);
            $contact->setObject($faker->title);
            $manager->persist($contact);

        }

        $manager->flush();
    }
}
