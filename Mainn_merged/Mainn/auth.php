<?php
/**
 * auth.php — Garde de session réutilisable
 * Usage : require_once __DIR__ . '/../../auth.php';
 *         requireAuth();       // Vérifie que l'utilisateur est connecté
 *         requireAdmin();      // Vérifie qu'il est admin
 *         getSessionUser();    // Retourne les données de l'utilisateur connecté
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAuth(): void
{
    if (empty($_SESSION['user'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non authentifié. Veuillez vous connecter.']);
        exit();
    }
}

function requireAdmin(): void
{
    requireAuth();
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé. Droits administrateur requis.']);
        exit();
    }
}

function getSessionUser(): ?array
{
    return $_SESSION['user'] ?? null;
}