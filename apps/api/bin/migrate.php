<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Config\MySQL\DsnConnectionConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Migrations\Migrator;
use Cycle\Migrations\Config\MigrationConfig;

$dbal = new DatabaseManager(new DatabaseConfig([
    'databases' => ['default' => ['connection' => 'mysql']],
    'connections' => ['mysql' => new MySQLDriverConfig(new DsnConnectionConfig(
        getenv('DATABASE_URL') ?: 'mysql:host=host.docker.internal;port=3306;dbname=dev_vendaly',
        getenv('DB_USER') ?: 'vendaly_app', getenv('DB_PASSWORD') ?: '',
    ))],
]));
$config = new MigrationConfig(['directory' => dirname(__DIR__) . '/migrations', 'safe' => true]);
$migrator = new Migrator($config, $dbal, new Cycle\Migrations\FileRepository($config));
$migrator->configure();
while (($migration = $migrator->run()) !== null) echo $migration->getState()->getName() . PHP_EOL;
