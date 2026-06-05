<?php

namespace App\Exception;

/**
 * Erreur de validation métier sur une réservation (dates, chambres, hôtel).
 */
class ReservationValidationException extends ReservationException
{
    public static function emptyChambres(): self
    {
        return new self(
            'Aucune chambre dans la réservation',
            'Vous devez sélectionner au moins une chambre.'
        );
    }

    public static function invalidDates(): self
    {
        return new self(
            'Date de fin antérieure ou égale à la date de début',
            'La date de départ doit être postérieure à la date d\'arrivée.'
        );
    }

    public static function invalidChambre(): self
    {
        return new self(
            'Objet chambre invalide',
            'Une des chambres sélectionnées est invalide.'
        );
    }

    public static function chambreWrongHotel(string $type, int $etage): self
    {
        return new self(
            sprintf('Chambre %s étage %d hors hôtel', $type, $etage),
            sprintf('La chambre « %s » (étage %d) n\'appartient pas à l\'hôtel choisi.', $type, $etage)
        );
    }

    public static function validationFailed(): self
    {
        return new self(
            'Échec validation entité Reservation',
            'Les données de réservation sont invalides. Vérifiez les informations saisies.'
        );
    }
}
