# Estados e Cidades do Brasil a partir da API do IBGE

Um pacote Laravel que cria e atualiza todos os estados e cidades do Brasil a partir de uma integração com a API do IBGE

## Instalação
```
composer require kayo-bruno/states-and-cities-ibge
```
Assim que terminar você precisar registrar o ServiceProvider no seu arquivo `app.php`
```
// config/app.php

'providers' => [
    Kayo\StatesAndCitiesIbge\Providers\StatesAndCitiesIbgeServiceProvider::class,
];
```

Agora execute o comando abaixo para publicar as configurações necessárias
```
php artisan vendor:publish --provider="Kayo\StatesAndCitiesIbge\Providers\StatesAndCitiesIbgeServiceProvider"
```

Execute o comando abaixo para criar as tabelas no banco de dados
```
php artisan migrate
```

E por fim adicione a configuração abaixo no seu arquivo `.env`
```
IBGE_REST_INTEGRATION_HOST="https://servicodados.ibge.gov.br/api/v1"
```

## Utilização

Para importar todos os estados e cidades da API do IBGE basta executar o comando abaixo:
```
php artisan ibge:import-states-cities
```

Obs.: Nenhum dado será duplicado caso o comando seja executado mais de uma vez, na verdade ele irá atualizar todos os registros. <br />
Então sinta-se a vontade para criar um `CRON` onde será executado em um determinado intervalo de tempo para atualizar seu banco de dados.
