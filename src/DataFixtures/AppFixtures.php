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
        //Création des hôtels
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

        $hotelNice = new Hotel();
        $hotelNice->setNom('Le Negresco');
        $hotelNice->setAdresse('37 Promenade des Anglais, 06000 Nice');
        $hotelNice->setCategorie('*');
        $manager->persist($hotelNice);

        $hotelMarseille = new Hotel();
        $hotelMarseille->setNom('InterContinental Marseille - Hotel Dieu');
        $hotelMarseille->setAdresse('1 Place Daviel, 13002 Marseille');
        $hotelMarseille->setCategorie('***');
        $manager->persist($hotelMarseille);

        $hotelCarcassonne = new Hotel();
        $hotelCarcassonne->setNom('Hôtel de la Cité');
        $hotelCarcassonne->setAdresse('Place Saint-Nazaire, 11000 Carcassonne');
        $hotelCarcassonne->setCategorie('****');
        $manager->persist($hotelCarcassonne);

        $hotelDordogne = new Hotel();
        $hotelDordogne->setNom('Château de la Treyne');
        $hotelDordogne->setAdresse('Château de la Treyne, 46200 Lacave');
        $hotelDordogne->setCategorie('****');
        $manager->persist($hotelDordogne);

        // Gestionnaires (admin hôtel)
        $gestionnaireParis = new Gestionnaire();
        $gestionnaireParis->setEmail('gestionnaire@hotelparis.com');
        $gestionnaireParis->setNom('Jean Dupont');
        $gestionnaireParis->setTelephone('0123456789');
        $gestionnaireParis->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($gestionnaireParis, 'admin123');
        $gestionnaireParis->setPassword($hashedPassword);
        $gestionnaireParis->setHotel($hotelParis);
        $manager->persist($gestionnaireParis);

        $gestionnaireMarseille = new Gestionnaire();
        $gestionnaireMarseille->setEmail('admin.marseille@hotel.fr');
        $gestionnaireMarseille->setNom('Marc Dupont');
        $gestionnaireMarseille->setTelephone('0601010101');
        $gestionnaireMarseille->setRoles(['ROLE_ADMIN']);
        $gestionnaireMarseille->setPassword($this->passwordHasher->hashPassword($gestionnaireMarseille, '123admin'));
        $gestionnaireMarseille->setHotel($hotelMarseille);
        $manager->persist($gestionnaireMarseille);

        // Créer des clients
        $client1 = new Client();
        $client1->setEmail('alice.martin@dawan.com');
        $client1->setNom('Alice Martin');
        $client1->setAdresse('789 Rue de la Paix, 75001 Paris');
        $client1->setTelephone('0234567890');
        $client1->setRoles(['ROLE_CLIENT']);
        $hashedPassword = $this->passwordHasher->hashPassword($client1, 'alice26');
        $client1->setPassword($hashedPassword);
        $manager->persist($client1);

        $client2 = new Client();
        $client2->setEmail('bob.dylan@yahoo.com');
        $client2->setNom('Bob Dylan');
        $client2->setAdresse('321 Boulevard Saint-Germain, 75005 Paris');
        $client2->setTelephone('0345678901');
        $client2->setRoles(['ROLE_CLIENT']);
        $hashedPassword = $this->passwordHasher->hashPassword($client2, 'bob26bob');
        $client2->setPassword($hashedPassword);
        $manager->persist($client2);

        $client3 = new Client();
        $client3->setEmail('camille.dubois@gmail.com');
        $client3->setNom('Camille Dubois');
        $client3->setAdresse('55 Rue de la Victoire, 69003 Lyon');
        $client3->setTelephone('0456789012');
        $client3->setRoles(['ROLE_CLIENT']);
        $hashedPassword = $this->passwordHasher->hashPassword($client3, 'camille99');
        $client3->setPassword($hashedPassword);
        $manager->persist($client3);

        //Créer des chambres pour Hotel Paris
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

        // Chambre Hotel Lyon
        $chambreL1 = new Chambre();
        $chambreL1->setType('Suite');
        $chambreL1->setEtage(5);
        $chambreL1->setNombreLits(2);
        $chambreL1->setHotel($hotelLyon);
        $manager->persist($chambreL1);

        $chambreL2 = new Chambre();
        $chambreL2->setType('Single');
        $chambreL2->setEtage(1);
        $chambreL2->setNombreLits(1);
        $chambreL2->setHotel($hotelLyon);
        $manager->persist($chambreL2);

       // Chambre Hotel Ca rcassone
        $chambreC1 = new Chambre();
        $chambreC1->setType('Suite');
        $chambreC1->setEtage(5);
        $chambreC1->setNombreLits(2);
        $chambreC1->setHotel($hotelCarcassonne);
        $manager->persist($chambreC1);

        $chambreC2 = new Chambre();
        $chambreC2->setType('Single');
        $chambreC2->setEtage(1);
        $chambreC2->setNombreLits(1);
        $chambreC2->setHotel($hotelCarcassonne);
        $manager->persist($chambreC2);

        // Chambre Hotel Dordogne
        $chambreD1 = new Chambre();
        $chambreD1->setType('Double');
        $chambreD1->setEtage(4);
        $chambreD1->setNombreLits(2);
        $chambreD1->setHotel($hotelDordogne);
        $manager->persist($chambreD1);

        $chambreD2 = new Chambre();
        $chambreD2->setType('Deluxe');
        $chambreD2->setEtage(3);
        $chambreD2->setNombreLits(2);
        $chambreD2->setHotel($hotelDordogne);
        $manager->persist($chambreC2);

        // Chambre Hotel Marseille
        $chambreM1 = new Chambre();
        $chambreM1->setType('Suite');
        $chambreM1->setEtage(5);
        $chambreM1->setNombreLits(2);
        $chambreM1->setHotel($hotelMarseille);
        $manager->persist($chambreM1);

        $chambreM2 = new Chambre();
        $chambreM2->setType('Single');
        $chambreM2->setEtage(1);
        $chambreM2->setNombreLits(1);
        $chambreM2->setHotel($hotelMarseille);
        $manager->persist($chambreM2);

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

        $reservation3 = new Reservation();
        $reservation3->setClient($client3);
        $reservation3->setHotel($hotelMarseille);
        $reservation3->setNumeroReservation('RES-' . uniqid());
        $reservation3->setDateDebut($inThreeDays);
        $reservation3->setDateFin($inFiveDays);
        $reservation3->setStatut('En attente');
        $reservation3->addChambre($chambreM2);
        $manager->persist($reservation3);

        $reservation4 = new Reservation();
        $reservation4->setClient($client3);
        $reservation4->setHotel($hotelDordogne);
        $reservation4->setNumeroReservation('RES-' . uniqid());
        $reservation4->setDateDebut($now);
        $reservation4->setDateFin($inFiveDays);
        $reservation4->setStatut('En attente');
        $reservation4->addChambre($chambreD1);
        $manager->persist($reservation4);

        //Créer des commentaires
        $commentaire = new CommentaireReservation();
        $commentaire->setContenu('Chambre très confortable, service excellent');
        $commentaire->setType('Remarque');
        $commentaire->setReservation($reservation1);
        $manager->persist($commentaire);

        //Sauvegarder tout
        $manager->flush();

        echo "✓ Données de test chargées avec succès!\n";
        echo "  - 6 hôtels créés\n";
        echo "  - 2 gestionnaires créés\n";
        echo "  - 3 clients créés\n";
        echo "  - 12 chambres créées\n";
        echo "  - 4 réservations créées\n";
        echo "  - 1 commentaire créé\n";
        echo "\nUtilisateurs de test:\n";
        echo "  Admin 1: gestionnaire@hotelparis.com / admin123\n";
        echo "  Admin 2: admin.marseille@hotel.fr / 123admin\n";
        echo "  Client 1 (Alice): alice.martin@dawan.com / alice26\n";
        echo "  Client 2 (Bob): bob.dylan@yahoo.com / bob26bob\n";
        echo "  Client 3 (Camille): camille.dubois@gmail.com / camille99\n";
    }
}
