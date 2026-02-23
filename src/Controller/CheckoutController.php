<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    #[Route('/{slug}/{interval}', name: 'app_checkout', methods: ['GET'])]
    public function checkout(
        string $slug,
        string $interval,
        ServiceRepository $serviceRepository,
        \App\Service\WhopService $whopService,
        Request $request,
    ): Response {
        if (!in_array($interval, ['monthly', 'annual'], true)) {
            throw $this->createNotFoundException('Intervalle invalide.');
        }

        $service = $serviceRepository->findBySlug($slug);
        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé.');
        }

        $referralCode = $request->query->get('ref');
        $successUrl = $this->generateUrl('app_checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $checkoutUrl = $whopService->createCheckoutUrl(
                $service,
                $interval,
                $successUrl,
                $referralCode
            );

            return $this->redirect($checkoutUrl);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Désolé, une erreur est survenue lors de la redirection vers Whop.');
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/success', name: 'app_checkout_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        return $this->render('checkout/success.html.twig');
    }

    #[Route('/cancel', name: 'app_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->redirectToRoute('app_home');
    }
}
