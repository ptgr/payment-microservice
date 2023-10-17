<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class RedirectController extends AbstractController
{
    #[Route('/api/v1/payment/redirect/{token}', name: 'redirect', methods: ['GET'])]
    /**
     * @OA\Tag(name="Redirect from payment provider")
     */
    public function redirectFromProvider(): Response
    {
        return new Response('Redirect from provider to the site...');
    }
}
