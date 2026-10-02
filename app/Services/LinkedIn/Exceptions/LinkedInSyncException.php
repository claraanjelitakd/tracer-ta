<?php

namespace App\Services\LinkedIn\Exceptions;

use Exception;

/**
 * Exception umum untuk kegagalan operasional sinkronisasi profil LinkedIn
 * (misalnya masalah komunikasi jaringan, format data tidak sesuai, dsb.).
 */
class LinkedInSyncException extends Exception
{
    //
}
