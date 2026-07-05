<?php

namespace App\Service;

use App\Entity\Chambre;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service pour gérer le panier de réservation
 * Stocke les chambres sélectionnées en session
 *
 * @package App\Service
 */
class BasketReservationService
{
    private const BASKET_KEY = 'reservation_basket';

    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    /**
     * Obtenir la session courante
     */
    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    /**
     * Ajouter une chambre au panier
     */
    public function addChamber(Chambre $chambre, \DateTimeInterface $dateDebut, \DateTimeInterface $dateFin): void
    {
        $basket = $this->getBasket();

        // Créer une clé unique pour la chambre
        $chambreKey = sprintf('%d_%s_%s', $chambre->getId(), $dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d'));

        // Éviter les doublons
        if (!isset($basket[$chambreKey])) {
            $basket[$chambreKey] = [
                'chambre_id' => $chambre->getId(),
                'chambre_type' => $chambre->getType(),
                'chambre_etage' => $chambre->getEtage(),
                'chambre_nombreLits' => $chambre->getNombreLits(),
                'hotel_id' => $chambre->getHotel()->getId(),
                'hotel_nom' => $chambre->getHotel()->getNom(),
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin->format('Y-m-d'),
                'added_at' => date('Y-m-d H:i:s'),
            ];

            $this->getSession()->set(self::BASKET_KEY, $basket);
        }
    }

    /**
     * Retirer une chambre du panier
     */
    public function removeChamber(string $chambreKey): void
    {
        $basket = $this->getBasket();

        if (isset($basket[$chambreKey])) {
            unset($basket[$chambreKey]);
            $this->getSession()->set(self::BASKET_KEY, $basket);
        }
    }

    /**
     * Obtenir le panier complet
     */
    public function getBasket(): array
    {
        return $this->getSession()->get(self::BASKET_KEY, []);
    }

    /**
     * Vider le panier
     */
    public function clearBasket(): void
    {
        $this->getSession()->remove(self::BASKET_KEY);
    }

    /**
     * Obtenir le nombre de chambres dans le panier
     */
    public function getBasketCount(): int
    {
        return count($this->getBasket());
    }

    /**
     * Vérifier si le panier est vide
     */
    public function isEmpty(): bool
    {
        return $this->getBasketCount() === 0;
    }

    /**
     * Obtenir les informations du panier pour validation
     */
    public function getBasketInfo(): array
    {
        $basket = $this->getBasket();

        if (empty($basket)) {
            return [
                'count' => 0,
                'hotels' => [],
                'first_date_debut' => null,
                'last_date_fin' => null,
            ];
        }

        $hotels = [];
        $dates_debut = [];
        $dates_fin = [];

        foreach ($basket as $item) {
            if (!isset($hotels[$item['hotel_id']])) {
                $hotels[$item['hotel_id']] = $item['hotel_nom'];
            }
            $dates_debut[] = $item['date_debut'];
            $dates_fin[] = $item['date_fin'];
        }

        return [
            'count' => count($basket),
            'hotels' => $hotels,
            'first_date_debut' => min($dates_debut),
            'last_date_fin' => max($dates_fin),
        ];
    }
}

