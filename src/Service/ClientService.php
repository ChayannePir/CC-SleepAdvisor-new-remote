<?php

namespace App\Service;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service pour gérer les clients
 * Contient la logique métier pour les clients
 * 
 * @package App\Service
 */
class ClientService
{
    public function __construct(
        private ClientRepository $clientRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Créer un nouveau client (inscription)
     */
    public function registerClient(
        string $email,
        string $plainPassword,
        string $nom,
        string $adresse,
        string $telephone
    ): Client {
        $client = new Client();
        $client->setEmail($email)
            ->setNom($nom)
            ->setAdresse($adresse)
            ->setTelephone($telephone);

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($client, $plainPassword);
        $client->setPassword($hashedPassword);

        return $this->saveClient($client);
    }

    /**
     * Sauvegarder ou mettre à jour un client
     */
    public function saveClient(Client $client): Client
    {
        $errors = $this->validator->validate($client);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException('Validation des données échouée');
        }

        if (!$client->getId()) {
            $this->entityManager->persist($client);
        }
        
        $this->entityManager->flush();

        return $client;
    }

    /**
     * Supprimer un client
     */
    public function deleteClient(Client $client): void
    {
        $this->entityManager->remove($client);
        $this->entityManager->flush();
    }

    /**
     * Paginer les clients
     */
    public function paginateClients(int $page, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $qb = $this->clientRepository->createQueryBuilder('c');
        $total = (int) (clone $qb)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $clients = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();

        return [
            'items' => $clients,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Rechercher les clients
     */
    public function searchClients(string $search): array
    {
        return $this->clientRepository->searchByNomOrEmail($search);
    }
}
