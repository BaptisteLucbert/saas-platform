<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\WhopService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WebhookController extends AbstractController
{
    #[Route('/webhook/whop', name: 'app_webhook_whop', methods: ['POST'])]
    public function handleWhopWebhook(
        Request $request,
        WhopService $whopService,
        LoggerInterface $logger
    ): Response {
        if (!$whopService->validateWebhook($request)) {
            $logger->error('Signature Webhook Whop invalide.');
            return new Response('Invalid signature', Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        if (!$payload) {
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        try {
            $whopService->handleWebhook($payload);
            return new Response('Webhook handled', Response::HTTP_OK);
        } catch (\Exception $e) {
            $logger->error('Erreur traitement Webhook Whop: ' . $e->getMessage());
            return new Response('Error handling webhook', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
