<?php

require __DIR__ . '/../Api.php';

$webhook_secret = getenv('WEBHOOK_SECRET');
$api = new Api();

if (empty($_GET['ws']) || $_GET['ws'] !== $webhook_secret) {
    $api->forbidden();
}


// Fetching update
try {
    $update = $api->getUpdate();
} catch (JsonException){
    $api->forbidden();
}

// Routing
if (isset($update->message) && in_array($update->message->chat->type, ['group', 'supergroup'])) {
    if(isset($update->message->new_chat_members)) {
        // Delete join message
        $api->deleteMessage($update->message->chat->id, $update->message->message_id);
    } elseif (isset($update->message->left_chat_member)) {
        // Delete leave message
        $api->deleteMessage($update->message->chat->id, $update->message->message_id);
    }
}

$api->executeResponseApiCall();