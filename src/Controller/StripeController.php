<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Entity\Payment;
use App\Repository\OrderDetailsRepository;
use App\Repository\OrderRepository;
use App\Repository\PaymentRepository;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeController extends AbstractController
{
    #[Route('/checkout', name: 'app_stripe_checkout')]
    public function checkout(Request $request, EntityManagerInterface $entityManager): Response
    {

        // Définir la clé secrète de Stripe
        // récupérer ma session stripe via ma clé stripe
        \Stripe\Stripe::setApiKey($this->getParameter('app.stripe_key'));

        $productsInSession = $request->getSession()->get('cart');

        dd($productsInSession);

        $products = [];

        // [
        //     "price" => "",
        //     "quantity" => 1
        // ]

        // afficher un formulaire de paiement avec une session de paiement stripe
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'currency' => 'eur',
            'line_items' => [
                $products
            ],
            'allow_promotion_codes' => true,
            'customer_email' => "sam@gmail.com",
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_stripe_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_stripe_error', [], UrlGeneratorInterface::ABSOLUTE_URL),
            // 'client_reference_id' => 1
        ]);


        // créer un paiement en bdd
        // pour stocker les informations liées à la session de paiement stripe
        $payment = new Payment();
        $payment->setUser($this->getUser())
            ->setSessionID($session['id'])
            ->setPaymentStatus($session['payment_status'])
            ->setDate(new \DateTime())
            ->setSuccessPageExpired(false)
            ->setAmount($session['amount_total'] / 100);
        $entityManager->persist($payment);
        $entityManager->flush();

        return $this->redirect($session->url, 303);

    }

    #[Route('/payment/success', name: 'app_stripe_success')]
    public function success(
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
        PaymentRepository $paymentRepository,
        OrderDetailsRepository $orderDetailsRepository,
        MailerInterface $mailer
    ): Response {

        // je récupère ma connexion à l'API stripe
        \Stripe\Stripe::setApiKey($this->getParameter('app.stripe_key'));
        $user = $this->getUser();

        // récupérer le dernier paiement réalisé par le user
        $lastPayment = $paymentRepository->findLastPayementByUser($user->getId());

        if ($lastPayment) {

            // Récupération de la Session lié au dernier paiement effectué par l'utilisateur
            $session = \Stripe\Checkout\Session::retrieve($lastPayment->getSessionId());

            // On vérifie si la page SUCCESS a déjà été visitée OU si la session à un customer d'enregistré.
            // S'il n'y a pas de Customer cela veut dire que l'utilisateur est allé sur la page Checkout mais n'a pas fait de paiement.

            // ca me permet de savoir que le dernier paiement effectué par le user
            // n'est pas encore arrivé sur la page success
            // et que donc je peux créer mes factures et mes commandes
            if ($lastPayment->getSuccessPageExpired() == false && $session['customer']) {

                // Récupération de toutes les informations liés à la session et donc au dernier paiement
                $subscription = \Stripe\Subscription::retrieve($session['subscription']);
                // $invoice = \Stripe\Invoice::retrieve($subscription['latest_invoice']);
                $paymentMethod = \Stripe\PaymentMethod::retrieve($subscription['default_payment_method']);

                // je mets à jour mon paiement

                /**
                 * Insertion des informations de paiement en BDD
                 */
                $lastPayment->setPaymentStatus($session['payment_status'])
                    // ->setCustomerStripeId($session['customer'])
                    // ->setSubscriptionId($session['subscription'])
                    ->setPaymentMethodId($paymentMethod['id'])
                    ->setSuccessPageExpired(true); // On paramètre ici la valeur true ce qui permettra d'éviter à un utilisateur de retourner sur cette page une deuxième fois.
                $entityManager->persist($lastPayment);


                // je vais générer les orders et orders details ainsi que la facture que j'envoie par email

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
                    $orderDetails->setOrderNumber($order->getId());
                    $orderDetails->setProduct($productRepository->find($cart["id"][$i]));
                    $orderDetails->setQuantity($cart["id"][$i]);

                    $entityManager->persist($orderDetails);
                    $entityManager->flush();
                }

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
                    $entityManager->persist($order);
                    $entityManager->flush();
            
                    // vider le panier
                    $session = $request->getSession();
                    $session->set('cart', []);        
            
                    // return $this->render("payment/success.html.twig", [
                    //     'user' => $this->getUser(),
                    //     'amount' => $order->getAmount(),
                    //     'invoiceNumber' => $invoiceNumber,
                    //     'date' => new \DateTime(),
                    //     'orderDetails' => $orderDetailsRepository->findBy(['orderNumber' => $order->getId()])
                    // ]);

                    return $this->render('stripe/success.html.twig', [
                        'invoiceNumber' => $order->getId(),
                        'paymentMethod' => $paymentMethod['card']['brand'],
                    ]);
        
                }

            }

        }

        // je tombe ici si je suis déja allé sur la page success
        // ou si mon pdf a déjà été généré (ce qui est impossible techniquement)

        // si je suis déjà arrivé une fois sur la page success pour ce paiement
        // je redirige vers la page d'acceuil pour ne pas générer deux fois les commandes
        // et renvoyer le même mail
        return $this->redirectToRoute('app_home');


    }

    #[Route('/payment/error', name: 'app_stripe_error')]
    public function error(): Response
    {
        return $this->render('stripe/index.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }
}



