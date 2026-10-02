<?php

namespace App\Services\LinkedIn\Exceptions;

use Exception;

/**
 * Exception yang dilempar ketika profil LinkedIn tidak ditemukan di sistem penyedia.
 * Mensimulasikan status HTTP 404 / PROFILE_NOT_FOUND.
 */
class LinkedInProfileNotFoundException extends Exception
{
    public function __construct(string $username, int $code = 404, ?Exception $previous = null)
    {
        parent::__construct("Profil LinkedIn untuk pengguna '{$username}' tidak ditemukan.", $code, $previous);
    }
}
