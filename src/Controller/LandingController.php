<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LandingController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(ServiceRepository $serviceRepository): Response
    {
        // If user is logged in, redirect to dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $services = $serviceRepository->findAllActive();

        // bundle pricing comes from environment variables with defaults
        $bundleMonthly = $_ENV['BUNDLE_MONTHLY_PRICE'] ?? '79';
        $bundleAnnual = $_ENV['BUNDLE_ANNUAL_PRICE'] ?? '750';

        return $this->render('landing/index.html.twig', [
            'services' => $services,
            'bundleMonthly' => $bundleMonthly,
            'bundleAnnual' => $bundleAnnual,
        ]);
    }


    #[Route('/legal/terms', name: 'app_terms', methods: ['GET'])]
    public function terms(): Response
    {
        return $this->render('landing/terms.html.twig');
    }

    #[Route('/legal/privacy', name: 'app_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('landing/privacy.html.twig');
    }
}
