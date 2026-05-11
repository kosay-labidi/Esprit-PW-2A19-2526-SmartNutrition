<?php
/* ============================================================
   model/translate_model.php
   Logique de traduction via Google Translate (gratuit, sans clé).
   ============================================================ */

/**
 * Traduit un tableau de textes du français vers la langue cible.
 * Retourne un tableau de traductions dans le même ordre.
 */
function translate_texts(array $texts, string $targetLang): array {
    $results = [];
    foreach ($texts as $text) {
        $text = (string)$text;
        $trimmed = trim($text);

        /* Ignorer les chaînes vides ou purement numériques */
        if ($trimmed === '' || preg_match('/^[\d\s.,;:!?\-\/%°]+$/', $trimmed)) {
            $results[] = $text;
            continue;
        }

        $translated = _google_translate($trimmed, 'fr', $targetLang);
        $results[]  = $translated !== '' ? $translated : $text;

        usleep(60000); /* 60ms entre appels pour éviter le blocage */
    }
    return $results;
}

/**
 * Appelle l'endpoint non officiel de Google Translate.
 * Gratuit, sans clé API, usage modéré.
 */
function _google_translate(string $text, string $sl, string $tl): string {
    $url = sprintf(
        'https://translate.googleapis.com/translate_a/single?client=gtx&sl=%s&tl=%s&dt=t&q=%s',
        urlencode($sl),
        urlencode($tl),
        urlencode($text)
    );

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $httpCode !== 200 || !$response) {
        return ''; /* retour vide = le JS garde le texte original */
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data[0])) return '';

    /* Reconstituer depuis les segments */
    $out = '';
    foreach ($data[0] as $seg) {
        if (!empty($seg[0])) $out .= $seg[0];
    }
    return trim($out);
}
