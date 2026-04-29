<?php

namespace anvildev\trails\jobs;

use anvildev\trails\models\Settings;
use anvildev\trails\Trails;
use Craft;
use craft\queue\BaseJob;

class BatchShipLogJob extends BaseJob
{
    public string $endpoint = '';
    public string $provider = 'webhook';
    public array $payloads = [];

    public function execute($queue): void
    {
        if (!$this->endpoint || empty($this->payloads)) {
            return;
        }

        $plugin = Craft::$app->getPlugins()->getPlugin('trails');
        if (!$plugin instanceof Trails) {
            throw new \RuntimeException('Trails plugin is not available.');
        }
        /** @var Settings $settings */
        $settings = $plugin->getSettings();
        $apiKey = $settings->externalApiKey;

        if (\anvildev\trails\helpers\HostGuard::isBlocked($this->endpoint)) {
            // Raise instead of returning silently. A silent return previously
            // caused the queue to mark the job as successfully shipped while
            // the payloads were lost — operators never saw the SSRF block in
            // queue/info or the CP failed-jobs view. Throwing lets Craft's
            // queue infrastructure record the failure (and, for webhook
            // provider, fall through to per-payload retries via ShipLogJob
            // which ALSO performs its own HostGuard check).
            $safeEndpoint = $this->safeEndpointForLogs($this->endpoint);
            Craft::error("Trails: refusing to ship batch to blocked host: {$safeEndpoint}", 'trails');
            throw new \RuntimeException("Trails: external endpoint {$safeEndpoint} resolves to a private/reserved/metadata address and was blocked by HostGuard.");
        }

        $client = Craft::createGuzzleClient([
            'timeout' => 60,
            'connect_timeout' => 10,
            'verify' => true,
            // SSRF: never follow redirects — HostGuard only validates the initial host.
            'allow_redirects' => false,
        ]);

        $headers = match ($this->provider) {
            'splunk' => ['Authorization' => 'Splunk ' . $apiKey],
            'datadog' => ['DD-API-KEY' => $apiKey, 'Content-Type' => 'application/json'],
            's3' => ['Content-Type' => 'application/json', 'x-api-key' => $apiKey],
            default => array_filter([
                'Content-Type' => 'application/json',
                'User-Agent' => 'Craft-Trails/1.0',
                'Authorization' => $apiKey ? 'Bearer ' . $apiKey : null,
            ]),
        };

        $body = match ($this->provider) {
            'splunk' => array_map(fn($p) => ['event' => $p, 'time' => time(), 'source' => 'craft-trails', 'sourcetype' => '_json'], $this->payloads),
            'datadog' => array_map(fn($p) => [
                'ddsource' => 'craft-trails',
                'ddtags' => 'env:' . (Craft::$app->env ?? 'production'),
                'hostname' => gethostname(),
                'service' => 'craft-cms',
                'message' => json_encode($p),
            ], $this->payloads),
            's3' => ['logs' => $this->payloads, 'timestamp' => date('c'), 'source' => 'craft-trails'],
            default => $this->payloads,
        };

        // Pre-encode body so HMAC signs the exact bytes sent on the wire
        $jsonBody = json_encode($body);

        // Add HMAC signature for webhook provider using webhookSecret (not the bearer token)
        if ($this->provider === 'webhook' && $jsonBody !== false) {
            $secret = \craft\helpers\App::parseEnv($settings->webhookSecret);
            if (is_string($secret) && str_starts_with($secret, '$')) {
                \Craft::warning("Trails: webhookSecret appears to reference an unresolved env var: {$settings->webhookSecret}", 'trails');
                $secret = '';
            }
            if (is_string($secret) && $secret !== '') {
                // X-Trails-Timestamp is part of the HMAC input so receivers can
                // detect and reject replays. Receivers MUST verify the timestamp
                // is within a short freshness window (e.g. ±5 minutes); the
                // signature alone does not prevent replay attacks. See WEBHOOKS.md.
                $timestamp = (string) time();
                $signature = hash_hmac('sha256', $timestamp . '.' . $jsonBody, $secret);
                $headers['X-Trails-Signature'] = 'sha256=' . $signature;
                $headers['X-Trails-Timestamp'] = $timestamp;
            }
        }

        try {
            $client->post($this->endpoint, [
                'body' => $jsonBody,
                'headers' => $headers,
            ]);
            Craft::info("BatchShipLogJob: Shipped " . count($this->payloads) . " logs to {$this->provider}", 'trails');
        } catch (\Throwable $e) {
            Craft::error("BatchShipLogJob failed: " . $e->getMessage(), 'trails');
            // Fall back to individual shipping
            foreach ($this->payloads as $payload) {
                Craft::$app->getQueue()->push(new ShipLogJob([
                    'endpoint' => $this->endpoint,
                    'provider' => $this->provider,
                    'payload' => $payload,
                ]));
            }
        }
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('trails', 'Shipping {count} audit logs to {provider}', [
            'count' => count($this->payloads),
            'provider' => $this->provider,
        ]);
    }

    private function safeEndpointForLogs(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '[invalid-url]';
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        if ($host === '') {
            return '[invalid-url]';
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';

        return "{$scheme}://{$host}{$port}{$path}";
    }
}
