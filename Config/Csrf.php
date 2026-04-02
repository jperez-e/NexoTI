<?php
declare(strict_types=1);

final class Csrf
{
    public static function obtenerToken(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function obtenerTokenSolicitud(): string
    {
        // Fetch API envia el token por header; los formularios tradicionales lo mandan por POST.
        $tokenCabecera = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if ($tokenCabecera !== '') {
            return $tokenCabecera;
        }

        return (string) ($_POST['_token'] ?? '');
    }

    public static function esSolicitudValida(): bool
    {
        $tokenSesion = (string) ($_SESSION['csrf_token'] ?? '');
        $tokenSolicitud = self::obtenerTokenSolicitud();

        return $tokenSesion !== ''
            && $tokenSolicitud !== ''
            && hash_equals($tokenSesion, $tokenSolicitud);
    }
}
