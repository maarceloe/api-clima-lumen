<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ClimaController extends BaseController
{
    protected $client;

    public function __construct()
    {
        // Inicia o cliente HTTP para fazer as requisições à API OpenWeather
        $this->client = new Client();
    }

    public function climaAtual(Request $request)
    {
        try {
            $lat = $request->get('lat');
            $lon = $request->get('lon');

            if (!$lat || !$lon) {
                return response()->json(['error' => 'Coordenadas não informadas'], 400);
            }

            // URL da API Open-Meteo para buscar o clima atual
            $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true&timezone=auto";

            // Faz a requisição para a API
            $response = $this->client->get($url);
            $dados = json_decode($response->getBody(), true);

            if (!isset($dados['current_weather'])) {
                return response()->json(['error' => 'Dados do clima não disponíveis'], 500);
            }

            // Retorna os dados do clima atual
            return response()->json([
                'temperatura' => $dados['current_weather']['temperature'],
                'velocidade_vento' => $dados['current_weather']['windspeed'],
                'direcao_vento' => $dados['current_weather']['winddirection'],
                'codigo_clima' => $dados['current_weather']['weathercode']
            ]);
        } catch (RequestException $e) {
            return response()->json(['error' => 'Erro ao buscar o clima atual'], 500);
        }
    }

    // Rota para buscar a previsão de 7 dias
    public function previsaoSeteDias(Request $request)
    {
        try {
            $lat = $request->get('lat');
            $lon = $request->get('lon');

            if (!$lat || !$lon) {
                return response()->json(['error' => 'Coordenadas não informadas'], 400);
            }

            // URL da API Open-Meteo para buscar a previsão de 7 dias
            $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&daily=temperature_2m_max,temperature_2m_min,weathercode&timezone=auto";

            // Faz a requisição para a API
            $response = $this->client->get($url);
            $dados = json_decode($response->getBody(), true);

            if (!isset($dados['daily'])) {
                return response()->json(['error' => 'Dados da previsão não disponíveis'], 500);
            }

            // Formata os dados da previsão
            $previsao = [];
            foreach ($dados['daily']['time'] as $index => $dia) {
                $previsao[] = [
                    'data' => $dia,
                    'temperatura_max' => $dados['daily']['temperature_2m_max'][$index],
                    'temperatura_min' => $dados['daily']['temperature_2m_min'][$index],
                    'codigo_clima' => $dados['daily']['weathercode'][$index]
                ];
            }

            return response()->json($previsao);
        } catch (RequestException $e) {
            return response()->json(['error' => 'Erro ao buscar a previsão de 7 dias'], 500);
        }
    }

    // Rota para buscar a temperatura de ontem
    public function temperaturaOntem(Request $request)
    {
        try {
            $lat = $request->get('lat');
            $lon = $request->get('lon');

            if (!$lat || !$lon) {
                return response()->json(['error' => 'Coordenadas não informadas'], 400);
            }

            // Calcula a data de ontem
            $ontem = date('Y-m-d', strtotime('-1 day'));

            // URL da API Open-Meteo para buscar o histórico de temperatura
            $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&start_date={$ontem}&end_date={$ontem}&daily=temperature_2m_max,temperature_2m_min&timezone=auto";

            // Faz a requisição para a API
            $response = $this->client->get($url);
            $dados = json_decode($response->getBody(), true);

            if (!isset($dados['daily'])) {
                return response()->json(['error' => 'Dados de temperatura não disponíveis'], 500);
            }

            // Retorna a temperatura de ontem
            return response()->json([
                'data' => $ontem,
                'temperatura_max' => $dados['daily']['temperature_2m_max'][0],
                'temperatura_min' => $dados['daily']['temperature_2m_min'][0]
            ]);
        } catch (RequestException $e) {
            return response()->json(['error' => 'Erro ao buscar a temperatura de ontem'], 500);
        }
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

}
