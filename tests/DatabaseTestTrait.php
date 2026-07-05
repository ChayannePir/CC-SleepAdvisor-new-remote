<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Crée le schéma et charge les fixtures (une fois par processus de test).
 */
trait DatabaseTestTrait
{
    private static bool $databaseReady = false;

    protected static function ensureTestDatabase(): void
    {
        if (self::$databaseReady) {
            return;
        }

        if (!static::$booted) {
            static::bootKernel();
        }

        try {
            /** @var EntityManagerInterface $em */
            $em = static::getContainer()->get('doctrine')->getManager();
            $metadata = $em->getMetadataFactory()->getAllMetadata();
            $schemaTool = new SchemaTool($em);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);

            /** @var AppFixtures $fixtures */
            $fixtures = static::getContainer()->get(AppFixtures::class);
            $fixtures->load($em);
        } catch (\Throwable $e) {
            static::markTestSkipped('Base de données indisponible pour les tests fonctionnels : ' . $e->getMessage());
        }

        self::$databaseReady = true;
    }
}
