<?php

namespace GuilhermeGonzaga\Repository\Contracts;

interface RepositoryContract
{
    public function getScopes();

    public function first($columns = ['*'], $fail = true);

    public function find($id, $columns = ['*'], $fail = true);

    public function findBy($attribute, $value);

    public function where(array $where, $boolean = 'and');

    public function create(array $data, $force = true);

    public function update($id, array $data, $force = true);

    public function delete($id = null);

    public function paginate($limit = 15, $columns = ['*'], $pageName = 'page');

    public function exists();

    public function random($qtd = 15);

    public function scopes($scopes);

    public function with($relations);

    public function withBoot();

    public function withoutBoot();

    public function all($columns = ['*']);

    public function get($columns = ['*']);
}
