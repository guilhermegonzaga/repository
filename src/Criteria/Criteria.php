<?php

namespace GuilhermeGonzaga\Repository\Criteria;

use GuilhermeGonzaga\Repository\Contracts\CriteriaContract;
use GuilhermeGonzaga\Repository\Contracts\RepositoryContract;

abstract class Criteria implements CriteriaContract
{
    /**
     * @param RepositoryContract $repository
     */
    abstract public function apply(RepositoryContract $repository);
}
