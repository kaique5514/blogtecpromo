<?php
header('Content-Type: application/json');

// ================= CONFIGURAÇÕES =================
$CLIENT_ID = '3752776027247742';
$CLIENT_SECRET = 'fxjR7uDe4yXlP5rMCeJ74lRy8wIAwzAL';
$ACCESS_TOKEN = 'APP_USR-3752776027247742-110109-93d87d66fc593a5cab267ea1b7065d6d-481406357';
$REFRESH_TOKEN = 'TG-69060685149dc5000129c684-481406357';
$TOKEN_FILE = 'token.json'; // arquivo para salvar tokens atualizados

// ================= FUNÇÃO PARA RENOVAR TOKEN =================
function refreshAccessToken(&$accessToken, &$refreshToken, $clientId, $clientSecret, $tokenFile){
    $url = "https://api.mercadolibre.com/oauth/token";
    $data = [
        "grant_type" => "refresh_token",
        "client_id" => $clientId,
        "client_secret" => $clientSecret,
        "refresh_token" => $refreshToken
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $response = curl_exec($ch);
    if(curl_errno($ch)){
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    $json = json_decode($response, true);
    if(isset($json['access_token'])){
        $accessToken = $json['access_token'];
        $refreshToken = $json['refresh_token'];
        file_put_contents($tokenFile, json_encode([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ]));
        return true;
    }
    return false;
}

// ================= CARREGAR TOKENS SALVOS =================
if(file_exists($TOKEN_FILE)){
    $tokens = json_decode(file_get_contents($TOKEN_FILE), true);
    $ACCESS_TOKEN = $tokens['access_token'] ?? $ACCESS_TOKEN;
    $REFRESH_TOKEN = $tokens['refresh_token'] ?? $REFRESH_TOKEN;
}

// ================= RECEBER PARÂMETRO DE BUSCA =================
$query = isset($_GET['q']) ? $_GET['q'] : '';
if (!$query) {
    echo json_encode(['results' => []]);
    exit;
}

// ================= FUNÇÃO PARA FAZER REQUISIÇÃO =================
function fetchFromML($url, $accessToken){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken"
    ]);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code'=>$httpcode, 'response'=>$response];
}

// ================= MONTAR URL DE BUSCA =================
$search_url = "https://api.mercadolibre.com/sites/MLB/search?q=" . urlencode($query) . "&limit=10";

// ================= TENTAR BUSCA =================
$result = fetchFromML($search_url, $ACCESS_TOKEN);

// ================= SE TOKEN EXPIRADO (401) =================
if($result['code'] == 401){
    if(refreshAccessToken($ACCESS_TOKEN, $REFRESH_TOKEN, $CLIENT_ID, $CLIENT_SECRET, $TOKEN_FILE)){
        $result = fetchFromML($search_url, $ACCESS_TOKEN);
    } else {
        echo json_encode(['error'=>'Não foi possível renovar o token.']);
        exit;
    }
}

// ================= TRATAR RESULTADOS =================
$data = json_decode($result['response'], true);
$results = [];
if(isset($data['results'])){
    foreach ($data['results'] as $item) {
        $results[] = [
            'id' => $item['id'],
            'title' => $item['title'],
            'price' => $item['price'],
            'permalink' => $item['permalink'],
            'thumbnail' => $item['thumbnail']
        ];
    }
}

echo json_encode(['results'=>$results]);
