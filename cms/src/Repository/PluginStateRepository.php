<?php

namespace App\Repository;

use App\Entity\PluginState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PluginState>
 */
class PluginStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PluginState::class);
    }

    public function findOneByName(string $name): ?PluginState
    {
        return $this->findOneBy(['name' => $name]);
    }
}
