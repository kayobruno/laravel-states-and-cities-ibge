<?php

namespace Kayo\StatesAndCitiesIbge\Services\Integration;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class IbgeRestIntegrationService extends BaseRestIntegrationService
{
    /**
     * Retorna todos os estados brasileiros
     *
     * @return array
     * @throws GuzzleException
     */
    public function getStates()
    {
        $uri = $this->getBaseUri() . '/localidades/estados';
        $request = new Request('GET', $uri);
        $response = $this->proxy($request, ['orderBy' => 'nome']);

        return $response['data'];
    }

    /**
     * Retorna todas as cidades de um determinado estado brasileiro
     *
     * @param int $stateIbgeId
     * @return mixed
     * @throws GuzzleException
     */
    public function getCitiesByState(int $stateIbgeId)
    {
        $uri = $this->getBaseUri() . "/localidades/estados/{$stateIbgeId}/municipios";
        $request = new Request('GET', $uri);
        $response = $this->proxy($request, ['orderBy' => 'nome']);

        return $response['data'];
    }
}
