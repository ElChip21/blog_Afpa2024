<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Repository\OrderDetailsRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
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
            $order->setPdf(false);
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

            return $this->redirectToRoute("success");

        }

        $session = $request->getSession();
        $session->set('url_retour', $request->getUri());

        // si pas connecté
        return $this->redirectToRoute('app_login');

    }

    #[Route('/success', name: 'success')]
    public function success(MailerInterface $mailer,
    OrderRepository $orderRepository,
    OrderDetailsRepository $orderDetailsRepository,
    EntityManagerInterface $entityManagerInterface,
    Request $request): Response
    {

        $idUser = $this->getUser()->getId();
        // le numéro de la dernière order pour le user
        $order = $orderRepository->findOneBy(['user' => $idUser], ['id' => 'DESC']);
        // le montant total
        // les produits achetés
        // => récupérer la dernière facture insérée en bdd pour le user
        // et tous les orderDetails liés à cette facture

        if(!$order->isPdf()) {

            // on génera le PDF
            $pdfOptions = new Options();
            $pdfOptions->set(['defaultFont' => 'Arial', 'enable_remote' => true]);
            // 2- On crée le pdf avec les options
            $dompdf = new Dompdf($pdfOptions);

            $invoiceNumber = $order->getId();
    
            // 3- On prépare le twig qui sera transformée en pdf
            $html = $this->renderView('invoice/index.html.twig', [
                'user' => $this->getUser(),
                'amount' => $order->getAmount(),
                'invoiceNumber' => $invoiceNumber,
                'date' => new \DateTime(),
                'orderDetails' => $orderDetailsRepository->findBy(['orderNumber' => $order->getId()])
            ]);
    
            // 4- On transforme le twig en pdf avec les options de format
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
    
            // 5- On enregistre le pdf dans une variable
            $dompdf->render();
            $finalInvoice = $dompdf->output();
    
            if (!file_exists('uploads/factures')) {
                mkdir('uploads/factures');
            }

            $pathInvoice = "./uploads/factures/" . $invoiceNumber . "_" . $this->getUser()->getId() . ".pdf";
            file_put_contents($pathInvoice, $finalInvoice);
            // on l'enverra par mail la facture
            // on affichera une page de succès
    
            $email = (new TemplatedEmail())
                ->from($this->getParameter('app.mailAddress'))
                ->to($this->getUser()->getEmail())
                //->bcc('bcc@example.com')
                //->replyTo('fabien@example.com')
                //->priority(Email::PRIORITY_HIGH)
                ->subject("Facture Blog Afpa 2024")
                // ->html('<p> ' . $contact->getMessage() . ' </p>');
                ->htmlTemplate("invoice/email.html.twig")
                ->context([
                    'user' => $this->getUser(),
                    'amount' => $order->getAmount(),
                    'invoiceNumber' => $invoiceNumber,
                    'date' => new \DateTime(),
                    'orderDetails' => $orderDetailsRepository->findBy(['orderNumber' => $order->getId()])
                ])
                ->attach($finalInvoice, sprintf('facture-' . $invoiceNumber . 'blog-afpa.pdf', date("Y-m-d")));
    
            $mailer->send($email);
    
            $order->setPdf(true);
            $entityManagerInterface->persist($order);
            $entityManagerInterface->flush();
    
            // vider le panier
            $session = $request->getSession();
            $session->set('cart', []);        
    
            return $this->render("payment/success.html.twig", [
                'user' => $this->getUser(),
                'amount' => $order->getAmount(),
                'invoiceNumber' => $invoiceNumber,
                'date' => new \DateTime(),
                'orderDetails' => $orderDetailsRepository->findBy(['orderNumber' => $order->getId()])
            ]);

        } else {

            return $this->redirectToRoute('app_home');
        }

    }
}
