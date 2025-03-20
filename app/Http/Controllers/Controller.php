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

        $urlClima = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true&timezone=auto";
        $urlGeocoding = "https://geocoding-api.open-meteo.com/v1/reverse?latitude={$lat}&longitude={$lon}&language=pt&format=json";

        try {
            // Faz a requisição para a API Open-Meteo (Clima Atual)
            $responseClima = $this->client->get($urlClima);
            $dadosClima = json_decode($responseClima->getBody(), true);

            // Verifica se os dados do clima atual estão disponíveis
            if (!isset($dadosClima['current_weather'])) {
                return response()->json(['error' => 'Dados do clima atual não disponíveis'], 500);
            }

            // Faz a requisição para a API de Geocodificação Reversa
            $responseGeocoding = $this->client->get($urlGeocoding);
            $dadosGeocoding = json_decode($responseGeocoding->getBody(), true);

            // Verifica se a API de geocodificação retornou resultados
            if (!isset($dadosGeocoding['results']) || empty($dadosGeocoding['results'])) {
                $cidade = 'Local desconhecido';
                $estado = null;
            } else {
                // Obtém o nome da cidade e do estado
                $cidade = $dadosGeocoding['results'][0]['name'] ?? 'Local desconhecido';
                $estado = $dadosGeocoding['results'][0]['admin1'] ?? null;
            }

            // Adiciona o nome da cidade, estado e umidade aos dados do clima
            $dadosClima['current_weather']['city'] = $cidade;
            $dadosClima['current_weather']['state'] = $estado;

            // Verifica se a umidade está disponível
            $dadosClima['current_weather']['humidity'] = $dadosClima['current_weather']['relative_humidity'] ?? 'N/A';

            return response()->json($dadosClima['current_weather']);
        } catch (RequestException $e) {
            return response()->json(['error' => 'Erro ao buscar o clima atual', 'details' => $e->getMessage()], 500);
        } catch (\Exception $e) {
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

        // Calcula as datas de início e fim
        $hoje = date('Y-m-d');
        $seteDiasDepois = date('Y-m-d', strtotime('+8 days'));

        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&start_date={$hoje}&end_date={$seteDiasDepois}&daily=temperature_2m_max,temperature_2m_min,weathercode,precipitation_sum&timezone=auto";

        try {
            $response = $this->client->get($url);
            $dados = json_decode($response->getBody(), true);

            if (!isset($dados['daily'])) {
                return response()->json(['error' => 'Dados da previsão não disponíveis'], 500);
            }

            return response()->json($dados['daily']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao buscar a previsão de 7 dias', 'details' => $e->getMessage()], 500);
        }
    }

    // Comparar temperaturas
    public function compararTemperatura(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if (!$lat || !$lon || !is_numeric($lat) || !is_numeric($lon)) {
            return response()->json(['error' => 'Coordenadas inválidas ou ausentes'], 400);
        }

        $ontem = date('Y-m-d', strtotime('-1 day'));
        $hoje = date('Y-m-d');

        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&start_date={$ontem}&end_date={$hoje}&daily=temperature_2m_max&timezone=auto";

        try {
            $response = $this->client->get($url);
            $dados = json_decode($response->getBody(), true);

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
            return response()->json(['error' => 'Erro ao buscar os dados de temperatura', 'details' => $e->getMessage()], 500);
        }
    }
}
