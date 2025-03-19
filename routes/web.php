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

// $router->get('/api/clima', 'Controller@getCurrentWeather');
// $router->get('/api/previsao', 'Controller@getForecast');
// $router->get('/api/temperatura-ontem', 'Controller@getYesterdayTemp');
// $router->get('/api/converter-temperatura', 'Controller@convertTemperature');
// $router->get('/api/sol', 'Controller@getSunTimes');
// $router->get('/api/previsao-chuva', 'Controller@getRainForecast');
// $router->get('/api/comparar-temperatura', 'Controller@compareTemperature');
$router->get('/api/clima-atual', 'Controller@climaAtual');


$router->get('/api/sugestoes', function (\Illuminate\Http\Request $request) {
    $query = $request->get('query');

    if (!$query || strlen($query) < 2) {
        return response()->json([]); // Retorna vazio se a consulta for muito curta
    }

    // URL da API Open-Meteo para buscar cidades
    $url = "https://geocoding-api.open-meteo.com/v1/search?name={$query}&count=5&language=pt&format=json";

    // Faz a requisição para a API
    $response = file_get_contents($url);
    $cidades = json_decode($response, true);

    // Verifica se há resultados
    if (!isset($cidades['results']) || empty($cidades['results'])) {
        return response()->json([]); // Retorna vazio se não houver resultados
    }

    // Extrai informações relevantes das cidades
    $sugestoes = array_map(function ($cidade) {
        return [
            'name' => $cidade['name'],
            'state' => $cidade['admin1'] ?? null, // Estado (se disponível)
            'country' => $cidade['country'],
            'lat' => $cidade['latitude'],
            'lon' => $cidade['longitude']
        ];
    }, $cidades['results']);

    return response()->json($sugestoes);
});

// Rota para buscar o clima atual
$router->get('/api/clima-atual', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat');
    $lon = $request->get('lon');

    if (!$lat || !$lon) {
        return response()->json(['error' => 'Coordenadas não informadas'], 400);
    }

    // URL da API Open-Meteo para buscar o clima atual
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true&timezone=auto";

    // Faz a requisição para a API
    $response = file_get_contents($url);
    $dadosClima = json_decode($response, true);

    // Verifica se os dados do clima estão disponíveis
    if (!isset($dadosClima['current_weather'])) {
        return response()->json(['error' => 'Dados do clima não disponíveis'], 500);
    }

    // Retorna os dados do clima atual
    return response()->json($dadosClima['current_weather']);
});

// Rota para buscar a previsão de 7 dias
$router->get('/api/previsao', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat');
    $lon = $request->get('lon');

    if (!$lat || !$lon) {
        return response()->json(['error' => 'Coordenadas não informadas'], 400);
    }

    // URL da API Open-Meteo para buscar a previsão de 7 dias
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&daily=temperature_2m_max,temperature_2m_min,weathercode&timezone=auto";

    // Faz a requisição para a API
    $response = file_get_contents($url);
    $dadosPrevisao = json_decode($response, true);

    // Verifica se os dados da previsão estão disponíveis
    if (!isset($dadosPrevisao['daily'])) {
        return response()->json(['error' => 'Dados da previsão não disponíveis'], 500);
    }

    // Retorna os dados da previsão diária
    return response()->json($dadosPrevisao['daily']);
});

$router->get('/test-api-key', function () {
    $apiKey = env('API_KEY');
    return response()->json(['api_key' => $apiKey]);
});
