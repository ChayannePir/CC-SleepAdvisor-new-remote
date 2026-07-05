<?php

namespace App\Exception;

use App\Entity\Chambre;

/**
 * Levée lorsqu'une chambre n'est plus disponible (conflit concurrent).
 */
class ChambreNotAvailableException extends ReservationException
{
    public static function forChambre(Chambre $chambre): self
    {
        $technical = sprintf(
            'Chambre #%d (%s, étage %d) indisponible',
            $chambre->getId() ?? 0,
            $chambre->getType(),
            $chambre->getEtage()
        );

        $user = sprintf(
            'La chambre « %s » (étage %d) n\'est plus disponible pour ces dates. Veuillez modifier votre sélection.',
            $chambre->getType(),
            $chambre->getEtage()
        );

        return new self($technical, $user);
    }
}
