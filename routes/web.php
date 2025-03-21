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

// Obter o nome da cidade e estado

// Função para obter o nome da cidade e estado a partir das coordenadas de latitude e longitude
function obterCidadeEstado($lat, $lon) {
    $apiKeyGeocoding = '7efd79e2c4474d3eb95b76fd24d3a15c';
    $urlGeocoding = "https://api.opencagedata.com/geocode/v1/json?q={$lat}+{$lon}&key={$apiKeyGeocoding}&language=pt";

    try {
        // Faz a requisição para a API de geocodificação
        $response = file_get_contents($urlGeocoding);

        // Verifica se a requisição falhou
        if ($response === false) {
            return ['cidade' => 'Local desconhecido', 'estado' => null];
        }

        // Decodifica a resposta em json da api
        $dados = json_decode($response, true);

        // Verifica se os dados retornados são válidos
        if (!isset($dados['results'][0])) {
            return ['cidade' => 'Local desconhecido', 'estado' => null];
        }

        // Obtém o nome da cidade e do estado a partir dos dados retornados
        $cidade = $dados['results'][0]['components']['city'] ?? 'Local desconhecido';
        $estado = $dados['results'][0]['components']['state'] ?? null;

        return ['cidade' => $cidade, 'estado' => $estado];
    } catch (\Exception $e) {
        // Retorna valores padrão em caso de erro
        return ['cidade' => 'Local desconhecido', 'estado' => null];
    }
}


//rotas

// Rota 1 = Dia atual
// Exemplo
// http://localhost:8000/api/dia-atual?lat=-23.5505&lon=-46.6333 = São Paulo
// http://localhost:8000/api/dia-atual?lat=-21.2115&lon=-50.4261 = Araçatuba
$router->get('/api/dia-atual', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat');
    $lon = $request->get('lon');

    if (!$lat || !$lon) {
        return response()->json(['error' => 'Coordenadas não fornecidas. Envie os parâmetros lat e lon.'], 400);
    }

    $localizacao = obterCidadeEstado($lat, $lon);

    $apiKeyWeather = 'fee7d0e3201887f2481f52f9257942db';
    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&lang=pt&appid={$apiKeyWeather}";

    try {
        $response = file_get_contents($url);

        if ($response === false) {
            return response()->json(['error' => 'Erro ao conectar à API OpenWeatherMap'], 500);
        }

        $dados = json_decode($response, true);

        if (!isset($dados['main'])) {
            return response()->json(['error' => 'Dados do clima atual não disponíveis'], 500);
        }

        $temperatura = $dados['main']['temp'];
        $umidade = $dados['main']['humidity'];
        $descricao = $dados['weather'][0]['description'] ?? 'Condição desconhecida';

        $frase = "Hoje está $descricao em {$localizacao['cidade']}, com {$temperatura}°C e umidade de {$umidade}%. Aproveite o dia!";

        return response()->json(['frase' => $frase]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar a frase do dia', 'details' => $e->getMessage()], 500);
    }
});

