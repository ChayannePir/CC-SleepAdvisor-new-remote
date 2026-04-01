<?php

namespace App\Entity;

use App\Repository\HotelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Hotel
 * Représente un hôtel avec ses catégories et chambres
 * 
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: HotelRepository::class)]
class Hotel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Nom de l\'hôtel est requis')]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Adresse est requise')]
    private ?string $adresse = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Catégorie est requise')]
    #[Assert\Choice(choices: ['*', '**', '***', '****', '*****'], message: 'Catégorie invalide')]
    private ?string $categorie = null;

    /**
     * @var Collection<int, Chambre>
     */
    #[ORM\OneToMany(targetEntity: Chambre::class, mappedBy: 'hotel', cascade: ['remove'])]
    private Collection $chambres;

    /**
     * @var Collection<int, Gestionnaire>
     */
    #[ORM\OneToMany(targetEntity: Gestionnaire::class, mappedBy: 'hotel', cascade: ['remove'])]
    private Collection $gestionnaires;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'hotel', cascade: ['remove'])]
    private Collection $reservations;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->chambres = new ArrayCollection();
        $this->gestionnaires = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * @return Collection<int, Chambre>
     */
    public function getChambres(): Collection
    {
        return $this->chambres;
    }

    public function addChambre(Chambre $chambre): static
    {
        if (!$this->chambres->contains($chambre)) {
            $this->chambres->add($chambre);
            $chambre->setHotel($this);
        }

        return $this;
    }

    public function removeChambre(Chambre $chambre): static
    {
        if ($this->chambres->removeElement($chambre)) {
            if ($chambre->getHotel() === $this) {
                $chambre->setHotel(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Gestionnaire>
     */
    public function getGestionnaires(): Collection
    {
        return $this->gestionnaires;
    }

    public function addGestionnaire(Gestionnaire $gestionnaire): static
    {
        if (!$this->gestionnaires->contains($gestionnaire)) {
            $this->gestionnaires->add($gestionnaire);
            $gestionnaire->setHotel($this);
        }

        return $this;
    }

    public function removeGestionnaire(Gestionnaire $gestionnaire): static
    {
        if ($this->gestionnaires->removeElement($gestionnaire)) {
            if ($gestionnaire->getHotel() === $this) {
                $gestionnaire->setHotel(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setHotel($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getHotel() === $this) {
                $reservation->setHotel(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
