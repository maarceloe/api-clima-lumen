<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Manipula uma requisição para adicionar cabeçalhos CORS.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Adiciona cabeçalhos CORS
        $response->headers->set('Access-Control-Allow-Origin', '*'); // Permite todas as origens
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        return $response;
    }
}