// Rota 2 = Clima Atual (nome na url)
// Exemplo
// http://localhost:8000/api/clima-atual?city=São Paulo
$router->get('/api/clima-atual', function (\Illuminate\Http\Request $request) {
    // Obtém o nome da cidade a partir do parâmetro "city" na URL, com valor padrão "São Paulo"
    $cidade = $request->get('city', 'São Paulo');

    $apiKeyGeocoding = '7efd79e2c4474d3eb95b76fd24d3a15c';
    $urlGeocoding = "https://api.opencagedata.com/geocode/v1/json?q=" . urlencode($cidade) . "&key={$apiKeyGeocoding}&language=pt";

    try {
        // Faz a requisição para o opencage para obter as coordenadas da cidade
        $responseGeocoding = file_get_contents($urlGeocoding);

        // Verifica se a requisição falhou
        if ($responseGeocoding === false) {
            return response()->json(['error' => 'Erro ao conectar à API de geocodificação'], 500);
        }

        // Decodifica a resposta json da API
        $dadosGeocoding = json_decode($responseGeocoding, true);

        // Verifica se a api retornou resultados válidos
        if (!isset($dadosGeocoding['results']) || empty($dadosGeocoding['results'])) {
            return response()->json(['error' => 'Nenhuma cidade encontrada para o nome fornecido'], 404);
        }

        // Obtém as coordenadas (latitude e longitude) da cidade
        $lat = $dadosGeocoding['results'][0]['geometry']['lat'];
        $lon = $dadosGeocoding['results'][0]['geometry']['lng'];

        $apiKeyWeather = 'fee7d0e3201887f2481f52f9257942db';
        $urlClima = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&lang=pt&appid={$apiKeyWeather}";

        // Faz a requisição para a openweather para obter os dados do clima
        $responseClima = file_get_contents($urlClima);

        if ($responseClima === false) {
            return response()->json(['error' => 'Erro ao conectar à API OpenWeatherMap'], 500);
        }

        $dadosClima = json_decode($responseClima, true);

        if (!isset($dadosClima['main'])) {
            return response()->json(['error' => 'Dados do clima atual não disponíveis'], 500);
        }

        // Extrai informações do clima: temperatura, umidade e descrição
        $temperatura = $dadosClima['main']['temp'];
        $umidade = $dadosClima['main']['humidity'];
        $descricao = $dadosClima['weather'][0]['description'] ?? 'Condição desconhecida';

        // Mapeia descrições do clima para ícones correspondentes
        $weatherCodeMap = [
            'céu limpo' => '☀️',
            'algumas nuvens' => '⛅',
            'nublado' => '☁️',
            'nuvens dispersas' => '☁️',
            'nuvens quebradas' => '☁️',
            'chuva leve' => '🌧️',
            'chuva moderada' => '🌦️',
            'chuva forte' => '🌧️',
            'trovoada' => '⛈️',
            'neve' => '❄️',
            'névoa' => '🌫️',
            'chuvisco' => '🌦️',
            'tempestade' => '⛈️'
        ];

        // Obtém o ícone correspondente à descrição do clima
        $icone = $weatherCodeMap[strtolower($descricao)] ?? '❓';

        // Retorna os dados do clima atual como resposta json
        return response()->json([
            'cidade' => $cidade,
            'temperatura' => "{$temperatura}°C",
            'umidade' => "{$umidade}%",
            'descricao' => ucfirst($descricao),
            'icone' => $icone
        ]);
    } catch (\Exception $e) {
        // Captura erros e retorna uma mensagem de erro detalhada
        return response()->json(['error' => 'Erro ao buscar o clima atual', 'details' => $e->getMessage()], 500);
    }
});

