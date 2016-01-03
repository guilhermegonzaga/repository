<?php

namespace GuilhermeGonzaga\Repository\Eloquent;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;
use GuilhermeGonzaga\Repository\Contracts\RepositoryContract;
use GuilhermeGonzaga\Repository\Exceptions\RepositoryException;

abstract class Repository implements RepositoryContract
{
    /**
     * @var \Illuminate\Container\Container
     */
    private $app;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $scopes;

    /**
     * @var bool
     */
    protected $boot;

    /**
     * @param \Illuminate\Container\Container $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->cleanRepository();
    }

    /**
     * @return mixed
     */
    abstract function model();

    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @throws RepositoryException
     */
    protected function makeModel()
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof \Illuminate\Database\Eloquent\Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * @throws RepositoryException
     */
    protected function cleanRepository()
    {
        $this->scopes = new Collection();
        $this->withBoot();
        $this->makeModel();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param array $columns
     * @param bool  $fail
     * @return mixed
     */
    public function first($columns = ['*'], $fail = true)
    {
        $this->applyBoot();
        $this->applyScopes();

        $method = 'firstOrFail';

        if (!$fail) {
            $method = 'first';
        }

        $result = $this->model->{$method}($columns);

        $this->cleanRepository();

        return $result;
    }

    /**
     * @param       $id
     * @param array $columns
     * @param bool  $fail
     * @return mixed
     */
    public function find($id, $columns = ['*'], $fail = true)
    {
        $this->applyBoot();
        $this->applyScopes();

        $method = 'findOrFail';

        if (!$fail) {
            $method = 'find';
        }

        $result = $this->model->{$method}($id, $columns);

        $this->cleanRepository();

        return $result;
    }

    /**
     * @param array $where
     * @param bool  $or
     * @return $this
     */
    public function where(array $where, $or = false)
    {
        $method = 'where';

        if ($or) {
            $method = 'orWhere';
        }

        foreach ($where as $k => $v) {

            if (is_array($v)) {
                $this->model = $this->model->{$method}($v[0], $v[1], $v[2]);
            } else {
                $this->model = $this->model->{$method}($k, $v);
            }

        }

        return $this;
    }

    /**
     * @param array $data
     * @param bool  $force
     * @return mixed
     * @throws RepositoryException
     */
    public function create(array $data, $force = true)
    {
        if ($force) {
            $model = $this->model->newInstance($data)->save();
        } else {
            $model = $this->model->create($data);
        }

        $this->cleanRepository();

        return $model;
    }

    /**
     * @param       $id
     * @param array $data
     * @param bool  $force
     * @return mixed
     */
    public function update($id, array $data, $force = true)
    {
        $model = $this->find($id);

        if ($force) {
            $model = $model->fill($data)->save();
        } else {
            $model = $model->update($data);
        }

        $this->cleanRepository();

        return $model;
    }

    /**
     * @param array|string|null $id
     * @return int
     */
    public function delete($id = null)
    {
        $model = null;

        if (is_array($id)) {

            $model = $this->model->destroy($id);

        } elseif (is_string($id)) {

            $model = $this->find($id)->delete();

        } elseif ($this->model instanceof \Illuminate\Database\Eloquent\Builder) {

            $model = $this->first()->delete();
        }

        $this->cleanRepository();

        return $model;
    }

    /**
     * @param int    $limit
     * @param array  $columns
     * @param string $pageName
     * @return mixed
     */
    public function paginate($limit = 15, $columns = ['*'], $pageName = 'page')
    {
        $this->applyBoot();
        $this->applyScopes();

        $result = $this->model->paginate($limit, $columns, $pageName);

        $this->cleanRepository();

        return $result;
    }

    /**
     * @param int $qtd
     * @return $this
     */
    public function random($qtd = 15)
    {
        $this->model = $this->model->orderByRaw('RAND()')->take($qtd);

        return $this;
    }

    /**
     * @param Closure|string|array $scopes
     * @return $this
     */
    public function scopes($scopes)
    {
        if (is_array($scopes)) {

            foreach ($scopes as $scope) {

                $this->scopes->push($scope);
            }

        } else {

            $this->scopes->push($scopes);
        }

        return $this;
    }

    /**
     * @param array|string $relations
     * @return $this
     */
    public function with($relations)
    {
        $this->model = $this->model->with($relations);

        return $this;
    }

    /**
     * @return $this
     */
    public function withBoot()
    {
        $this->boot = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutBoot()
    {
        $this->boot = false;

        return $this;
    }

    /**
     *
     */
    protected function applyBoot()
    {
        if ($this->boot and method_exists($this, 'boot')) {
            $this->boot();
        }
    }

    /**
     *
     */
    protected function applyScopes()
    {
        $scopes = $this->getScopes();

        if ($scopes->count() > 0) {

            foreach ($scopes as $scope) {

                if ($scope instanceof Closure) {

                    $this->model = $scope($this->model);

                } elseif (is_string($scope) and is_callable([$this->model, $scope])) {

                    $this->model = call_user_func([$this->model, $scope], $this->model);
                }

            }

        }
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        return $this->get($columns);
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function get($columns = ['*'])
    {
        $this->applyBoot();
        $this->applyScopes();

        if ($this->model instanceof \Illuminate\Database\Eloquent\Builder) {
            $results = $this->model->get($columns);
        } else {
            $results = $this->model->all($columns);
        }

        $this->cleanRepository();

        return $results;
    }

    /**
     * @param $method
     * @param $arguments
     * @return $this|mixed
     * @throws RepositoryException
     */
    public function __call($method, $arguments)
    {
        if (is_callable([$this->model, $method])) {

            $result = call_user_func_array([$this->model, $method], $arguments);

            if (!$result instanceof \Illuminate\Database\Eloquent\Builder) {

                throw new RepositoryException("Method {$method} can not be called in " . get_class($this));
            }

            $this->model = $result;

        } else {

            throw new RepositoryException("Method {$method} not exists in {$this->model()}");
        }

        return $this;
    }
}
