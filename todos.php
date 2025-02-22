<html>
    <head>
    <meta charset="UTF-8">
</head>
<?php
// Aumenta o tempo máximo de execução para 300 segundos (5 minutos)
header('Content-Type: text/html; charset=UTF-8');
ini_set('max_execution_time', 600);

// URL da página de sessão (use a URL real da página)
$url_sessao = 'https://plenariovirtual.tjrn.jus.br/sessao.php?sessao=4078';

// Função para fazer requisições HTTP com cURL
function fetchUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirecionamentos
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Função para extrair links dos processos da página de sessão
function extractProcessLinks($html) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Ignora erros de HTML inválido
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query("//tr[contains(@onclick, 'processo.php?processo=')]");

    $links = [];

    foreach ($nodes as $node) {
        // Extrai o link do processo do atributo onclick
        preg_match("/window\.location='(.*?)'/", $node->getAttribute('onclick'), $matches);
        if (isset($matches[1])) {
            $links[] = 'https://plenariovirtual.tjrn.jus.br/' . $matches[1];
        }
    }

    return $links;
}

// Função para extrair informações de um processo (juízes e votos)
function extractProcessInfo($html) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Ignora erros de HTML inválido
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // XPaths para juízes e votos
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

    $info = [];

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
                $info[] = "$voto - Juiz: $juiz";
            }
        }
    }

    return $info;
}

// Início do script
try {
    // Faz a requisição para a página de sessão
    $html_sessao = fetchUrl($url_sessao);
    if (!$html_sessao) {
        throw new Exception("Erro ao acessar a página de sessão.");
    }

    // Extrai os links dos processos
    $process_links = extractProcessLinks($html_sessao);
    if (empty($process_links)) {
        throw new Exception("Nenhum processo encontrado nesta página.");
    }

    echo "Número de processos encontrados: " . count($process_links) . "<br><br>";

    // Divide os links em lotes de 30
    $process_batches = array_chunk($process_links, 20);

    // Itera sobre cada lote
    foreach ($process_batches as $batch) {
        foreach ($batch as $link) {
            echo "Processo: <a href='$link' target='_blank'>$link</a><br>";

            // Faz a requisição para a página do processo
            $html_processo = fetchUrl($link);
            if (!$html_processo) {
                echo "Erro ao acessar o processo: $link<br><br>";
                continue;
            }

            // Extrai as informações do processo
            $info = extractProcessInfo($html_processo);
            if (!empty($info)) {
                echo "Placar:<br>";
                foreach ($info as $line) {
                    echo "$line<br>";
                }
            } else {
                echo "Nenhuma informacao encontrada.<br>";
            }

            echo "<br>";

            // Delay entre as requisições (1 segundo)
            sleep(1);
        }

        // Delay entre os lotes (5 segundos)
        sleep(5);
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
</html>
