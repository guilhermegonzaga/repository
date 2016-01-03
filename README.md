# Laravel Repositories

Repository is a design pattern for Laravel 5 which is used to abstract the database layer.
<br>
It makes your application easier to be maintained.

##### Important: This package is still being tested, use with caution.

## Installation

#### Laravel 5.1 and 5.2

Run the following command from you terminal:

```terminal
composer require "guilhermegonzaga/repository: 5.2.*"
```

#### Laravel 5.0

Run the following command from you terminal:

```terminal
composer require "guilhermegonzaga/repository: 5.0.*"
```

## Methods

```php
public function first($columns = ['*'], $fail = true);
public function find($id, $columns = ['*'], $fail = true);
public function where(array $where, $or = false);
public function create(array $data, $force = true);
public function update($id, array $data, $force = true);
public function delete($id = null);
public function paginate($limit = 15, $columns = ['*'], $pageName = 'page');
public function random($qtd = 15);
public function scopes($scopes);
public function with($relations);
public function withBoot();
public function withoutBoot();
public function all($columns = ['*']);
public function get($columns = ['*']);
```

## Usage

Create your repository class.
<br>
Note that your repository class must extend ```GuilhermeGonzaga\Repository\Eloquent\Repository``` and implement ```model()``` method.

```php
namespace App\Repositories;

use GuilhermeGonzaga\Repository\Eloquent\Repository;

class CategoryRepository extends Repository
{
    public function model()
    {
        return 'App\Category';
    }
}
```

By implementing ```model()``` method you telling repository what model class you want to use.
<br>
Create your model:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model; // or any other type model class

class Category extends Model
{
    protected $table = 'categories';
    
    protected $fillable = [
        'name',
        'parent_id',
        ...
    ];
}
```

Finally, use the repository in the controller:

```php
use App\Repositories\CategoryRepository;

class CategoriesController extends Controller {

    protected $repository;

    public function __construct(CategoryRepository $repository) {

        $this->repository = $repository;
    }

    ...
}
```

## Usage methods

Find all results:
Methods ```all``` and ```get``` generate the same result:

```php
$results = $this->repository->all();
$results = $this->repository->get();
```

Find first result:

```php
$result = $this->repository->where(['abc' => 'def'])->first(); // Fire ModelNotFoundException (firstOrFail)
$result = $this->repository->where(['abc' => 'def'])->first(['*'], false); // Not fire ModelNotFoundException (first)
```

Find all results with pagination:

```php
$results = $this->repository->paginate();
```

Find result by id.
<br>
By default, if not found, triggers ModelNotFoundException:

```php
$result = $this->repository->find($id); // Fire ModelNotFoundException (findOrFail)
$result = $this->repository->find($id, ['*'], false); // Not fire ModelNotFoundException (find)
```

Loading the model relationships:

```php
$result = $this->repository->with('relationName')->find($id);
$result = $this->repository->with(['relation1', 'relation2'])->find($id);
```

Find result by multiple fields:

```php
$results = $this->repository->where([

    //Default condition (=) 
    'user_id' => '10',
    
    //Custom condition
    ['title', 'LIKE', '%search%']
    
])->get();
```

Find all using scope predefined in Model:

```php
$results = $this->repository->scopes('scopeName')->get();
$results = $this->repository->scopes(['scope1', 'scope2'])->get();
```

Find all using custom scope:

```php
$results = $this->repository->scopes(function($query) {
    return $query->orderBy('created_at', 'desc');
})->all();
```

Get random results:

```php
$results = $this->repository->scopes('popular')->random()->get();
```

Enable/disable ```boot()``` method in repository:

```php
$results = $this->repository->withBoot()->all();
$results = $this->repository->withoutBoot()->all();
```

Create new entry:

```php
$result = $this->repository->create(Input::all()); // without $fillable
$result = $this->repository->create(Input::all(), false); // with $fillable
```

Update entry:

```php
$result = $this->repository->update($id, Input::all()); // without $fillable
$result = $this->repository->update($id, Input::all(), false); // with $fillable
```

Delete entry:

```php
$this->repository->delete($id);
$this->repository->delete([1, 2, 3]);
$this->repository->where(['active' => true])->delete();
```

## Credits

This package is largely inspired by <a href="https://github.com/andersao/l5-repository">this</a> great package by @andersao.
