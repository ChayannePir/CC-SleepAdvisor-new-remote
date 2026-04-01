<?php

namespace App\Entity;

use App\Repository\CommentaireReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité CommentaireReservation
 * Permet aux clients de laisser des commentaires spéciaux sur leurs réservations
 * (ajout de lits bébés, demandes spéciales, etc.)
 * 
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: CommentaireReservationRepository::class)]
class CommentaireReservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Le commentaire est requis')]
    #[Assert\Length(min: 5, max: 1000, minMessage: 'Le commentaire doit contenir au moins 5 caractères')]
    private ?string $contenu = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reservation $reservation = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['Demande spéciale', 'Réclamation', 'Remarque'], message: 'Type de commentaire invalide')]
    private ?string $type = 'Demande spéciale';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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
}
