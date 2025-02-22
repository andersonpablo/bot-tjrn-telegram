<?php
// Substitua 'SEU_TOKEN_DO_BOT' pelo token do seu bot do Telegram
$token = '';
$chat_id = '';  // ID do chat ou username do grupo (você pode usar o seu chat privado também)

// Função para enviar a mensagem para o Telegram
function sendTelegramMessage($message, $token, $chat_id) {
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $params = [
        'chat_id' => $chat_id,
        'text' => $message
    ];

    // Inicializa o cURL
    $ch = curl_init();

    // Configurações do cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

    // Executa a requisição
    $response = curl_exec($ch);

    // Verifica se houve erro
    if(curl_errno($ch)) {
        echo "Erro ao enviar a mensagem: " . curl_error($ch);
    } else {
        echo "Mensagem enviada com sucesso!";
    }

    // Fecha o cURL
    curl_close($ch);
}

// Defina a URL da página
$url = 'https://plenariovirtual.tjrn.jus.br/processo.php?processo=521787&sessao=4078';

// Inicializa o cURL
$ch = curl_init();

// Configurações do cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Executa a requisição
$html = curl_exec($ch);

// Verifica se houve erro na requisição
if (curl_errno($ch)) {
    echo "Erro ao acessar a página!";
    exit;
}

// Fecha a sessão do cURL
curl_close($ch);

// Carrega o conteúdo da página HTML no DOM
$dom = new DOMDocument();
libxml_use_internal_errors(true); // Ignora erros ao carregar HTML
$dom->loadHTML($html);
libxml_clear_errors();

// Inicializa o XPath
$xpath = new DOMXPath($dom);

// Variáveis de XPaths
$juiz_xpath = [
    '/html/body/div[4]/div/div[14]/div/div/div/div',  // Juiz 1
    '/html/body/div[4]/div/div[15]/div/div/div/div',
    '/html/body/div[4]/div/div[15]/div[1]/div/div/div', // Juiz 4 (ou alternativa para o Gab. do Juiz Fábio)
    '/html/body/div[4]/div/div[15]/div[2]/div/div/div',  // Juiz 2 (Gab. do Juiz Reynaldo Odilio Martins Soares)
    '/html/body/div[4]/div/div[16]/div/div/div/div',   // Juiz 3 (Gab. do Juiz Fábio Antônio Correia Filgueira)
];

$voto_xpath = [
    '/html/body/div[4]/div/h3[2]',  // Voto "Não provido"
    '/html/body/div[4]/div/h3[3]',  // Voto "Acompanha o relator"
    '/html/body/div[4]/div/h3[4]',  // Voto "Não proferido"
    '/html/body/div[4]/div/h3[5]',  // Voto alternativo ou "outros" votos
    '/html/body/div[4]/div/h3[6]',  // Outro possível voto
];

// Formatar a mensagem
$message = "Votos:\n";

// Laço para iterar os juízes e votos
for ($i = 0; $i < count($juiz_xpath); $i++) {
    // Verifica se o índice do voto existe
    if (isset($voto_xpath[$i])) {
        // Busca o juiz usando o XPath
        $juiz_node = $xpath->query($juiz_xpath[$i]);
        $juiz = ($juiz_node->length > 0 && $juiz_node->item(0)->textContent != '') 
                ? trim($juiz_node->item(0)->textContent) 
                : 'Juiz desconhecido';

        // Busca o voto usando o XPath
        $voto_node = $xpath->query($voto_xpath[$i]);
        $voto = ($voto_node->length > 0 && $voto_node->item(0)->textContent != '') 
                ? trim($voto_node->item(0)->textContent) 
                : 'Voto desconhecido';

        // Se ambos juiz e voto estiverem como 'desconhecido', não exibe a informação
        if ($voto !== 'Voto desconhecido' && $juiz !== 'Juiz desconhecido') {
            $message .= "$voto - Juiz: $juiz\n";
        }
    }
}

// Enviar a mensagem via Telegram, se houver votos válidos
if ($message !== "Votos:\n") {
    sendTelegramMessage($message, $token, $chat_id);
} else {
    echo "Nenhum voto encontrado para enviar.";
}

$page = $_SERVER['PHP_SELF'];
$sec = "1800";
header("Refresh: $sec; url=$page");

?>
