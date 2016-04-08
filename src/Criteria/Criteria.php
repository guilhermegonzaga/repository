<?php

namespace Laracodes\Repository\Criteria;

use Laracodes\Repository\Contracts\CriteriaContract;
use Laracodes\Repository\Contracts\RepositoryContract;

abstract class Criteria implements CriteriaContract
{
    /**
     * @param RepositoryContract $repository
     */
    abstract public function apply(RepositoryContract $repository);
}
