<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ClimaController extends BaseController
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        // Inicia o cliente HTTP para fazer as requisições à API OpenWeather
        $this->client = new Client();
        $this->apiKey = env('API_KEY'); // Obtém a chave da API do .env
    }

    public function fraseDiaAtual(Request $request)
    {
        $cidade = $request->get('cidade', 'Brasília');
        $dados = $this->obterClimaAtual($cidade);

        if (!$dados) {
            return response()->json(['erro' => 'Cidade não encontrada'], 404);
        }

        $frase = "Hoje está {$dados['descricao']} em {$cidade}, com {$dados['temperatura']}°C e umidade de {$dados['umidade']}%.";
        return response()->json(['frase' => $frase]);
    }

    public function climaAtual(Request $request)
    {
        try {
            // Obtém os parâmetros de latitude e longitude ou o nome da cidade
            $lat = $request->get('lat');
            $lon = $request->get('lon');
            $cidade = $request->get('cidade', 'Brasília');

            // Verifica se latitude e longitude foram fornecidas
            if ($lat && $lon) {
                // Faz a requisição para a API OpenWeather com latitude e longitude
                $resposta = $this->client->get("https://api.openweathermap.org/data/2.5/weather", [
                    'query' => [
                        'lat' => $lat,
                        'lon' => $lon,
                        'appid' => $this->apiKey,
                        'units' => 'metric',
                        'lang' => 'pt'
                    ]
                ]);
            } else {
                // Faz a requisição para a API OpenWeather com o nome da cidade
                $resposta = $this->client->get("https://api.openweathermap.org/data/2.5/weather", [
                    'query' => [
                        'q' => $cidade,
                        'appid' => $this->apiKey,
                        'units' => 'metric',
                        'lang' => 'pt'
                    ]
                ]);
            }

            // Converte a resposta JSON da API para array PHP
            $dados = json_decode($resposta->getBody(), true);

            // Retorna os dados formatados
            return response()->json([
                'cidade' => $dados['name'],
                'pais' => $dados['sys']['country'],
                'temperatura' => $dados['main']['temp'],
                'umidade' => $dados['main']['humidity'],
                'descricao' => $dados['weather'][0]['description']
            ]);

        } catch (RequestException $e) {
            // Se a API do OpenWeather retornar erro, captura a resposta
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;

            return response()->json(['erro' => 'Erro ao buscar clima. Tente novamente mais tarde.'], $statusCode);
        }
    }

    public function previsaoSeteDias(Request $request)
    {
        $cidade = $request->get('cidade', 'Brasília');
        $res = $this->chamarApi("https://api.openweathermap.org/data/2.5/forecast/daily?q={$cidade}&cnt=7&appid={$this->apiKey}&units=metric");

        if (!$res) {
            return response()->json(['erro' => 'Cidade não encontrada'], 404);
        }

        $previsao = array_map(function ($dia) {
            return [
                'dia' => date('l', $dia['dt']),
                'temperatura' => $dia['temp']['day'] . '°C',
                'descricao' => $dia['weather'][0]['description']
            ];
        }, $res['list']);

        return response()->json(['cidade' => $cidade, 'previsao' => $previsao]);
    }

    public function temperaturaOntem(Request $request)
    {
        $cidade = $request->get('cidade', 'Brasília');
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if (!$lat || !$lon) {
            return response()->json(['erro' => 'Coordenadas necessárias'], 400);
        }

        $ontem = time() - (24 * 60 * 60);
        $res = $this->chamarApi("https://api.open-meteo.com/v1/history?latitude={$lat}&longitude={$lon}&start={$ontem}&end={$ontem}&appid={$this->apiKey}&units=metric");

        if (!$res) {
            return response()->json(['erro' => 'Dados indisponíveis'], 404);
        }

        return response()->json(['cidade' => $cidade, 'temperatura_media' => "{$res['temperature']}°C"]);
    }

    public function converterTemperatura(Request $request)
    {
        $temperatura = $request->get('temperatura');
        $unidade = strtoupper($request->get('unidade', 'C'));

        if (!is_numeric($temperatura)) {
            return response()->json(['erro' => 'Temperatura inválida'], 400);
        }

        $resultado = match ($unidade) {
            'F' => ($temperatura * 9/5) + 32 . '°F',
            'K' => $temperatura + 273.15 . 'K',
            default => $temperatura . '°C'
        };

        return response()->json(['temperatura_original' => "{$temperatura}°C", 'convertida' => $resultado]);
    }

    public function nascerPorSol(Request $request)
    {
        $cidade = $request->get('cidade', 'Brasília');
        $dados = $this->obterClimaAtual($cidade);

        if (!$dados) {
            return response()->json(['erro' => 'Cidade não encontrada'], 404);
        }

        return response()->json([
            'cidade' => $cidade,
            'nascer_do_sol' => date('H:i', $dados['nascer_do_sol']),
            'por_do_sol' => date('H:i', $dados['por_do_sol']),
        ]);
    }

    public function previsaoChuva(Request $request)
    {
        $cidade = $request->get('cidade', 'Brasília');
        $dados = $this->previsaoSeteDias($request);

        foreach ($dados['previsao'] as $dia) {
            if (str_contains(strtolower($dia['descricao']), 'chuva')) {
                return response()->json(['cidade' => $cidade, 'previsao' => "Chuva prevista nos próximos dias"]);
            }
        }

        return response()->json(['cidade' => $cidade, 'previsao' => "Sem previsão de chuva"]);
    }

    public function compararTemperatura(Request $request)
    {
        $cidade = $request->get('cidade', 'Brasília');
        $dadosHoje = $this->obterClimaAtual($cidade);
        $dadosOntem = $this->temperaturaOntem($request)->original;

        if (!$dadosHoje || !$dadosOntem) {
            return response()->json(['erro' => 'Dados indisponíveis'], 404);
        }

        $comparacao = $dadosHoje['temperatura'] > $dadosOntem['temperatura_media'] ? "mais quente" : "mais frio";
        return response()->json(['cidade' => $cidade, 'ontem' => $dadosOntem['temperatura_media'], 'hoje' => "{$dadosHoje['temperatura']}°C", 'comparacao' => "Hoje está $comparacao que ontem."]);
    }

    private function obterClimaAtual($cidade)
    {
        $res = $this->chamarApi("https://api.openweathermap.org/data/2.5/weather?q={$cidade}&appid={$this->apiKey}&units=metric");

        return $res ? [
            'cidade' => $cidade,
            'pais' => $res['sys']['country'],
            'temperatura' => $res['main']['temp'],
            'umidade' => $res['main']['humidity'],
            'descricao' => $res['weather'][0]['description'],
            'nascer_do_sol' => $res['sys']['sunrise'],
            'por_do_sol' => $res['sys']['sunset'],
        ] : null;
    }

    private function chamarApi($url)
    {
        try {
            $res = $this->client->get($url);
            return json_decode($res->getBody(), true);
        } catch (RequestException $e) {
            return null;
        }
    }

}
