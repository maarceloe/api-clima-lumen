<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


//rotas

// Rota para buscar o clima atual
$router->get('/api/clima-atual', 'ClimaController@climaAtual');

// Rota para buscar a previsão de 7 dias
$router->get('/api/previsao', 'ClimaController@previsaoSeteDias');

// Rota para comparar temperaturas (dia anterior e atual)
$router->get('/api/comparar-temperatura', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat');
    $lon = $request->get('lon');

    if (!$lat || !$lon || !is_numeric($lat) || !is_numeric($lon)) {
        return response()->json(['error' => 'Coordenadas inválidas ou ausentes'], 400);
    }

    $ontem = date('Y-m-d', strtotime('-1 day'));
    $hoje = date('Y-m-d');

    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&start_date={$ontem}&end_date={$hoje}&daily=temperature_2m_max&timezone=auto";

    try {
        $response = file_get_contents($url);

        // Verifica se a resposta é válida
        if ($response === false) {
            return response()->json(['error' => 'Erro ao conectar à API de previsão'], 500);
        }

        $dados = json_decode($response, true);

        // Verifica se os dados necessários estão disponíveis
        if (!isset($dados['daily']['temperature_2m_max']) || count($dados['daily']['temperature_2m_max']) < 2) {
            return response()->json(['error' => 'Dados de temperatura não disponíveis ou incompletos'], 500);
        }

        $ontemTemp = $dados['daily']['temperature_2m_max'][0];
        $hojeTemp = $dados['daily']['temperature_2m_max'][1];

        $comparacao = $hojeTemp > $ontemTemp
            ? "Hoje está mais quente que ontem."
            : ($hojeTemp < $ontemTemp
                ? "Hoje está mais frio que ontem."
                : "Hoje está com a mesma temperatura de ontem.");

        return response()->json([
            'ontem' => "{$ontemTemp}°C",
            'hoje' => "{$hojeTemp}°C",
            'comparacao' => $comparacao
        ]);
    } catch (\Exception $e) {
        // Retorna um JSON válido em caso de erro
        return response()->json(['error' => 'Erro ao buscar os dados de temperatura', 'details' => $e->getMessage()], 500);
    }
});

// Rota para buscar sugestões de cidades
$router->get('/api/sugestoes', function (\Illuminate\Http\Request $request) {
    $query = $request->get('query');

    if (!$query || strlen($query) < 2) {
        return response()->json([]); // Retorna um array vazio se a consulta for muito curta
    }

    // URL da API Open-Meteo para buscar cidades
    $url = "https://geocoding-api.open-meteo.com/v1/search?name={$query}&count=5&language=pt&format=json";

    try {
        // Faz a requisição para a API
        $response = file_get_contents($url);

        // Verifica se a resposta é válida
        if ($response === false) {
            return response()->json(['error' => 'Erro ao conectar à API de geocodificação'], 500);
        }

        $cidades = json_decode($response, true);

        // Verifica se há resultados
        if (!isset($cidades['results']) || empty($cidades['results'])) {
            return response()->json([]); // Retorna vazio se não houver resultados
        }

        // Extrai informações relevantes das cidades
        $sugestoes = array_map(function ($cidade) {
            return [
                'name' => $cidade['name'],
                'state' => $cidade['city'] ?? null, // Estado (se disponível)
                'country' => $cidade['country'],
                'lat' => $cidade['latitude'],
                'lon' => $cidade['longitude']
            ];
        }, $cidades['results']);

        return response()->json($sugestoes);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar sugestões de cidades', 'details' => $e->getMessage()], 500);
    }
});

// Rota para buscar o clima atual
$router->get('/api/clima-atual', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat');
    $lon = $request->get('lon');

    // Validação das coordenadas
    if (!$lat || !$lon || !is_numeric($lat) || !is_numeric($lon)) {
        return response()->json(['error' => 'Coordenadas inválidas ou ausentes'], 400);
    }

    // Corrigir possíveis problemas na URL
    $urlClima = "https://api.open-meteo.com/v1/forecast?latitude=" . urlencode($lat) . "&longitude=" . urlencode($lon) . "&current_weather=true&timezone=auto";
    $urlGeocoding = "https://geocoding-api.open-meteo.com/v1/reverse?latitude=" . urlencode($lat) . "&longitude=" . urlencode($lon) . "&language=pt&format=json";

    try {
        // Faz a requisição para a API de clima atual
        $responseClima = file_get_contents($urlClima);
        if ($responseClima === false) {
            return response()->json(['error' => 'Erro ao conectar à API Open-Meteo'], 500);
        }
        $dadosClima = json_decode($responseClima, true);

        // Verifica se os dados do clima atual estão disponíveis
        if (!isset($dadosClima['current_weather'])) {
            return response()->json(['error' => 'Dados do clima atual não disponíveis'], 500);
        }

        // Faz a requisição para a API de geocodificação reversa
        $responseGeocoding = @file_get_contents($urlGeocoding);

        if ($responseGeocoding === false || empty($responseGeocoding)) {
            error_log("Erro: API de geocodificação retornou erro 404 ou outra falha.");
            $cidade = 'Local desconhecido';
            $estado = null;
        } else {
            $dadosGeocoding = json_decode($responseGeocoding, true);

            if (isset($dadosGeocoding['results']) && count($dadosGeocoding['results']) > 0) {
                $cidade = $dadosGeocoding['results'][0]['name'] ?? 'Local desconhecido';
                $estado = $dadosGeocoding['results'][0]['admin1'] ?? null;
            } else {
                $cidade = 'Local desconhecido';
                $estado = null;
            }
        }

        // Adiciona o nome da cidade, estado e umidade aos dados do clima
        $dadosClima['current_weather']['city'] = $cidade;
        $dadosClima['current_weather']['state'] = $estado;
        $dadosClima['current_weather']['humidity'] = $dadosClima['current_weather']['relative_humidity'] ?? 'N/A';

        return response()->json($dadosClima['current_weather']);
    } catch (\Exception $e) {
        error_log("Erro: " . $e->getMessage());
        return response()->json(['error' => 'Erro ao buscar o clima atual', 'details' => $e->getMessage()], 500);
    }
});

