<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ClimaController extends BaseController
{
    protected $client;

    // Construtor para inicializar o cliente HTTP
    public function __construct()
    {
        $this->client = new Client();
    }

    // Obtém o clima atual com base na latitude e longitude fornecidas
    public function climaAtual(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        // Verifica se as coordenadas foram fornecidas
        if (!$lat || !$lon) {
            return response()->json(['error' => 'Coordenadas inválidas ou ausentes'], 400);
        }

        $urlClima = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true&timezone=auto";

        try {
            // Faz a requisição para a api e decodifica a resposta
            $response = $this->client->get($urlClima);
            $dadosClima = json_decode($response->getBody(), true);

            if (!isset($dadosClima['current_weather'])) {
                return response()->json(['error' => 'Dados do clima atual não disponíveis'], 500);
            }

            return response()->json($dadosClima['current_weather']);
        } catch (\Exception $e) {
            // Captura erros e retorna uma mensagem detalhada
            return response()->json(['error' => 'Erro ao buscar o clima atual', 'details' => $e->getMessage()], 500);
        }
    }

    // Obtém a previsão do tempo para os próximos 7 dias
    public function previsaoSeteDias(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if (!$lat || !$lon) {
            return response()->json(['error' => 'Coordenadas não informadas'], 400);
        }

        // Calcula as datas de início (hoje) e fim (7 dias depois)
        $hoje = date('Y-m-d');
        $seteDiasDepois = date('Y-m-d', strtotime('+7 days'));

        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&start_date={$hoje}&end_date={$seteDiasDepois}&daily=temperature_2m_max,temperature_2m_min,weathercode&timezone=auto";

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

    // Compara as temperaturas máximas de ontem e hoje
    public function compararTemperatura(Request $request)
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');

        if (!$lat || !$lon) {
            return response()->json(['error' => 'Coordenadas inválidas ou ausentes'], 400);
        }

        // Calcula as datas de ontem e hoje
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
