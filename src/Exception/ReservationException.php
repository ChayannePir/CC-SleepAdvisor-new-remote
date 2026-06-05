<?php

namespace App\Exception;

/**
 * Exception métier liée au processus de réservation.
 */
class ReservationException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $userMessage = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Message affiché à l'utilisateur final (flash, formulaire).
     */
    public function getUserMessage(): string
    {
        return $this->userMessage !== '' ? $this->userMessage : $this->getMessage();
    }
}
