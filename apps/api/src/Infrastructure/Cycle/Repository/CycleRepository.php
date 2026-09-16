<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Infrastructure\Cycle\CycleOrm;
use Cycle\ORM\EntityManager;
use Cycle\ORM\ORM;

abstract class CycleRepository
{
    protected ORM $orm;
    public function __construct(?ORM $orm = null)
    {
        $this->orm = $orm ?: CycleOrm::create();
    }
    protected function save(object $entity): object
    {
        (new EntityManager($this->orm))->persist($entity)->run();
        return $entity;
    }
    protected function find(string $class, int $id): ?object
    {
        return $this->orm->getRepository($class)->findByPK($id);
    }
    protected function deleteEntity(object $entity): void
    {
        (new EntityManager($this->orm))->delete($entity)->run();
    }
}
