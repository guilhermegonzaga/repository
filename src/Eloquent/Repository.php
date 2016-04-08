<?php

namespace Laracodes\Repository\Eloquent;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;
use Illuminate\Database\Eloquent\Builder;
use Laracodes\Repository\Contracts\RepositoryContract;
use Laracodes\Repository\Contracts\CriteriaContract;
use Laracodes\Repository\Exceptions\RepositoryException;

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
     * @var \Illuminate\Support\Collection
     */
    protected $criteria;

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
    abstract public function model();

    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @throws RepositoryException
     */
    protected function makeModel()
    {
        $model = $this->app->make($this->model());

        if (! $model instanceof \Illuminate\Database\Eloquent\Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * @param       $class
     * @param array $args
     * @return mixed
     * @throws RepositoryException
     */
    protected function makeCriteria($class, array $args)
    {
        $criteria = $this->app->make($class, $args);

        if (! $criteria instanceof CriteriaContract) {
            throw new RepositoryException("Class {$class} must be an instance of Laracodes\\Repository\\Criteria\\Criteria");
        }

        return $criteria;
    }

    /**
     * @throws RepositoryException
     */
    protected function cleanRepository()
    {
        $this->scopes = new Collection();
        $this->criteria = new Collection();
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
     * @return \Illuminate\Support\Collection
     */
    public function getCriteria()
    {
        return $this->criteria;
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
        $this->applyCriteria();

        $method = $fail ? 'firstOrFail' : 'first';

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
        $this->applyCriteria();

        $method = $fail ? 'findOrFail' : 'find';

        $result = $this->model->{$method}($id, $columns);

        $this->cleanRepository();

        return $result;
    }

    /**
     * @param string $attribute
     * @param mixed  $value
     * @return $this
     */
    public function findBy($attribute, $value)
    {
        $this->model = $this->model->where($attribute, '=', $value);

        return $this;
    }

    /**
     * @param array  $where
     * @param string $boolean
     * @return $this
     */
    public function where(array $where, $boolean = 'and')
    {
        foreach ($where as $k => $v) {
            list($field, $condition, $value) = is_array($v) ? $v : [$k, '=', $v];

            $this->model = $this->model->where($field, $condition, $value, $boolean);
        }

        return $this;
    }

    /**
     * @param array $data
     * @param bool  $force
     * @return mixed
     * @throws RepositoryException
     */
    public function create(array $data, $force = false)
    {
        $model = $force ? $this->model->forceCreate($data) : $this->model->create($data);

        $this->cleanRepository();

        return $model;
    }

    /**
     * @param array $data
     * @param int|null $id
     * @param bool $force
     * @return mixed
     */
    public function update(array $data, $id = null, $force = false)
    {
        if (is_null($id) and $this->model instanceof Builder) {
            $model = $this->first();
        } else {
            $model = $this->find($id);
        }

        $model = $force ? $model->forceFill($data)->save() : $model->update($data);

        $this->cleanRepository();

        return $model;
    }

    /**
     * @param mixed $id
     * @return int
     */
    public function delete($id = null)
    {
        $model = null;

        if (is_array($id)) {
            $model = $this->model->destroy($id);
        } elseif (! is_null($id)) {
            $model = $this->find($id)->delete();
        } elseif ($this->model instanceof Builder) {
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
        $this->applyCriteria();

        $result = $this->model->paginate($limit, $columns, $pageName);

        $this->cleanRepository();

        return $result;
    }

    /**
     * @return mixed
     */
    public function exists()
    {
        $this->applyBoot();
        $this->applyScopes();
        $this->applyCriteria();

        $result = $this->model->exists();

        $this->cleanRepository();

        return $result;
    }

    /**
     * @param Closure $scope
     * @param string $boolean
     * @return $this
     */
    public function scopes(Closure $scope, $boolean = 'and')
    {
        $this->scopes->push([$scope, $boolean]);

        return $this;
    }

    /**
     * @param       $class
     * @param array $args
     * @return $this
     */
    public function criteria($class, array $args = [])
    {
        $this->criteria->push([$class, $args]);

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
     * @return void
     */
    protected function applyBoot()
    {
        if ($this->boot and method_exists($this, 'boot')) {
            $this->boot();
        }
    }

    /**
     * @return void
     */
    protected function applyScopes()
    {
        $scopes = $this->getScopes();

        if ($scopes->count() > 0) {
            foreach ($scopes as $scope) {
                list($closure, $boolean) = $scope;

                $this->whereNested($closure, $boolean);
            }
        }
    }

    /**
     * @return void
     */
    protected function applyCriteria()
    {
        $criteria = $this->getCriteria();

        if ($criteria->count() > 0) {
            foreach ($criteria as $c) {
                list($class, $args) = $c;

                $object = $this->makeCriteria($class, $args);
                $object->apply($this);
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
        $this->applyCriteria();

        if ($this->model instanceof Builder) {
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

            if (! $result instanceof Builder) {
                throw new RepositoryException("Method '{$method}' can't be called in ".get_class($this));
            }

            $this->model = $result;
        } else {
            throw new RepositoryException("Method '{$method}' not exists in {$this->model()}");
        }

        return $this;
    }
}
