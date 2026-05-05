<?php
// controller/ChatbotController.php
// Role: Receive chat messages from the frontend, forward to Python API composer,
//       and return the AI response.

require_once __DIR__ . '/../config.php';

// For now, we use a static user_id (1) until authentication is integrated.
// Later you will replace with $_SESSION['user_id'].
$staticUserId = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'ask') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'] ?? '';
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message is required']);
        exit;
    }
    
    // Prepare data to send to Python service
    $payload = [
        'user_id' => $staticUserId,
        'message' => $message,
        'email'   => $input['email'] ?? null,   // optional, can be fetched from session later
        'phone'   => $input['phone'] ?? null
    ];
    
    // Forward to Python (running on localhost:8000)
    $ch = curl_init('http://localhost:8000/chat');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        http_response_code(502);
        echo json_encode(['error' => 'AI service unavailable', 'details' => $response]);
        exit;
    }
    
    echo $response;
    exit;
}

// If the endpoint is called incorrectly
http_response_code(404);
echo json_encode(['error' => 'Not found']);