// Rota 3 = Previsão para os próximos 7 dias
// Exemplo
// http://localhost:8000/api/previsao-7-dias?lat=-23.5505&lon=-46.6333 = São Paulo
// http://localhost:8000/api/previsao-7-dias?lat=-21.2115&lon=-50.4261 = Araçatuba
$router->get('/api/previsao-7-dias', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat');
    $lon = $request->get('lon');

    if (!$lat || !$lon) {
        return response()->json(['error' => 'Coordenadas não fornecidas. Envie os parâmetros lat e lon.'], 400);
    }

    $localizacao = obterCidadeEstado($lat, $lon);

    $hoje = date('Y-m-d');
    $seteDiasDepois = date('Y-m-d', strtotime('+7 days'));

    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&start_date={$hoje}&end_date={$seteDiasDepois}&daily=temperature_2m_max,temperature_2m_min,weathercode&timezone=auto";

    try {
        $response = file_get_contents($url);

        if ($response === false) {
            return response()->json(['error' => 'Erro ao conectar à API Open-Meteo'], 500);
        }

        $dados = json_decode($response, true);

        if (!isset($dados['daily'])) {
            return response()->json(['error' => 'Dados da previsão não disponíveis'], 500);
        }

        $weatherCodeMap = [
            0 => 'Céu limpo',
            1 => 'Parcialmente nublado',
            2 => 'Nublado',
            3 => 'Chuva leve',
            4 => 'Chuva forte',
        ];

        $previsao = [];
        foreach ($dados['daily']['time'] as $index => $dia) {
            $descricao = $weatherCodeMap[$dados['daily']['weathercode'][$index]] ?? 'Condição desconhecida';
            $previsao[] = [
                'dia' => date('l', strtotime($dia)),
                'temperatura_maxima' => "{$dados['daily']['temperature_2m_max'][$index]}°C",
                'temperatura_minima' => "{$dados['daily']['temperature_2m_min'][$index]}°C",
                'descricao' => $descricao
            ];
        }

        $frase = "Hoje está {$previsao[0]['descricao']} em {$localizacao['cidade']}, com temperatura máxima de {$previsao[0]['temperatura_maxima']} e mínima de {$previsao[0]['temperatura_minima']}. Aproveite o dia!";

        return response()->json([
            'frase' => $frase,
            'cidade' => $localizacao['cidade'],
            'estado' => $localizacao['estado'],
            'previsao' => $previsao
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar a previsão de 7 dias', 'details' => $e->getMessage()], 500);
    }
});

// Rota 4 = Tempertura média do dia anterior
// Exemplo
// http://localhost:8000/api/temperatura-ontem?lat=-23.5505&lon=-46.6333 = São Paulo
// http://localhost:8000/api/temperatura-ontem?lat=-21.2115&lon=-50.4261 = Araçatuba
$router->get('/api/temperatura-ontem', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat', -23.5505); // Latitude padrão: São Paulo
    $lon = $request->get('lon', 46.6333); // Longitude padrão: São Paulo

    // Obtém o nome da cidade e estado
    $localizacao = obterCidadeEstado($lat, $lon);

    // Calcula a data de ontem
    $ontem = date('Y-m-d', strtotime('-1 day'));

    // URL da api open meteo para buscar a temperatura de ontem
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&start_date={$ontem}&end_date={$ontem}&daily=temperature_2m_max,temperature_2m_min&timezone=auto";

    try {
        // Faz a requisição para a api
        $response = file_get_contents($url);

        if ($response === false) {
            return response()->json(['error' => 'Erro ao conectar à API Open-Meteo'], 500);
        }

        $dados = json_decode($response, true);

        // Verifica se os dados da temperatura estão disponíveis
        if (!isset($dados['daily']['temperature_2m_max'][0]) || !isset($dados['daily']['temperature_2m_min'][0])) {
            return response()->json(['error' => 'Dados de temperatura não disponíveis'], 500);
        }

        // Calcula a temperatura média
        $temperaturaMaxima = $dados['daily']['temperature_2m_max'][0];
        $temperaturaMinima = $dados['daily']['temperature_2m_min'][0];
        $temperaturaMedia = ($temperaturaMaxima + $temperaturaMinima) / 2;

        // Retorna a temperatura média
        return response()->json([
            'cidade' => $localizacao['cidade'],
            'estado' => $localizacao['estado'],
            'temperatura_media_de_ontem' => round($temperaturaMedia, 1) . "°C"
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar a temperatura média de ontem', 'details' => $e->getMessage()], 500);
    }
});

// Rota 5 = Converter temperatura
// Exemplo
// http://localhost:8000/api/converter-temperatura?temperatura=28 (mudar temperatura na url)
$router->get('/api/converter-temperatura', function (\Illuminate\Http\Request $request) {
    $temperatura = $request->get('temperatura');

    if (is_null($temperatura) || !is_numeric($temperatura)) {
        return response()->json(['error' => 'Temperatura inválida. Envie um valor numérico para o parâmetro "temperatura".'], 400);
    }

    // Conversão de temperatura
    $celsius = round($temperatura, 2) . "°C";
    $fahrenheit = round(($temperatura * 9 / 5) + 32, 2) . "°F";
    $kelvin = round($temperatura + 273.15, 2) . "K";

    // Retorna todas as conversões
    return response()->json([
        'temperatura_original' => $celsius,
        'conversoes' => [
            'Celsius' => $celsius,
            'Fahrenheit' => $fahrenheit,
            'Kelvin' => $kelvin
        ]
    ]);
});



// Rota 6 = Nascer e por do sol
// Exemplo
// http://localhost:8000/api/nascer-por-sol?lat=-23.5505&lon=-46.6333 = São Paulo
// http://localhost:8000/api/nascer-por-sol?lat=-21.2115&lon=-50.4261 = Araçatuba
$router->get('/api/nascer-por-sol', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat', -23.5505); // Latitude padrão: São Paulo
    $lon = $request->get('lon', -46.6333); // Longitude padrão: São Paulo

    // Obtém o nome da cidade e estado
    $localizacao = obterCidadeEstado($lat, $lon);

    // URL da api open meteo para buscar os horários de nascer e pôr do sol
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&daily=sunrise,sunset&timezone=auto";

    try {
        // Faz a requisição para a api
        $response = file_get_contents($url);

        if ($response === false) {
            return response()->json(['error' => 'Erro ao conectar à API Open-Meteo'], 500);
        }

        $dados = json_decode($response, true);

        if (!isset($dados['daily']['sunrise'][0]) || !isset($dados['daily']['sunset'][0])) {
            return response()->json(['error' => 'Dados de nascer e pôr do sol não disponíveis'], 500);
        }

        // Formata os horários de nascer e pôr do sol
        $nascerDoSol = date('h:i A', strtotime($dados['daily']['sunrise'][0]));
        $porDoSol = date('h:i A', strtotime($dados['daily']['sunset'][0]));

        // Retorna os dados
        return response()->json([
            'cidade' => $localizacao['cidade'],
            'estado' => $localizacao['estado'],
            'nascer_do_sol' => $nascerDoSol,
            'por_do_sol' => $porDoSol
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar os horários de nascer e pôr do sol', 'details' => $e->getMessage()], 500);
    }
});

// Rota 7 = Previsão de chuva
// Exemplo
// http://localhost:8000/api/previsao-chuva?lat=-23.5505&lon=-46.6333 = São Paulo
// http://localhost:8000/api/previsao-chuva?lat=-21.2115&lon=-50.4261 = Araçatuba
$router->get('/api/previsao-chuva', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat', -23.5505); // Latitude padrão: São Paulo
    $lon = $request->get('lon', -46.6333); // Longitude padrão: São Paulo

    $localizacao = obterCidadeEstado($lat, $lon);

    // URL da api para buscar a previsão de chuva
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&daily=precipitation_sum&timezone=auto";

    try {
        // Faz a requisição pra api
        $response = file_get_contents($url);

        if ($response === false) {
            return response()->json(['error' => 'Erro ao conectar à API Open-Meteo'], 500);
        }

        $dados = json_decode($response, true);

        if (!isset($dados['daily']['precipitation_sum'])) {
            return response()->json(['error' => 'Dados de precipitação não disponíveis'], 500);
        }

        // Verifica se há previsão de chuva nos próximos dias
        $chuvaPrevista = array_filter($dados['daily']['precipitation_sum'], fn($chuva) => $chuva > 0);

        $previsao = count($chuvaPrevista) > 0
            ? "Chuva prevista para os próximos " . count($chuvaPrevista) . " dias."
            : "Sem previsão de chuva nos próximos dias.";

        // Retorna a previsão de chuva
        return response()->json([
            'cidade' => $localizacao['cidade'],
            'estado' => $localizacao['estado'],
            'previsao' => $previsao
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar a previsão de chuva', 'details' => $e->getMessage()], 500);
    }
});

// Rota 8 = Comparar temperatura do dia anterior com a do dia atual
// Exemplo
// http://localhost:8000/api/comparar-temperatura?lat=-23.5505&lon=-46.6333 = São Paulo
// http://localhost:8000/api/comparar-temperatura?lat=-21.2115&lon=-50.4261 = Araçatuba
$router->get('/api/comparar-temperatura', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat', -23.5505); // Latitude padrão: São Paulo
    $lon = $request->get('lon', -46.6333); // Longitude padrão: São Paulo

    $localizacao = obterCidadeEstado($lat, $lon);

    // Calcula as datas de ontem e hoje
    $ontem = date('Y-m-d', strtotime('-1 day'));
    $hoje = date('Y-m-d');

    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&start_date={$ontem}&end_date={$hoje}&daily=temperature_2m_max&timezone=auto";

    try {
        // Faz a requisição para a api
        $response = file_get_contents($url);

        if ($response === false) {
            return response()->json(['error' => 'Erro ao conectar à API Open-Meteo'], 500);
        }

        $dados = json_decode($response, true);

        if (!isset($dados['daily']['temperature_2m_max']) || count($dados['daily']['temperature_2m_max']) < 2) {
            return response()->json(['error' => 'Dados de temperatura não disponíveis ou incompletos'], 500);
        }

        $ontemTemp = $dados['daily']['temperature_2m_max'][0];
        $hojeTemp = $dados['daily']['temperature_2m_max'][1];

        // Compara as temperaturas
        $comparacao = $hojeTemp > $ontemTemp
            ? "Hoje está mais quente que ontem."
            : ($hojeTemp < $ontemTemp
                ? "Hoje está mais frio que ontem."
                : "Hoje está com a mesma temperatura de ontem.");

        // Retorna os dados
        return response()->json([
            'cidade' => $localizacao['cidade'],
            'estado' => $localizacao['estado'],
            'ontem' => "{$ontemTemp}°C",
            'hoje' => "{$hojeTemp}°C",
            'comparacao' => $comparacao
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar os dados de temperatura', 'details' => $e->getMessage()], 500);
    }
});