// Rota para buscar a previsão de 7 dias
$router->get('/api/previsao', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat');
    $lon = $request->get('lon');

    if (!$lat || !$lon) {
        return response()->json(['error' => 'Coordenadas não informadas'], 400);
    }

    // Calcula as datas de início e fim
    $hoje = date('Y-m-d');
    $seteDiasDepois = date('Y-m-d', strtotime('+8 days'));

    // URL da API Open-Meteo para buscar a previsão de 7 dias
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&start_date={$hoje}&end_date={$seteDiasDepois}&daily=temperature_2m_max,temperature_2m_min,weathercode,precipitation_sum&timezone=auto";

    try {
        // Faz a requisição para a API
        $response = file_get_contents($url);
        $dadosPrevisao = json_decode($response, true);

        // Verifica se os dados da previsão estão disponíveis
        if (!isset($dadosPrevisao['daily'])) {
            return response()->json(['error' => 'Dados da previsão não disponíveis'], 500);
        }

        // Retorna os dados da previsão diária
        return response()->json($dadosPrevisao['daily']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar a previsão de 7 dias', 'details' => $e->getMessage()], 500);
    }
});

// Rota para buscar os horários de nascer e pôr do sol
$router->get('/api/nascer-por-sol', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat');
    $lon = $request->get('lon');

    // Validação das coordenadas
    if (!$lat || !$lon || !is_numeric($lat) || !is_numeric($lon)) {
        return response()->json(['error' => 'Coordenadas inválidas ou ausentes'], 400);
    }

    // URL da API para buscar os horários de nascer e pôr do sol
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&daily=sunrise,sunset&timezone=auto";

    try {
        // Faz a requisição para a API
        $response = file_get_contents($url);

        if ($response === false) {
            return response()->json(['error' => 'Erro ao conectar à API Open-Meteo'], 500);
        }

        $dados = json_decode($response, true);

        // Verifica se os dados de nascer e pôr do sol estão disponíveis
        if (!isset($dados['daily']['sunrise']) || !isset($dados['daily']['sunset'])) {
            return response()->json(['error' => 'Dados de nascer e pôr do sol não disponíveis'], 500);
        }

        return response()->json([
            'nascer_do_sol' => $dados['daily']['sunrise'][0],
            'por_do_sol' => $dados['daily']['sunset'][0],
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar os horários de nascer e pôr do sol', 'details' => $e->getMessage()], 500);
    }
});

// Rota para buscar o nome da cidade
$router->get('/api/nome-cidade', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat');
    $lon = $request->get('lon');

    // Validação das coordenadas
    if (!$lat || !$lon || !is_numeric($lat) || !is_numeric($lon)) {
        return response()->json(['error' => 'Coordenadas inválidas ou ausentes'], 400);
    }

    // URL da API de geocodificação reversa
    $urlGeocoding = "https://geocoding-api.open-meteo.com/v1/reverse?latitude=" . urlencode($lat) . "&longitude=" . urlencode($lon) . "&language=pt&format=json";

    try {
        // Faz a requisição para a API de geocodificação reversa
        $responseGeocoding = @file_get_contents($urlGeocoding);

        // Log da URL e resposta
        error_log("URL Geocoding: $urlGeocoding");
        error_log("Resposta Geocoding: $responseGeocoding");

        if ($responseGeocoding === false || empty($responseGeocoding)) {
            return response()->json(['error' => 'Erro ao conectar à API de geocodificação ou dados não encontrados'], 404);
        }

        $dadosGeocoding = json_decode($responseGeocoding, true);

        // Verifica se a API de geocodificação retornou resultados
        if (!isset($dadosGeocoding['results']) || empty($dadosGeocoding['results'])) {
            return response()->json(['error' => 'Nenhuma cidade encontrada para as coordenadas fornecidas'], 404);
        }

        // Obtém o nome da cidade e do estado
        $cidade = $dadosGeocoding['results'][0]['name'] ?? 'Local desconhecido';
        $estado = $dadosGeocoding['results'][0]['admin1'] ?? null;

        return response()->json([
            'cidade' => $cidade,
            'estado' => $estado,
        ]);
    } catch (\Exception $e) {
        error_log("Erro: " . $e->getMessage());
        return response()->json(['error' => 'Erro ao buscar o nome da cidade', 'details' => $e->getMessage()], 500);
    }
});
