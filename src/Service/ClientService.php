<?php

namespace App\Service;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Service\Admin\PaginationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service pour gérer les clients
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

        $hashedPassword = $this->passwordHasher->hashPassword($client, $plainPassword);
        $client->setPassword($hashedPassword);

        return $this->saveClient($client);
    }

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

    public function deleteClient(Client $client): void
    {
        $this->entityManager->remove($client);
        $this->entityManager->flush();
    }

    /**
     * @return array{items: Client[], total: int, page: int, limit: int, pages: int, page_range: int[]}
     */
    public function paginateClients(int $page, int $limit = 20, ?string $search = null): array
    {
        $probe = $this->clientRepository->paginateAdmin(0, 1, $search);
        $meta = PaginationHelper::normalize($page, $limit, $probe['total']);
        $result = $this->clientRepository->paginateAdmin($meta['offset'], $meta['limit'], $search);

        return [
            'items' => $result['items'],
            'total' => $meta['total'],
            'page' => $meta['page'],
            'limit' => $meta['limit'],
            'pages' => $meta['pages'],
            'page_range' => PaginationHelper::pageRange($meta['page'], $meta['pages']),
        ];
    }

    /**
     * @deprecated Utiliser paginateClients avec paramètre search
     */
    public function searchClients(string $search): array
    {
        return $this->clientRepository->searchByNomOrEmail($search);
    }
}
