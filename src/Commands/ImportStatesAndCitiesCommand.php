<?php

namespace Kayo\StatesAndCitiesIbge\Commands;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Kayo\StatesAndCitiesIbge\Models\City;
use Kayo\StatesAndCitiesIbge\Models\State;
use Kayo\StatesAndCitiesIbge\Services\Integration\IbgeRestIntegrationService;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ImportStatesAndCitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ibge:import-states-cities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa todos os estados e cidades da API do IBGE';

    /**
     * @var IbgeRestIntegrationService
     */
    protected $ibgeService;

    /**
     * Create a new command instance.
     *
     * @param IbgeRestIntegrationService $ibgeRestIntegrationService
     */
    public function __construct(IbgeRestIntegrationService $ibgeRestIntegrationService)
    {
        $this->ibgeService = $ibgeRestIntegrationService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws GuzzleException
     */
    public function handle()
    {
        try {
            $this->importStates();
            $this->importCities();
        } catch (ServiceUnavailableHttpException | \Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     */
    private function importStates()
    {
        $states = $this->ibgeService->getStates();
        $this->output->progressStart(count($states));
        $totalCreated = $totalUpdated = 0;

        foreach ($states as $value) {
            $this->output->progressAdvance();

            $stateData = [
                'ibge_id' => $value['id'],
                'name' => $value['nome'],
                'acronym' => $value['sigla'],
            ];

            if (isset($value['regiao']) && !empty($value['regiao'])) {
                $stateData['ibge_region_id'] = $value['regiao']['id'];
                $stateData['region_acronym'] = $value['regiao']['sigla'];
                $stateData['region_name'] = $value['regiao']['nome'];
            }

            if (!$state = State::where('ibge_id', $value['id'])->first()) {
                State::create($stateData);
                $totalCreated++;
                continue;
            }

            $state->update($stateData);
            $totalUpdated++;
        }
        $this->output->progressFinish();
        $this->generateTableLog('Estados', $totalCreated, $totalUpdated);
    }

    /**
     * @throws GuzzleException
     */
    private function importCities()
    {
        $tableBody = [];
        $states = State::all();
        foreach ($states as $state) {
            $this->info("Importando as cidades do estado: {$state->name}");
            $this->newLine();

            $totalCreated = $totalUpdated = 0;
            $cities = $this->ibgeService->getCitiesByState($state->ibge_id);
            $this->output->progressStart(count($cities));

            foreach ($cities as $value) {
                $this->output->progressAdvance();

                $dataCity = [
                    'ibge_id' => $value['id'],
                    'name' => $value['nome'],
                    'state_id' => $state->id,
                ];

                if (!$city = City::where('ibge_id', $value['id'])->first()) {
                    City::create($dataCity);
                    $totalCreated++;
                    continue;
                }

                $city->update($dataCity);
                $totalUpdated++;
            }

            $this->output->progressFinish();

            $tableBody[] = [
                'state' => $state->name,
                'created' => $totalCreated,
                'updated' => $totalUpdated,
            ];

            $this->info("Todas as cidades do estado {$state->name} foram importadas com sucesso");
            $this->newLine();
        }

        $tableHeader = ['Estado', 'Total de Cidades Cadastradas', 'Total de Cidades Atualizadas'];
        $this->table($tableHeader, $tableBody);
    }

    /**
     * @param string $table
     * @param int $totalCreated
     * @param int $totalUpdated
     */
    private function generateTableLog(string $table, int $totalCreated, int $totalUpdated)
    {
        $header = ['Tabela', 'Total de Cadastros', 'Total de Atualizações'];
        $body = [['name' => $table, 'created' => $totalCreated, 'updated' => $totalUpdated]];
        $this->table($header, $body);
    }
}
