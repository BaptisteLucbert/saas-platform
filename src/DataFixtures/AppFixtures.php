<?php

namespace App\DataFixtures;

use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(
        private \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $services = [
            [
                'name' => 'Pronostics Sportifs',
                'slug' => 'pronostics',
                'description' => 'Notre algorithme analyse des millions de données en temps réel pour vous livrer les meilleures predictions via Discord.',
                'monthly_price' => 29.99,
                'annual_price' => 280.00,
                'icon' => '⚽',
                'badge' => 'IA Prédictive',
                'features' => [
                    'Analyse IA multi-sport',
                    'Alertes Discord instantanées',
                    'Statistiques & historique détaillé',
                    'Odds optimisés en temps réel',
                ],
                'whop_monthly_id' => 'plan_pronostics_monthly',
                'whop_annual_id' => 'plan_pronostics_annual',
            ],
            [
                'name' => 'Amazon Deals',
                'slug' => 'amazon',
                'description' => 'Détectez les erreurs de prix et les meilleures offres Amazon avant tout le monde. Profitez des opportunités en quelques secondes.',
                'monthly_price' => 19.99,
                'annual_price' => 190.00,
                'icon' => '🛒',
                'badge' => '🔥 Populaire',
                'features' => [
                    'Détection erreurs de prix en temps réel',
                    'Alertes ultra-rapides (<5 secondes)',
                    'Filtres avancés (catégorie, remise)',
                    'Channels Discord privés exclusifs',
                ],
                'whop_monthly_id' => 'plan_amazon_monthly',
                'whop_annual_id' => 'plan_amazon_annual',
            ],
            [
                'name' => 'Vinted Web',
                'slug' => 'vinted-web',
                'description' => 'Automatisez vos achats/reventes Vinted avec notre dashboard web premium. Multi-comptes, proxys, sniping ultra-rapide.',
                'monthly_price' => 49.99,
                'annual_price' => 480.00,
                'icon' => '👗',
                'badge' => 'Pro Web',
                'features' => [
                    'Sniping automatique ultra-rapide',
                    'Dashboard web premium inclus',
                    'Gestion multi-comptes & proxys',
                    'Auto buy configurable',
                ],
                'whop_monthly_id' => 'plan_vinted_monthly',
                'whop_annual_id' => 'plan_vinted_annual',
            ],
            [
                'name' => 'Vinted Discord',
                'slug' => 'vinted-discord',
                'description' => 'Recevez des alertes en temps réel directement sur votre serveur Discord. Configuration simple et rapide.',
                'monthly_price' => 19.99,
                'annual_price' => 190.00,
                'icon' => '💬',
                'badge' => 'Discord Bot',
                'features' => [
                    'Alertes sans latence (<1s)',
                    'Filtres personnalisés par salon',
                    'Installation en 2 clics',
                    'Support multi-serveur',
                ],
                'whop_monthly_id' => 'plan_vinted_discord_monthly',
                'whop_annual_id' => 'plan_vinted_discord_annual',
            ],
        ];

        foreach ($services as $s) {
            $service = new Service();
            $service->setName($s['name']);
            $service->setSlug($s['slug']);
            $service->setDescription($s['description']);
            $service->setMonthlyPrice((string) $s['monthly_price']);
            $service->setAnnualPrice((string) $s['annual_price']);
            $service->setIcon($s['icon']);
            if (isset($s['badge'])) {
                $service->setBadgeText($s['badge']);
            }
            if (isset($s['features'])) {
                $service->setFeatures($s['features']);
            }
            $service->setWhopMonthlyPlanId($s['whop_monthly_id']);
            $service->setWhopAnnualPlanId($s['whop_annual_id']);
            $service->setIsActive(true);

            $manager->persist($service);
        }

        // bundle service stored separately, pricing from env
        $bundleMonthly = $_ENV['BUNDLE_MONTHLY_PRICE'] ?? '79';
        $bundleAnnual = $_ENV['BUNDLE_ANNUAL_PRICE'] ?? '750';
        $bundle = new Service();
        $bundle->setName('Accès illimité – Tous les services');
        $bundle->setSlug('bundle');
        $bundle->setDescription('Un tarif unique pour accéder à l’ensemble de nos services.');
        $bundle->setMonthlyPrice((string) $bundleMonthly);
        $bundle->setAnnualPrice((string) $bundleAnnual);
        $bundle->setIcon('🎁');
        $bundle->setBadgeText('🎁 Bundle');
        $bundle->setFeatures([
            'Accès à tous les services',
            'Support prioritaire',
            'Tarif le plus avantageux'
        ]);
        $bundle->setWhopMonthlyPlanId($_ENV['WHOP_PLAN_ID_ALL_TEST'] ?? null);
        $bundle->setWhopAnnualPlanId($_ENV['WHOP_PLAN_ID_ALL_TEST'] ?? null);
        $bundle->setIsActive(true);
        $manager->persist($bundle);

        // Create Admin User
        $admin = new \App\Entity\User();
        $admin->setEmail('admin@profitpulse.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('ProfitPulse');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsActive(true);
        $admin->setMustChangePassword(false);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));

        $manager->persist($admin);

        $manager->flush();
    }
}
