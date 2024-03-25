<?php

namespace App\Controller;


use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;

class PaymentController extends AbstractController
{
    #[Route('/payment', name: 'pay')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        OrderRepository $orderRepository,
        Security $security
    ): Response {

        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {


            // je récupère la session cart
            $cart = $request->getSession()->get('cart');
            // je créé une commande
            $order = new Order;
            $cartTotal = 0;

            for ($i = 0; $i < count($cart["id"]); $i++) {
                $cartTotal += (float) $cart["price"][$i] * $cart["quantity"][$i];
            }

            $order->setAmount($cartTotal);
            $order->setStatus('En cours');
            $order->setUser($this->getUser());
            $order->setDate(new \DateTime);

            $entityManager->persist($order);
            $entityManager->flush();


            // pour chaque élément de mon panier je créé un détail de commande
            for ($i = 0; $i < count($cart["id"]); $i++) {
                $orderDetails = new OrderDetails;
                $orderDetails->setOrderNumber($orderRepository->findOneBy([], ['id' => 'DESC']));
                $orderDetails->setProduct($productRepository->find($cart["id"][$i]));
                $orderDetails->setQuantity($cart["id"][$i]);

                $entityManager->persist($orderDetails);
                $entityManager->flush();

            }

            // Rendu de la page après la boucle for
            $request->getSession()->set('cart', []);

            return $this->redirectToRoute("success");
        }

        $session = $request->getSession();
        $session->set('url_retour', $request->getUri());

        // si pas connecté
        return $this->redirectToRoute('app_login');



    }

    #[Route('/success', name: 'success')]
    public function success(MailerInterface $mailer): Response
    {
        // Génération du PDF
        $pdfOptions = new Options();
        $pdfOptions->set(['defaultFont' => 'Arial', 'enable_remote' => true]);
        $dompdf = new Dompdf($pdfOptions);
    
        $html = $this->renderView('invoice/index.html.twig', [
            'Amount' => 10,
            'invoiceNumber' => 'F1093',
            'date' => new \DateTime(),
            'products' => []
        ]);
    
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $finalInvoice = $dompdf->output();
    
        // Enregistrement du PDF sur le serveur
        $invoiceNumber = uniqid(); // Utilisation d'un identifiant unique pour la facture
        $userId = $this->getUser()->getId();
        $directoryPath = $this->getParameter('kernel.project_dir') . '/public/uploads/factures';
        $pathInvoice = "{$directoryPath}/{$invoiceNumber}_{$userId}.pdf";
    
        // Vérifier si le répertoire existe, sinon le créer
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }
    
        file_put_contents($pathInvoice, $finalInvoice);
    
        // Envoi de l'e-mail avec le PDF de la facture en pièce jointe
        $email = (new TemplatedEmail())
            ->from($this->getParameter('app.mailAddress'))
            ->to($this->getUser()->getEmail())
            ->subject("Facture Blog Afpa 2024")
            ->htmlTemplate("invoice/email.html.twig")
            ->attach(
                fopen($pathInvoice, 'r'),
                sprintf('facture-%s-blog-afpa.pdf', $invoiceNumber),
                'application/pdf'
            );
            
    
        $mailer->send($email);
    
        // Affichage de la page de succès
        return $this->render("payment/success.html.twig", [
            'invoiceNumber' => $invoiceNumber,
            'amount' => 100,
        ]);
    }
    
}
