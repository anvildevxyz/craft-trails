<?php

namespace anvildev\trails\jobs;

use Craft;
use craft\queue\BaseJob;

/**
 * Sends audit log entries to external services (Splunk, Datadog, S3, Webhook)
 */
class ShipLogJob extends BaseJob
{
    public int $maxTries = 5;
    public int $attempt = 1;
    public string $endpoint = '';
    public string $provider = 'webhook';
    public array $payload = [];

    private ?string $resolvedApiKey = null;

    public function execute($queue): void
    {
        if (!$this->endpoint) {
            Craft::warning('ShipLogJob: No endpoint configured', 'trails');
            return;
        }

        $this->resolvedApiKey = \anvildev\trails\Trails::getInstance()->getSettings()->externalApiKey;

        if (\anvildev\trails\helpers\HostGuard::isBlocked($this->endpoint)) {
            // See BatchShipLogJob for rationale — throw so the queue marks
            // the job as failed and operators see the SSRF block in the
            // failed-jobs list instead of a silent "shipped" success.
            $safeEndpoint = $this->safeEndpointForLogs($this->endpoint);
            Craft::error("Trails: refusing to ship logs to blocked host: {$safeEndpoint}", 'trails');
            throw new \RuntimeException("Trails: external endpoint {$safeEndpoint} resolves to a private/reserved/metadata address and was blocked by HostGuard.");
        }

        try {
            match ($this->provider) {
                'splunk' => $this->shipToSplunk(),
                'datadog' => $this->shipToDatadog(),
                's3' => $this->shipToS3(),
                default => $this->shipToWebhook(),
            };
        } catch (\Throwable $e) {
            Craft::error("ShipLogJob failed (attempt {$this->attempt}/{$this->maxTries}): " . $e->getMessage(), 'trails');

            if ($this->attempt >= $this->maxTries) {
                Craft::error("ShipLogJob abandoned after {$this->maxTries} attempts for provider '{$this->provider}'", 'trails');
                // Surface terminal failure to Craft queue instead of marking job successful.
                throw $e;
            }

            Craft::$app->getQueue()->delay(min(60 * (2 ** ($this->attempt - 1)), 3600))->push(new self([
                'endpoint' => $this->endpoint,
                'provider' => $this->provider,
                'payload' => $this->payload,
                'maxTries' => $this->maxTries,
                'attempt' => $this->attempt + 1,
            ]));
        }
    }

    private function shipToSplunk(): void
    {
        $this->sendHttpRequest($this->endpoint, [
            'event' => $this->payload,
            'time' => time(),
            'source' => 'craft-trails',
            'sourcetype' => '_json',
        ], ['Authorization' => 'Splunk ' . $this->resolvedApiKey]);
    }

    private function shipToDatadog(): void
    {
        $this->sendHttpRequest($this->endpoint, [
            'ddsource' => 'craft-trails',
            'ddtags' => 'env:' . (Craft::$app->env ?? 'production'),
            'hostname' => gethostname(),
            'service' => 'craft-cms',
            'message' => json_encode($this->payload),
        ], [
            'DD-API-KEY' => $this->resolvedApiKey,
            'Content-Type' => 'application/json',
        ]);
    }

    private function shipToS3(): void
    {
        $this->sendHttpRequest($this->endpoint, [
            'logs' => [$this->payload],
            'timestamp' => date('c'),
            'source' => 'craft-trails',
        ], [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->resolvedApiKey,
        ]);
    }

    private function shipToWebhook(): void
    {
        $headers = ['Content-Type' => 'application/json', 'User-Agent' => 'Craft-Trails/1.0'];
        if ($this->resolvedApiKey) {
            $headers['Authorization'] = 'Bearer ' . $this->resolvedApiKey;
        }

        // Pre-encode so HMAC signs the exact bytes sent on the wire
        $jsonPayload = json_encode($this->payload) ?: '{}';
        $timestamp = (string) time();

        $plugin = \anvildev\trails\Trails::getInstance();
        $rawSecret = $plugin->getSettings()->webhookSecret;
        $secret = \craft\helpers\App::parseEnv($rawSecret);
        if (is_string($secret) && str_starts_with($secret, '$')) {
            \Craft::warning("Trails: webhookSecret appears to reference an unresolved env var: {$rawSecret}", 'trails');
            $secret = '';
        }
        if (is_string($secret) && $secret !== '') {
            // X-Trails-Timestamp is part of the HMAC input so receivers can detect
            // and reject replays. Receivers MUST verify the timestamp is within
            // a short freshness window (e.g. ±5 minutes) — the signature alone
            // does not prevent replay attacks. See WEBHOOKS.md.
            $signature = hash_hmac('sha256', $timestamp . '.' . $jsonPayload, $secret);
            $headers['X-Trails-Signature'] = 'sha256=' . $signature;
            $headers['X-Trails-Timestamp'] = $timestamp;
        }

        $this->sendHttpRequest($this->endpoint, $jsonPayload, $headers);
    }

    /**
     * @param string|array $data Pre-encoded JSON string or array (array will use Guzzle's json option)
     */
    private function sendHttpRequest(string $url, string|array $data, array $headers = []): void
    {
        $client = Craft::createGuzzleClient([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true,
            // SSRF: never follow redirects — HostGuard only validates the initial host.
            // A 30x to a private IP would otherwise bypass the guard.
            'allow_redirects' => false,
        ]);

        $options = ['headers' => $headers];
        if (is_string($data)) {
            $options['body'] = $data;
        } else {
            $options['json'] = $data;
        }

        $client->post($url, $options);

        Craft::info("ShipLogJob: Successfully shipped log to {$this->provider}", 'trails');
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('trails', 'Shipping audit log to {provider}', ['provider' => $this->provider]);
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
