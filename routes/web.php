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
    $apiKey = env('API_KEY');

    if (!$query || strlen($query) < 2) {
        return response()->json([]); // Retorna vazio se a consulta for muito curta
    }

    // URL da API OpenWeatherMap para buscar cidades
    $url = "http://api.openweathermap.org/geo/1.0/direct?q={$query}&limit=5&appid={$apiKey}";

    // Faz a requisição para a API
    $response = file_get_contents($url);
    $cidades = json_decode($response, true);

    // Extrai apenas os nomes das cidades
    $sugestoes = array_map(function ($cidade) {
        return $cidade['name'];
    }, $cidades);

    return response()->json($sugestoes);
});

$router->get('/api/sugestoes', function (\Illuminate\Http\Request $request) {
    $query = $request->get('query');
    $apiKey = env('API_KEY');

    if (!$query || strlen($query) < 2) {
        return response()->json([]); // Retorna vazio se a consulta for muito curta
    }

    // URL da API OpenWeatherMap para buscar cidades
    $url = "http://api.openweathermap.org/geo/1.0/direct?q={$query}&limit=5&appid={$apiKey}";

    // Faz a requisição para a API
    $response = file_get_contents($url);
    $cidades = json_decode($response, true);

    // Extrai informações relevantes das cidades
    $sugestoes = array_map(function ($cidade) {
        return [
            'name' => $cidade['name'],
            'country' => $cidade['country'],
            'lat' => $cidade['lat'],
            'lon' => $cidade['lon']
        ];
    }, $cidades);

    return response()->json($sugestoes);
});

$router->get('/api/clima-atual', function (\Illuminate\Http\Request $request) {
    $lat = $request->get('lat');
    $lon = $request->get('lon');
    $apiKey = env('API_KEY');

    if (!$lat || !$lon) {
        return response()->json(['error' => 'Coordenadas não informadas'], 400);
    }

    // URL da API OpenWeatherMap para buscar o clima
    $url = "http://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}";

    // Faz a requisição para a API
    $response = file_get_contents($url);
    $dadosClima = json_decode($response, true);

    // Retorna os dados do clima
    return response()->json($dadosClima);
});

$router->get('/test-api-key', function () {
    $apiKey = env('API_KEY');
    return response()->json(['api_key' => $apiKey]);
});
