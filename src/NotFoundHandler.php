<?php

namespace App;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class NotFoundHandler
{
    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof NotFoundHttpException)
            return;

        $response = new JsonResponse(['message' => $event->getThrowable()->getMessage()], 404);

        $event->setResponse($response);
        $event->stopPropagation();
    }
}