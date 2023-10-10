<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RedirectController extends AbstractController
{
    #[Route('/api/v1/payment/redirect/{token}', name: 'redirect')]
    public function index(): Response
    {
        return new Response('Redirect from provider to the site...');
    }
}
