<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle;

use Cycle\Annotated\Entities;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Config\MySQL\DsnConnectionConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\Schema\Compiler;
use Cycle\Schema\Registry;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;

final class CycleOrm
{
    public static function create(): ORM
    {
        $dbal = new DatabaseManager(new DatabaseConfig(['databases' => ['default' => ['connection' => 'mysql']], 'connections' => ['mysql' => new MySQLDriverConfig(new DsnConnectionConfig(getenv('DATABASE_URL') ?: 'mysql:host=host.docker.internal;port=3306;dbname=dev_vendaly', getenv('DB_USER') ?: 'vendaly_app', getenv('DB_PASSWORD') ?: ''))]]));
        $locator = new TokenizerEntityLocator(new ClassLocator((new Finder())->files()->in(dirname(__DIR__, 2) . '/Domain')));
        $registry = new Registry($dbal);
        $schema = (new Compiler())->compile($registry, [new Entities($locator)]);
        return new ORM(new Factory($dbal), new \Cycle\ORM\Schema($schema));
    }
}
