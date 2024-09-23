<?php

namespace App\Services;

use GuzzleHttp\Client;

class FCMService
{
    protected $client;
    protected $fcmUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->fcmUrl = 'https://fcm.googleapis.com/v1/projects/dealdash-878ba/messages:send';
    }

    public function sendNotification($title, $body, $fcm_token, $data = [])
    {
        $accessToken = $this->getAccessToken();
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ];

        $payload = [
            'message' => [
                'token' => $fcm_token,  // or use 'tokens' for multiple recipients
                // 'tokens' => $fcm_tokens,  // or use 'tokens' for multiple recipients
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $data,  // Optional: Custom data you want to send
            ],
        ];

        $response = $this->client->post($this->fcmUrl, [
            'headers' => $headers,
            'json'    => $payload,
        ]);

        return json_decode($response->getBody(), true);
    }

    private function getAccessToken()
    {
        $keyFilePath = storage_path('app/firebase/serviceAccountKey.json');
        $client = new \Google_Client();
        $client->setAuthConfig($keyFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        return $client->fetchAccessTokenWithAssertion()['access_token'];
    }
}
