<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Reservation
 * Représente une réservation avec relation ternaire vers Client, Hotel et Chambre
 * 
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private ?string $numeroReservation = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: Hotel::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hotel $hotel = null;

    /**
     * @var Collection<int, Chambre>
     */
    #[ORM\ManyToMany(targetEntity: Chambre::class, inversedBy: 'reservations')]
    #[ORM\JoinTable(name: 'reservation_chambre')]
    #[Assert\Count(min: 1, minMessage: 'Au moins une chambre doit être réservée')]
    private Collection $chambres;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'Date de début est requise')]
    #[Assert\Type(\DateTimeInterface::class)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'Date de fin est requise')]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Assert\GreaterThan(propertyPath: 'dateDebut', message: 'La date de fin doit être après la date de début')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['En attente', 'Confirmée', 'Annulée'], message: 'Statut invalide')]
    private ?string $statut = 'En attente';

    /**
     * @var Collection<int, CommentaireReservation>
     */
    #[ORM\OneToMany(targetEntity: CommentaireReservation::class, mappedBy: 'reservation', cascade: ['remove'])]
    private Collection $commentaires;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->chambres = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->numeroReservation = 'RES-' . strtoupper(uniqid());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroReservation(): ?string
    {
        return $this->numeroReservation;
    }

    public function setNumeroReservation(string $numeroReservation): static
    {
        $this->numeroReservation = $numeroReservation;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

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
        }

        return $this;
    }

    public function removeChambre(Chambre $chambre): static
    {
        $this->chambres->removeElement($chambre);

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * @return Collection<int, CommentaireReservation>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(CommentaireReservation $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setReservation($this);
        }

        return $this;
    }

    public function removeCommentaire(CommentaireReservation $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getReservation() === $this) {
                $commentaire->setReservation(null);
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
