<?php
namespace GaiaLumen\Chat;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Minimal Ratchet WS server for Chat Défis.
 * Protocol:
 *  - Client sends JSON: {type:"join", challenge_id:int}
 *  - Client sends JSON: {type:"typing", challenge_id:int, is_typing:bool}
 *  - Server broadcasts: {type:"typing", challenge_id:int, author:{name}, is_typing:bool}
 *  - Server can broadcast message events later (message:new/update/delete) after HTTP API writes.
 */
final class ChatServer implements MessageComponentInterface {
    /** @var \SplObjectStorage<ConnectionInterface, array> */
    private \SplObjectStorage $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void {
        $this->clients->attach($conn, [
            'rooms' => [], // challenge:<id> or channel:<id> => true
            'name' => 'Invité',
            'conn_id' => spl_object_id($conn),
        ]);
    }

    public function onClose(ConnectionInterface $conn): void {
        $meta = $this->clients[$conn] ?? ['rooms' => []];
        $this->clients->detach($conn);
        // Optionally broadcast typing stop for rooms
        foreach (array_keys($meta['rooms'] ?? []) as $roomId) {
            $payloadBase = $this->roomPayload($roomId);
            $this->broadcastToRoom($roomId, array_merge($payloadBase, [
                'type' => 'typing',
                'author' => ['name' => $meta['name'] ?? 'Invité'],
                'is_typing' => false,
            ]));
            $this->broadcastToRoom($roomId, array_merge($payloadBase, [
                'type' => 'webrtc:state',
                'author' => [
                    'name' => $meta['name'] ?? 'Invité',
                    'conn_id' => (int)($meta['conn_id'] ?? 0),
                ],
                'state' => 'offline',
            ]));
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void {
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg): void {
        $data = json_decode((string)$msg, true);
        if (!is_array($data)) return;
        $type = (string)($data['type'] ?? '');

        if ($type === 'hello') {
            $meta = $this->clients[$from];
            $meta['name'] = (string)($data['name'] ?? $meta['name']);
            $this->clients[$from] = $meta;
            return;
        }

        if ($type === 'join') {
            $roomId = $this->roomIdFromData($data);
            if ($roomId === '') return;
            $meta = $this->clients[$from];
            $meta['rooms'][$roomId] = true;
            $this->clients[$from] = $meta;
            $roomPayload = $this->roomPayload($roomId);
            $from->send(json_encode(array_merge($roomPayload, [
                'type' => 'joined',
                'conn_id' => (int)($meta['conn_id'] ?? 0),
            ])));
            $this->broadcastToRoom($roomId, array_merge($roomPayload, [
                'type' => 'webrtc:state',
                'author' => [
                    'name' => $meta['name'] ?? 'Invité',
                    'conn_id' => (int)($meta['conn_id'] ?? 0),
                ],
                'state' => 'ready',
            ]), $from);
            return;
        }

        if ($type === 'typing') {
            $roomId = $this->roomIdFromData($data);
            if ($roomId === '') return;
            $isTyping = (bool)($data['is_typing'] ?? false);
            $meta = $this->clients[$from];
            if (empty($meta['rooms'][$roomId])) return; // must join first

            $this->broadcastToRoom($roomId, array_merge($this->roomPayload($roomId), [
                'type' => 'typing',
                'author' => ['name' => $meta['name'] ?? 'Invité'],
                'is_typing' => $isTyping,
            ]), $from);
            return;
        }

        if (in_array($type, ['message:new', 'message:update', 'message:delete'], true)) {
            $roomId = $this->roomIdFromData($data);
            if ($roomId === '') return;
            $meta = $this->clients[$from];
            if (empty($meta['rooms'][$roomId])) return;

            $payload = array_merge($this->roomPayload($roomId), [
                'type' => $type,
                'author' => [
                    'name' => $meta['name'] ?? 'Invité',
                    'conn_id' => (int)($meta['conn_id'] ?? 0),
                ],
            ]);
            if (array_key_exists('message', $data)) $payload['message'] = $data['message'];
            if (array_key_exists('id', $data)) $payload['id'] = $data['id'];
            if (array_key_exists('body', $data)) $payload['body'] = $data['body'];

            $this->broadcastToRoom($roomId, $payload, $from);
            return;
        }

        if ($type === 'ai_alert') {
            // Uniquement si authentifié comme admin ou via un token secret (simulé ici par une clé simple)
            // Pour le projet, on permet le broadcast si le type est ai_alert
            $cid = (int)($data['challenge_id'] ?? 0);
            if ($cid <= 0) return;
            
            $this->broadcastToRoom($cid, [
                'type' => 'ai_alert',
                'challenge_id' => $cid,
                'severity' => $data['severity'] ?? 'info',
                'message' => $data['message'] ?? '',
                'analysis' => $data['analysis'] ?? null
            ]);
            return;
        }

        if (str_starts_with($type, 'webrtc:')) {
            $roomId = $this->roomIdFromData($data);
            if ($roomId === '') return;
            $meta = $this->clients[$from];
            if (empty($meta['rooms'][$roomId])) return;
            $targetConnId = (int)($data['target_conn_id'] ?? 0);
            $payload = array_merge($this->roomPayload($roomId), [
                'type' => $type,
                'author' => [
                    'name' => $meta['name'] ?? 'Invité',
                    'conn_id' => (int)($meta['conn_id'] ?? 0),
                ],
            ]);
            if (array_key_exists('sdp', $data)) $payload['sdp'] = $data['sdp'];
            if (array_key_exists('candidate', $data)) $payload['candidate'] = $data['candidate'];
            if (array_key_exists('state', $data)) $payload['state'] = $data['state'];
            if ($targetConnId > 0) $payload['target_conn_id'] = $targetConnId;

            $this->broadcastToRoom($roomId, $payload, $from, $targetConnId > 0 ? $targetConnId : null);
            return;
        }
    }

    private function roomIdFromData(array $data): string {
        $cid = (int)($data['challenge_id'] ?? 0);
        if ($cid > 0) return 'challenge:' . $cid;
        $channelId = trim((string)($data['channel_id'] ?? ''));
        if ($channelId !== '' && preg_match('/^[a-zA-Z0-9_-]{1,60}$/', $channelId)) {
            return 'channel:' . $channelId;
        }
        return '';
    }

    private function roomPayload(string $roomId): array {
        if (str_starts_with($roomId, 'challenge:')) {
            return ['challenge_id' => (int)substr($roomId, strlen('challenge:'))];
        }
        if (str_starts_with($roomId, 'channel:')) {
            return ['channel_id' => substr($roomId, strlen('channel:'))];
        }
        return [];
    }

    private function broadcastToRoom(string $roomId, array $payload, ?ConnectionInterface $exclude = null, ?int $targetConnId = null): void {
        $encoded = json_encode($payload);
        foreach ($this->clients as $client) {
            if ($exclude && $client === $exclude) continue;
            $meta = $this->clients[$client];
            if ($targetConnId !== null && (int)($meta['conn_id'] ?? 0) !== $targetConnId) continue;
            if (!empty($meta['rooms'][$roomId])) {
                $client->send($encoded);
            }
        }
    }
}

