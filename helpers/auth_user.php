<?php
if (!function_exists('gl_ensure_session')) {
    function gl_ensure_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }
}

if (!function_exists('gl_current_user_id')) {
    function gl_current_user_id(array $input = []): int
    {
        gl_ensure_session();
        foreach ([
            $_SESSION['user']['id_utilisateur'] ?? null,
            $_SESSION['user_id'] ?? null,
            $input['id_utilisateur'] ?? null,
            $input['user_id'] ?? null,
            $input['id_user'] ?? null,
        ] as $value) {
            if (is_numeric($value) && (int)$value > 0) {
                return (int)$value;
            }
        }
        return 0;
    }
}

if (!function_exists('gl_current_user_role')) {
    function gl_current_user_role(): string
    {
        gl_ensure_session();
        return (string)($_SESSION['user']['role'] ?? $_SESSION['user_role'] ?? '');
    }
}

if (!function_exists('gl_is_admin')) {
    function gl_is_admin(): bool
    {
        return gl_current_user_role() === 'admin';
    }
}

if (!function_exists('gl_current_user_name')) {
    function gl_current_user_name(array $fallback = []): string
    {
        gl_ensure_session();
        $nom = trim((string)($_SESSION['user']['nom'] ?? $fallback['nom'] ?? ''));
        $prenom = trim((string)($_SESSION['user']['prenom'] ?? $fallback['prenom'] ?? ''));
        $full = trim($prenom . ' ' . $nom);
        return $full !== '' ? $full : trim((string)($fallback['nom_complet'] ?? $fallback['name'] ?? 'Utilisateur'));
    }
}

if (!function_exists('gl_current_user_email')) {
    function gl_current_user_email(array $fallback = []): string
    {
        gl_ensure_session();
        return trim((string)($_SESSION['user']['email'] ?? $fallback['email'] ?? ''));
    }
}
