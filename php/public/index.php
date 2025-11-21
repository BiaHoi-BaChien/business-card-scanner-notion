<?php

use CardScanner\{buildNotionPayload, createNotionPage, extractContactData, hashPasskey, jsonResponse, loadEnv, loadPropertyConfig, loadSettings, notionClient, openaiClient, verifyNotionConnection, verifyPasskey, verifyPasswordLogin};
use GuzzleHttp\Exception\GuzzleException;

require __DIR__ . '/../vendor/autoload.php';

$root = realpath(__DIR__ . '/..');
session_start();
loadEnv($root);
$settings = loadSettings();
$properties = loadPropertyConfig($root);

$path = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function getJsonBody(): array
{
    $input = file_get_contents('php://input');
    $decoded = json_decode($input, true);
    return is_array($decoded) ? $decoded : [];
}

try {
    if ($path === '/login' && $method === 'POST') {
        $body = getJsonBody();
        $ok = verifyPasswordLogin($body['username'] ?? '', $body['password'] ?? '', $settings);
        if ($ok) {
            $_SESSION['auth'] = true;
            jsonResponse(['ok' => true]);
        } else {
            jsonResponse(['ok' => false, 'error' => 'Invalid credentials'], 401);
        }
        return;
    }

    if ($path === '/passkey/register' && $method === 'POST') {
        $body = getJsonBody();
        if (empty($body['passkey']) || empty($settings['auth_secret'])) {
            jsonResponse(['ok' => false, 'error' => 'Missing passkey or secret'], 400);
            return;
        }
        $_SESSION['registered_passkey_hash'] = hashPasskey($body['passkey'], $settings['auth_secret']);
        jsonResponse(['ok' => true]);
        return;
    }

    if ($path === '/passkey/login' && $method === 'POST') {
        $body = getJsonBody();
        if (verifyPasskey($body['passkey'] ?? '', $settings)) {
            $_SESSION['auth'] = true;
            jsonResponse(['ok' => true]);
        } else {
            jsonResponse(['ok' => false, 'error' => 'Invalid passkey'], 401);
        }
        return;
    }

    if (!($_SESSION['auth'] ?? false)) {
        jsonResponse(['error' => 'Unauthorized'], 401);
        return;
    }

    if ($path === '/extract' && $method === 'POST') {
        if (empty($_FILES['images'])) {
            jsonResponse(['error' => 'Upload 1-2 images via multipart form-data field "images[]"'], 400);
            return;
        }
        $files = $_FILES['images'];
        $count = is_array($files['name']) ? count($files['name']) : 0;
        if ($count < 1 || $count > 2) {
            jsonResponse(['error' => 'Exactly 1 or 2 images are required'], 400);
            return;
        }

        $normalized = [];
        for ($i = 0; $i < $count; $i++) {
            $normalized[] = [
                'tmp_name' => $files['tmp_name'][$i],
                'type' => $files['type'][$i] ?? null,
            ];
        }

        $client = openaiClient($settings);
        $contact = extractContactData($client, $normalized);
        jsonResponse(['contact' => $contact]);
        return;
    }

    if ($path === '/notion/verify' && $method === 'POST') {
        $client = notionClient($settings);
        $ok = verifyNotionConnection($client, $settings['notion_data_source_id'] ?? '');
        jsonResponse(['ok' => $ok]);
        return;
    }

    if ($path === '/notion/create' && $method === 'POST') {
        $body = getJsonBody();
        $contact = is_array($body['contact'] ?? null) ? $body['contact'] : [];
        $attachments = is_array($body['attachments'] ?? null) ? $body['attachments'] : [];

        $payload = buildNotionPayload($contact, $properties);
        $client = notionClient($settings);
        $page = createNotionPage($client, $payload, $attachments);
        jsonResponse(['page' => $page]);
        return;
    }

    jsonResponse(['error' => 'Not found'], 404);
} catch (GuzzleException $e) {
    jsonResponse(['error' => 'HTTP error', 'message' => $e->getMessage()], 500);
} catch (Throwable $e) {
    jsonResponse(['error' => 'Unexpected error', 'message' => $e->getMessage()], 500);
}
