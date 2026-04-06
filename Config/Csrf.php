<?php
/*
  Clase para gestionar tokens CSRF.
    Proporciona métodos para generar un token único por sesión,
    obtener el token enviado en la solicitud y 
    validar que el token de la solicitud 
    coincida con el token almacenado en la sesión. 
    Esto ayuda a proteger la aplicación contra 
    ataques de falsificación de solicitudes 
    entre sitios (CSRF) al asegurar que las 
    solicitudes POST provengan de fuentes legítimas.
 */

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
