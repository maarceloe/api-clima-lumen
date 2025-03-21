<h1>Desafio Backend Júnior -API de Clima</h1>

API de Clima utilizando Lumen Framework <BR>
Este é um projeto de API desenvolvido com o micro-framework Lumen. A API fornece informações climáticas, como o clima atual, previsão para os próximos 7 dias, comparação de temperaturas e etc, utilizando dados de APIs externas como OpenWeatherMap, Open-Meteo e OpenCage.

A API permite:

Obter o clima atual: Informações como temperatura, umidade e descrição do clima para uma cidade ou coordenadas específicas.<br>
Previsão para os próximos 7 dias: Temperaturas máximas e mínimas diárias, além de descrições do clima.<br>
Comparar temperaturas: Comparação entre as temperaturas máximas de ontem e hoje para uma localização específica.

Como testar este projeto:


1. Pré-requisitos<br>
PHP >= 8.0<br>
Composer<br>
Git<br>
Um navegador ou ferramenta para testar APIs (como Postman ou cURL)


2. Clone o repositório
git clone https://github.com/ar7utz/api-clima-lumen


3. Instale as dependências<br>
composer install


4. Configure as variáveis de ambiente<br>
cp .env.example .env <br>
(isso se o arquivo do repositório clonado der erro, caso contrário o arquivo já está modificado)


5. Abra o terminal na pasta raíz do projeto e inicie o servidor<br>
php -S localhost:8000 -t public


6. Teste as rotas da API<br>
O projeto funcionará corretamente e as rotas seram visíveis copiando e colando no navegador ou colando as rotas no postam/apidog, entre outros.
<br>

# Lumen PHP Framework

[![Build Status](https://travis-ci.org/laravel/lumen-framework.svg)](https://travis-ci.org/laravel/lumen-framework)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel/lumen-framework)](https://packagist.org/packages/laravel/lumen-framework)
[![Latest Stable Version](https://img.shields.io/packagist/v/laravel/lumen-framework)](https://packagist.org/packages/laravel/lumen-framework)
[![License](https://img.shields.io/packagist/l/laravel/lumen)](https://packagist.org/packages/laravel/lumen-framework)

Laravel Lumen is a stunningly fast PHP micro-framework for building web applications with expressive, elegant syntax. We believe development must be an enjoyable, creative experience to be truly fulfilling. Lumen attempts to take the pain out of development by easing common tasks used in the majority of web projects, such as routing, database abstraction, queueing, and caching.

> **Note:** In the years since releasing Lumen, PHP has made a variety of wonderful performance improvements. For this reason, along with the availability of [Laravel Octane](https://laravel.com/docs/octane), we no longer recommend that you begin new projects with Lumen. Instead, we recommend always beginning new projects with [Laravel](https://laravel.com).

## Official Documentation

Documentation for the framework can be found on the [Lumen website](https://lumen.laravel.com/docs).

## Contributing

Thank you for considering contributing to Lumen! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Security Vulnerabilities

If you discover a security vulnerability within Lumen, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

## License

The Lumen framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
