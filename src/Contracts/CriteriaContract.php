<?php

namespace GuilhermeGonzaga\Repository\Contracts;

interface CriteriaContract
{
    public function apply(RepositoryContract $repository);
}
