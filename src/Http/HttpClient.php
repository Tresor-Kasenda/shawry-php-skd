<?php

declare(strict_types=1);

namespace Shwary\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shwary\Config;
use Shwary\Exceptions\ApiException;
use Shwary\Exceptions\AuthenticationException;

final class HttpClient
{
    private GuzzleClient $client;
    private LoggerInterface $logger;

    public function __construct(
        private readonly Config $config,
        ?GuzzleClient $client = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->client = $client ?? $this->createDefaultClient();
    }

    private function createDefaultClient(): GuzzleClient
    {
        return new GuzzleClient([
            'base_uri' => $this->config->getApiUrl() . '/',
            'timeout' => $this->config->getTimeout(),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'x-merchant-id' => $this->config->getMerchantId(),
                'x-merchant-key' => $this->config->getMerchantKey(),
            ],
        ]);
    }

    /**
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, [], $query);
    }

    /**
     * @throws ApiException
     * @throws AuthenticationException
     */
    private function request(string $method, string $endpoint, array $data = [], array $query = []): array
    {
        $this->logger->debug('Shwary API Request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $this->sanitizeForLog($data),
        ]);

        $options = [];
        
        if (!empty($data)) {
            $options['json'] = $data;
        }
        
        if (!empty($query)) {
            $options['query'] = $query;
        }

        try {
            $response = $this->client->request($method, $endpoint, $options);
            $body = (string) $response->getBody();
            $result = json_decode($body, true) ?? [];

            $this->logger->debug('Shwary API Response', [
                'status' => $response->getStatusCode(),
                'body' => $this->sanitizeForLog($result),
            ]);

            return $result;
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();

            $this->logger->error('Shwary API Client Error', [
                'status' => $statusCode,
                'body' => (string) $response->getBody(),
            ]);

            if ($statusCode === 401) {
                throw AuthenticationException::invalidCredentials();
            }

            throw ApiException::fromResponse($response);
        } catch (ServerException $e) {
            $response = $e->getResponse();
            
            $this->logger->error('Shwary API Server Error', [
                'status' => $response->getStatusCode(),
                'body' => (string) $response->getBody(),
            ]);

            if ($response->getStatusCode() === 502) {
                throw ApiException::badGateway();
            }

            throw ApiException::fromResponse($response);
        } catch (GuzzleException $e) {
            $this->logger->error('Shwary API Network Error', [
                'message' => $e->getMessage(),
            ]);

            throw ApiException::networkError($e->getMessage(), $e);
        }
    }

    private function sanitizeForLog(array $data): array
    {
        $sensitiveKeys = ['merchantKey', 'x-merchant-key', 'clientPhoneNumber'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***REDACTED***';
            }
        }

        return $data;
    }
}
