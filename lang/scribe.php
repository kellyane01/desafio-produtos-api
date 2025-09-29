<?php

return [
    'labels' => [
        'search' => 'Buscar',
        'base_url' => 'URL base',
    ],

    'auth' => [
        'none' => 'Esta API não exige autenticação.',
        'instruction' => [
            'query' => <<<'TEXT'
                Para autenticar as requisições, inclua o parâmetro de query **`:parameterName`** na URL.
                TEXT,
            'body' => <<<'TEXT'
                Para autenticar as requisições, inclua o parâmetro **`:parameterName`** no corpo da requisição.
                TEXT,
            'query_or_body' => <<<'TEXT'
                Para autenticar as requisições, inclua o parâmetro **`:parameterName`** na query string ou no corpo da requisição.
                TEXT,
            'bearer' => <<<'TEXT'
                Para autenticar as requisições, envie o cabeçalho **`Authorization`** com o valor **`"Bearer :placeholder"`**.
                TEXT,
            'basic' => <<<'TEXT'
                Para autenticar as requisições, envie o cabeçalho **`Authorization`** no formato **`"Basic {credentials}"`**.
                O valor de `{credentials}` deve conter seu usuário/ID e senha separados por dois pontos (:)
                e codificados em base64.
                TEXT,
            'header' => <<<'TEXT'
                Para autenticar as requisições, envie o cabeçalho **`:parameterName`** com o valor **`":placeholder"`**.
                TEXT,
        ],
        'details' => <<<'TEXT'
            Todos os endpoints autenticados estão destacados com o selo `requer autenticação` na documentação abaixo.
            TEXT,
    ],

    'headings' => [
        'introduction' => 'Introdução',
        'auth' => 'Autenticando requisições',
    ],

    'endpoint' => [
        'request' => 'Requisição',
        'headers' => 'Cabeçalhos',
        'url_parameters' => 'Parâmetros de URL',
        'body_parameters' => 'Parâmetros de corpo',
        'query_parameters' => 'Parâmetros de query',
        'response' => 'Resposta',
        'response_fields' => 'Campos da resposta',
        'example_request' => 'Requisição de exemplo',
        'example_response' => 'Resposta de exemplo',
        'responses' => [
            'binary' => 'Dados binários',
            'empty' => 'Resposta vazia',
        ],
    ],

    'try_it_out' => [
        'open' => 'Testar requisição ⚡',
        'cancel' => 'Cancelar 🛑',
        'send' => 'Enviar requisição 💥',
        'loading' => '⏱ Enviando...',
        'received_response' => 'Resposta recebida',
        'request_failed' => 'A requisição retornou erro',
        'error_help' => <<<'TEXT'
            Dica: verifique sua conexão com a rede.
            Se você mantém esta API, confirme que o serviço está em execução e que o CORS está habilitado.
            Consulte o console de Dev Tools para mais detalhes de depuração.
            TEXT,
    ],

    'links' => [
        'postman' => 'Ver coleção Postman',
        'openapi' => 'Ver especificação OpenAPI',
    ],
];
