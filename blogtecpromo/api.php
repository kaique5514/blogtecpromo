<?php
header('Content-Type: application/json');

// Configurações
$ACCESS_TOKEN = 'SEU_ACCESS_TOKEN_AQUI'; // Substitua pelo seu token do Mercado Livre

// Recebe o parâmetro de busca
$query = isset($_GET['q']) ? $_GET['q'] : '';

if (!$query) {
    echo json_encode(['results' => []]);
    exit;
}

// Para este exemplo, vamos buscar pelo endpoint de items
// Se quiser fazer pesquisa por palavra, use /sites/MLB/search?q=...
// Aqui vamos buscar 10 itens de exemplo pelo termo pesquisado
$search_url = "https://api.mercadolibre.com/sites/MLB/search?q=" . urlencode($query) . "&limit=10";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $search_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $ACCESS_TOKEN"
]);

$response = curl_exec($ch);

if(curl_errno($ch)){
    echo json_encode(['error' => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

// Transformar os itens no formato que seu JS espera
$results = [];
if (isset($data['results'])) {
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

echo json_encode(['results' => $results]);
