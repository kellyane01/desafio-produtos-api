<?php

return [
    'hosts' => array_filter(array_map('trim', explode(',', env('ELASTICSEARCH_HOSTS', 'http://elasticsearch:9200')))),

    'username' => env('ELASTICSEARCH_USERNAME'),

    'password' => env('ELASTICSEARCH_PASSWORD'),

    'index' => env('ELASTICSEARCH_INDEX', 'produtos'),

    'retries' => (int) env('ELASTICSEARCH_RETRIES', 2),

    'suggestion_size' => (int) env('ELASTICSEARCH_SUGGESTION_SIZE', 5),
];
