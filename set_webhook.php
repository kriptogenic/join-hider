<?php

require __DIR__ . '/Api.php';

$webhook_secret = getenv('WEBHOOK_SECRET');
$token = getenv('BOT_TOKEN');
$app_name = getenv('HEROKU_APP_NAME');

$api = new Api($token);

$api->setWebhook("https://$app_name.herokuapp.com/?ws=$webhook_secret", allowed_updates: json_encode([
    'channel_post', 'callback_query'
]));

