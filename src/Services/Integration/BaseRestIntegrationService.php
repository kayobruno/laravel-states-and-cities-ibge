<?php

namespace Kayo\StatesAndCitiesIbge\Services\Integration;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\TransferStats;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class BaseRestIntegrationService
{
    /**
     * @var string
     */
    private $baseUri;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var Client
     */
    private $client;

    /**
     * BaseRestIntegrationService constructor.
     * @param string $baseUri
     * @param int $timeout
     */
    public function __construct(string $baseUri = '', int $timeout = 20)
    {
        $this->baseUri = $baseUri;
        $this->timeout = $timeout;
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client([
                'base_uri' => $this->baseUri,
                'timeout' => $this->timeout,
            ]);
        }

        return $this->client;
    }

    /**
     * @param $contents
     * @return mixed|null
     * @throws Exception
     */
    protected function decode($contents)
    {
        $data = null;
        try {
            if (!empty($contents)) {
                $data = Utils::jsonDecode($contents, true);
            }
        } catch (InvalidArgumentException $e) {
            throw new Exception(Utils::jsonDecode($e->getMessage()));
        }

        return $data;
    }

    /**
     * @param Request $request
     * @param array $query
     * @param int $timeout
     * @return array
     * @throws GuzzleException
     */
    protected function proxy(Request $request, array $query = [], int $timeout = 10): array
    {
        try {
            /** @var Uri $uri */
            $uri = null;
            $guzzleResponse = $this->getClient()->send($request, [
                'on_stats' => function (TransferStats $stats) use (&$uri) {
                    $uri = $stats->getEffectiveUri();
                },
                'query' => $query,
                'timeout' => $timeout,
            ]);
            $contents = $guzzleResponse->getBody()->getContents();
            $status = $guzzleResponse->getStatusCode();
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $contents = $e->getResponse()->getBody();
                $status = $e->getCode();
            } else {
                throw new ServiceUnavailableHttpException('Pas de réponse');
            }
        } catch (GuzzleException | Exception $e) {
            throw new ServiceUnavailableHttpException($e);
        }

        $data = Utils::jsonDecode((string) $contents, true);
        try {
            $data = Utils::jsonDecode((string) $contents, true);
        } catch (InvalidArgumentException $e) {
            if ($status != Response::HTTP_NO_CONTENT) {
                throw new ServiceUnavailableHttpException(
                    'La syntaxe de la response est erronée: '.dump((string) $contents)
                    . ' >>> ' . json_last_error_msg()
                );
            }
        }

        return [
            'data' => $data,
            'status' => $status,
        ];
    }

    /**
     * @param array $response
     * @return array
     * @throws Exception
     */
    protected function formatResponse(array $response): array
    {
        $defaultErrorMessage = __('messages.error.unavailable');
        if (!isset($response['status'])) {
            throw new Exception(__LINE__ . ": $defaultErrorMessage");
        }

        if (intdiv($response['status'], 100) == 2) {
            if (isset($response['data']['data'])) {
                $response['data'] = $response['data']['data'];
            }
        } else {
            $errors = [$defaultErrorMessage];
            if (isset($response['status']) && $response['status'] === Response::HTTP_NOT_FOUND) {
                $errors = [__('messages.error.notfound')];
            }
            unset($response['data']);
            $response['errors'] = $errors;
        }

        return $response;
    }
}
