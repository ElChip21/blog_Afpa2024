<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;

class MonAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $httpUtils;

    public function __construct(HttpUtils $httpUtils)
    {
        $this->httpUtils = $httpUtils;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token) :RedirectResponse
    {
        $session = $request->getSession();
        if ($session->has('url_retour')) {
            $url = $session->get('url_retour');
            $session->remove('url_retour');
            return new RedirectResponse($url);
        }

        // Sinon, redirection vers la page par défaut
        return $this->httpUtils->createRedirectResponse($request, 'route_par_defaut');
    }
}

?>