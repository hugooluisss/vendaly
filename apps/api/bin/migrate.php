<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Config\Postgres\DsnConnectionConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Migrations\Migrator;
use Cycle\Migrations\Config\MigrationConfig;

$dbal = new DatabaseManager(new DatabaseConfig([
    'databases' => ['default' => ['connection' => 'pgsql']],
    'connections' => ['pgsql' => new PostgresDriverConfig(new DsnConnectionConfig(
        getenv('DATABASE_URL') ?: 'pgsql:host=postgres;port=5432;dbname=vendaly',
        getenv('POSTGRES_USER') ?: 'vendaly', getenv('POSTGRES_PASSWORD') ?: 'vendaly',
    ))],
]));
$config = new MigrationConfig(['directory' => dirname(__DIR__) . '/migrations', 'safe' => true]);
$migrator = new Migrator($config, $dbal, new Cycle\Migrations\FileRepository($config));
$migrator->configure();
while (($migration = $migrator->run()) !== null) echo $migration->getState()->getName() . PHP_EOL;
