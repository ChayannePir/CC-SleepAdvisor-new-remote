<?php

namespace App\Entity;

use App\Repository\GestionnaireRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Gestionnaire - Hérite de User pour l'authentification
 * Gestionnaire d'un hôtel avec droits administrateur
 * 
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: GestionnaireRepository::class)]
class Gestionnaire extends User
{
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Nom est requis')]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $nom = null;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'gestionnaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hotel $hotel = null;

    public function __construct()
    {
        parent::__construct();
        $this->setRoles(['ROLE_ADMIN', 'ROLE_GESTIONNAIRE']);
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(?Hotel $hotel): static
    {
        $this->hotel = $hotel;

        return $this;
    }
}
