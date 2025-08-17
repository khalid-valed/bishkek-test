<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

function sendTelegram(string $text): void
{
    $post_parameters = [
        'text' => $text,
        'chat_id' => $_ENV['TELEGRAM_CHANNEL']
    ];

    $url = "https://api.telegram.org/{$_ENV['TELEGRAM_BOT_TOKEN']}/sendMessage";
    if ($_ENV['ENV' === 'prod']) {
        asyncCurl($url, $post_parameters);
    }
}

function asyncCurl(string $url, array $post_parameters): void
{
    $client = new Client();

    try {
        $client->postAsync($url, ['form_params' => $post_parameters])
            ->then(
                function ($response) {
                    // Log successful response if needed
                },
                function (RequestException $e) {
                    // Log error if needed
                    logger('Request failed: ' . $e->getMessage());
                }
            )->wait();
    } catch (\Exception $e) {
        // Handle any other exceptions
        logger('Unexpected error: ' . $e->getMessage());
    }
}

function logger($data): void
{
    $logFile = ROOT_DIR. "/debug.txt";
    $logData = $data . ',' . PHP_EOL;
    file_put_contents($logFile, $logData, FILE_APPEND | LOCK_EX);
}

function parseNginxLog($logText)
{
    // Regular expression to match the log pattern
    $pattern = '/(?P<ip>\d{1,3}(?:\.\d{1,3}){3})\s-\s-\s\[(?P<date>.*?)\]\s"(?P<method>[A-Z]+)\s(?P<path>\/[^"]*)\sHTTP\/[\d\.]+"\s(?P<status>\d{3})\s\d+\s"-"\s"(?P<userAgent>[^"]+)"/';

    // Split log text into lines
    $lines = explode("\n", trim($logText));

    // Placeholder for parsed logs
    $parsedLogs = [];

    // Iterate through each log line
    foreach ($lines as $line) {
        if (preg_match($pattern, $line, $matches)) {
            // Sample IP-based location mapping for simplicity (you can use an external API here)
            $ipLocationMap = [
                '18.133.136.156' => 'United Kingdom',
                '135.125.246.189' => 'France',
                // Add more IPs and locations as needed
            ];

            // Add parsed data to the array
            $parsedLogs[] = [
                'ip' => $matches['ip'],
                'date' => $matches['date'],
                'method' => $matches['method'],
                'path' => $matches['path'],
                'status' => $matches['status'],
                'userAgent' => $matches['userAgent'],
                'location' => isset($ipLocationMap[$matches['ip']]) ? $ipLocationMap[$matches['ip']] : 'Unknown'
            ];
        }
    }

    // Convert the parsed logs to JSON
    return $parsedLogs;
}
