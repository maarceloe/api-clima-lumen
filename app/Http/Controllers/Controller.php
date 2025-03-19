<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use illuminate\Http\Request;
use GuzzleHttp\Client;

class ClimaController extends BaseController
{
    public function climaAtual(Request $request){
        //Obtém a cidade local
        $cidade = $request->get('cidade','São Paulo');

        //Chave API
        $apiKey = env('API_KEY');

        //Inicia o cliente http pra fazer requisição na api OpenWeather
        $client = new Client();

        //Faz a requisição get da api pra buscar o clima atual
        $res = $client->get("https://api.openweathermap.org/data/2.5/weather?q={$cidade}&appid={$apiKey}&units=metric");
    }
}
