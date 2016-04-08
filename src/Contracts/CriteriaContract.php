<?php

namespace Laracodes\Repository\Contracts;

interface CriteriaContract
{
    public function apply(RepositoryContract $repository);
}
