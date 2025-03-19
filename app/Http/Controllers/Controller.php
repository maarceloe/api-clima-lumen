<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ClimaController extends BaseController
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    // Sugestões de cidades
    public function sugestoes(Request $request)
    {
        $query = $request->get('query');

        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $url = "https://geocoding-api.open-meteo.com/v1/search?name={$query}&count=5&language=pt&format=json";

        try {
            // Faz a requisição para a API usando Guzzle
            $response = $this->client->get($url);
            $dados = json_decode($response->getBody(), true);

            // Verifica se há resultados
            if (!isset($dados['results']) || empty($dados['results'])) {
                return response()->json([]);
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
            }, $dados['results']);

            return response()->json($sugestoes);
        } catch (RequestException $e) {
            // Captura erros de requisição e retorna uma mensagem detalhada
            return response()->json(['error' => 'Erro ao buscar sugestões de cidades', 'details' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // Captura outros erros e retorna uma mensagem genérica
            return response()->json(['error' => 'Erro ao buscar sugestões de cidades', 'details' => $e->getMessage()], 500);
        }
    }

    // Clima atual
    public function climaAtual(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        // Validação das coordenadas
        if (!$lat || !$lon || !is_numeric($lat) || !is_numeric($lon)) {
            return response()->json(['error' => 'Coordenadas inválidas ou ausentes'], 400);
        }

        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true&timezone=auto";

        try {
            // Faz a requisição para a API Open-Meteo
            $response = $this->client->get($url);
            $dados = json_decode($response->getBody(), true);

            // Verifica se os dados do clima atual estão disponíveis
            if (!isset($dados['current_weather'])) {
                return response()->json(['error' => 'Dados do clima atual não disponíveis'], 500);
            }

            return response()->json($dados['current_weather']);
        } catch (RequestException $e) {
            // Captura erros de requisição e retorna detalhes
            return response()->json(['error' => 'Erro ao buscar o clima atual', 'details' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // Captura outros erros e retorna detalhes
            return response()->json(['error' => 'Erro ao buscar o clima atual', 'details' => $e->getMessage()], 500);
        }
    }

    // Previsão de 7 dias
    public function previsaoSeteDias(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if (!$lat || !$lon) {
            return response()->json(['error' => 'Coordenadas não informadas'], 400);
        }

        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&daily=temperature_2m_max,temperature_2m_min,weathercode,precipitation_sum&timezone=auto";

        try {
            $response = $this->client->get($url);
            $dados = json_decode($response->getBody(), true);

            if (!isset($dados['daily'])) {
                return response()->json(['error' => 'Dados da previsão não disponíveis'], 500);
            }

            return response()->json($dados['daily']);
        } catch (RequestException $e) {
            return response()->json(['error' => 'Erro ao buscar a previsão de 7 dias'], 500);
        }
    }

    // Comparar temperaturas
    public function compararTemperatura(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if (!$lat || !$lon) {
            return response()->json(['error' => 'Coordenadas não informadas'], 400);
        }

        $ontem = date('Y-m-d', strtotime('-1 day'));
        $hoje = date('Y-m-d');

        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&start_date={$ontem}&end_date={$hoje}&daily=temperature_2m_max,temperature_2m_min&timezone=auto";

        try {
            $response = $this->client->get($url);
            $dados = json_decode($response->getBody(), true);

            if (!isset($dados['daily'])) {
                return response()->json(['error' => 'Dados de temperatura não disponíveis'], 500);
            }

            $ontemTemp = $dados['daily']['temperature_2m_max'][0];
            $hojeTemp = $dados['daily']['temperature_2m_max'][1];

            return response()->json([
                'ontem' => $ontemTemp,
                'hoje' => $hojeTemp,
                'diferenca' => $hojeTemp - $ontemTemp
            ]);
        } catch (RequestException $e) {
            return response()->json(['error' => 'Erro ao comparar temperaturas'], 500);
        }
    }
}
