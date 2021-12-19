<?php


class Api
{
    private string $apiEndpoit;
    private array $cacheApiCall;

    public function __construct(private ?string $token = null)
    {
        $this->apiEndpoit = 'https://api.telegram.org/bot' . $this->token . '/';
    }

    // Simulating 403 of nginx for fun
    public function forbidden()
    {
        header('HTTP/1.0 403 Forbidden');
        echo <<<'TAG'
<html>
<head><title>403 Forbidden</title></head>
<body>
<center><h1>403 Forbidden</h1></center>
<hr><center>nginx</center>
</body>
</html>
<!-- a padding to disable MSIE and Chrome friendly error page -->
<!-- a padding to disable MSIE and Chrome friendly error page -->
<!-- a padding to disable MSIE and Chrome friendly error page -->
<!-- a padding to disable MSIE and Chrome friendly error page -->
<!-- a padding to disable MSIE and Chrome friendly error page -->
<!-- a padding to disable MSIE and Chrome friendly error page -->
TAG;
        die();
    }

    /**
     * @return stdClass
     * @throws JsonException
     */
    public function getUpdate() :stdClass
    {
        return json_decode(file_get_contents('php://input'), flags: JSON_THROW_ON_ERROR);
    }

    private function apiCall(string $method, array $params = []) :?stdClass
    {
        if (isset($this->token)) {
            return $this->httpApiCall($method, $params);
        }

        $this->responseApiCall($method, $params);
        return null;
    }

    private function httpApiCall(string $method, array $params = []) :stdClass
    {
        $ch = curl_init($this->apiEndpoit . $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!empty($params)) {
            $params = $this->clearNullValues($params);
            curl_setopt_array($ch,[
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $params
            ]);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception(curl_error($ch));
        }

        $json_response = json_decode($response, flags: JSON_THROW_ON_ERROR);
        curl_close ($ch);

        if ($json_response->ok === false) {
            throw new Exception('API Error:' . json_encode([
                    'response' =>$response,
                    'method' => $method,
                    'params' => $params
                ]));
        }

        return $json_response;
    }

    private function responseApiCall(string $method, array $params = [])
    {
        if (isset($this->cacheApiCall)) {
            throw new Exception('In tokenless mode 2 or more api call can not be executed!');
        }

        $params['method'] = $method;
        $this->cacheApiCall = $params;
    }

    public function executeResponseApiCall()
    {
        if (!isset($this->cacheApiCall)) {
            return;
        }

        $payload = json_encode($this->cacheApiCall);
        header('Content-Type: application/json');
        header('Content-Length: ' . mb_strlen($payload));

        echo $payload;
    }

    private function clearNullValues(array $values) :array
    {
        return array_filter($values, fn($value) => !is_null($value));
    }

    public function callbackKeyboard( array ...$button_lines) :string
    {
        $keyboard = [];

        $i = 0;
        foreach ($button_lines as $button_line) {
            foreach ($button_line as $callback_data => $text) {
                $keyboard[$i][] = [
                    'callback_data' => $callback_data,
                    'text' => $text,
                ];
            }
            $i++;
        }

        return json_encode(['inline_keyboard' => $keyboard]);
    }

    public function sendMessage(
        int|string $chat_id, string $text, string $parse_mode = null, string $entities = null,
        bool $disable_web_page_preview = null, bool $disable_notification = null,
        int $reply_to_message_id = null, bool $allow_sending_without_reply = null,
        string $reply_markup = null
    ) {
        return $this->apiCall('sendMessage', compact(
        'chat_id', 'text', 'parse_mode',
                'entities', 'disable_web_page_preview', 'disable_notification', 'reply_to_message_id',
                'allow_sending_without_reply', 'reply_markup'
        ));
    }

    public function deleteMessage(int|string $chat_id, int $message_id)
    {
        return $this->apiCall('deleteMessage', compact('chat_id', 'message_id'));
    }

    public function editMessageReplyMarkup(
        int|string $chat_id = null, int $message_id = null,
        string $inline_message_id = null, string $reply_markup = null)
    {
        return $this->apiCall('editMessageReplyMarkup', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'inline_message_id' => $inline_message_id,
            'reply_markup' => $reply_markup
        ]);
    }

    public function leaveChat(int|string $chat_id)
    {
        return $this->apiCall('leaveChat', ['chat_id' => $chat_id]);
    }

    public function setWebhook(string $url, CURLFile $certificate = null, string $ip_adress = null,
                               int $max_connections = null, string $allowed_updates = null,
                               bool $drop_pending_updates = null)
    {
        return $this->apiCall('setWebhook', compact('url', 'certificate',
            'ip_adress', 'max_connections', 'allowed_updates', 'drop_pending_updates'));
    }
}