<?php
declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function requestToken(): string
    {
        // Fetch API envia el token por header; los formularios tradicionales lo mandan por POST.
        $headerToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if ($headerToken !== '') {
            return $headerToken;
        }

        return (string) ($_POST['_token'] ?? '');
    }

    public static function isValidRequest(): bool
    {
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
        $requestToken = self::requestToken();

        return $sessionToken !== ''
            && $requestToken !== ''
            && hash_equals($sessionToken, $requestToken);
    }
}
