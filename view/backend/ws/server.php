<?php
/**
 * Ratchet WebSocket server launcher.
 *
 * Run (from repo root):
 *   composer install
 *   php view/backend/ws/server.php
 *
 * Default: ws://127.0.0.1:8081
 */

require_once(__DIR__ . '/../../../vendor/autoload.php');

use GaiaLumen\Chat\ChatServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

$port = (int)($_ENV['GL_CHAT_WS_PORT'] ?? getenv('GL_CHAT_WS_PORT') ?: 8081);

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    $port
);

echo "GaiaLumen Chat WS running on ws://127.0.0.1:{$port}\n";
$server->run();
