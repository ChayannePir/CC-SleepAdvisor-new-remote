<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Gestionnaire;
use App\Entity\Hotel;
use App\Entity\Chambre;
use App\Entity\Reservation;
use App\Entity\CommentaireReservation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * DataFixtures - Données de test pour le développement
 * 
 * Ce fichier fournit des données initiales pour la base de données.
 * À exécuter avec: php bin/console doctrine:fixtures:load
 */
class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Créer des hôtels
        $hotelParis = new Hotel();
        $hotelParis->setNom('Hotel Grand Paris');
        $hotelParis->setAdresse('123 Avenue des Champs-Élysées, 75008 Paris');
        $hotelParis->setCategorie('*****');
        $manager->persist($hotelParis);

        $hotelLyon = new Hotel();
        $hotelLyon->setNom('Hotel Lyon Prestige');
        $hotelLyon->setAdresse('456 Rue de la République, 69001 Lyon');
        $hotelLyon->setCategorie('***');
        $manager->persist($hotelLyon);

        // 2. Créer des gestionnaires (admin hôtel)
        $gestionnaireParis = new Gestionnaire();
        $gestionnaireParis->setEmail('gestionnaire@hotelparis.com');
        $gestionnaireParis->setNom('Jean Dupont');
        $gestionnaireParis->setTelephone('0123456789');
        $gestionnaireParis->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($gestionnaireParis, 'admin123');
        $gestionnaireParis->setPassword($hashedPassword);
        $gestionnaireParis->setHotel($hotelParis);
        $manager->persist($gestionnaireParis);

        // 3. Créer des clients
        $client1 = new Client();
        $client1->setEmail('client1@example.com');
        $client1->setNom('Alice Martin');
        $client1->setAdresse('789 Rue de la Paix, 75001 Paris');
        $client1->setTelephone('0234567890');
        $client1->setRoles(['ROLE_CLIENT']);
        $hashedPassword = $this->passwordHasher->hashPassword($client1, 'client123');
        $client1->setPassword($hashedPassword);
        $manager->persist($client1);

        $client2 = new Client();
        $client2->setEmail('client2@example.com');
        $client2->setNom('Bob Bernard');
        $client2->setAdresse('321 Boulevard Saint-Germain, 75005 Paris');
        $client2->setTelephone('0345678901');
        $client2->setRoles(['ROLE_CLIENT']);
        $hashedPassword = $this->passwordHasher->hashPassword($client2, 'client123');
        $client2->setPassword($hashedPassword);
        $manager->persist($client2);

        // 4. Créer des chambres pour Hotel Paris
        $chambre1 = new Chambre();
        $chambre1->setType('Single');
        $chambre1->setEtage(1);
        $chambre1->setNombreLits(1);
        $chambre1->setHotel($hotelParis);
        $manager->persist($chambre1);

        $chambre2 = new Chambre();
        $chambre2->setType('Double');
        $chambre2->setEtage(2);
        $chambre2->setNombreLits(2);
        $chambre2->setHotel($hotelParis);
        $manager->persist($chambre2);

        $chambre3 = new Chambre();
        $chambre3->setType('Suite');
        $chambre3->setEtage(3);
        $chambre3->setNombreLits(2);
        $chambre3->setHotel($hotelParis);
        $manager->persist($chambre3);

        // 5. Créer des réservations
        $now = new \DateTime();
        $tomorrow = (new \DateTime())->modify('+1 day');
        $inThreeDays = (new \DateTime())->modify('+3 days');
        $inFiveDays = (new \DateTime())->modify('+5 days');

        $reservation1 = new Reservation();
        $reservation1->setClient($client1);
        $reservation1->setHotel($hotelParis);
        $reservation1->setNumeroReservation('RES-' . uniqid());
        $reservation1->setDateDebut($tomorrow);
        $reservation1->setDateFin($inThreeDays);
        $reservation1->setStatut('Confirmée');
        $reservation1->addChambre($chambre1);
        $manager->persist($reservation1);

        $reservation2 = new Reservation();
        $reservation2->setClient($client2);
        $reservation2->setHotel($hotelParis);
        $reservation2->setNumeroReservation('RES-' . uniqid());
        $reservation2->setDateDebut($inThreeDays);
        $reservation2->setDateFin($inFiveDays);
        $reservation2->setStatut('En attente');
        $reservation2->addChambre($chambre2);
        $manager->persist($reservation2);

        // 6. Créer des commentaires
        $commentaire = new CommentaireReservation();
        $commentaire->setContenu('Chambre très confortable, service excellent');
        $commentaire->setType('Remarque');
        $commentaire->setReservation($reservation1);
        $manager->persist($commentaire);

        // 7. Sauvegarder tout
        $manager->flush();

        echo "✓ Données de test chargées avec succès!\n";
        echo "  - 2 hôtels créés\n";
        echo "  - 1 gestionnaire créé\n";
        echo "  - 2 clients créés\n";
        echo "  - 3 chambres créées\n";
        echo "  - 2 réservations créées\n";
        echo "  - 1 commentaire créé\n";
        echo "\nUtilisateurs de test:\n";
        echo "  Admin: gestionnaire@hotelparis.com / admin123\n";
        echo "  Client 1: client1@example.com / client123\n";
        echo "  Client 2: client2@example.com / client123\n";
    }
}
