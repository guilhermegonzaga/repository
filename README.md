# Laravel Repositories

[![Latest Stable Version](https://poser.pugx.org/guilhermegonzaga/repository/v/stable)](https://packagist.org/packages/guilhermegonzaga/repository) [![Total Downloads](https://poser.pugx.org/guilhermegonzaga/repository/downloads)](https://packagist.org/packages/guilhermegonzaga/repository) [![Latest Unstable Version](https://poser.pugx.org/guilhermegonzaga/repository/v/unstable)](https://packagist.org/packages/guilhermegonzaga/repository) [![License](https://poser.pugx.org/guilhermegonzaga/repository/license)](https://packagist.org/packages/guilhermegonzaga/repository)

Repository is a design pattern for Laravel 5 which is used to abstract the database layer.
<br>
It makes your application easier to be maintained.

## Installation

#### Laravel (5.1 and 5.2)

Execute the following command to get the latest version of the package:

```terminal
composer require guilhermegonzaga/repository
```

## Methods

```php
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
public function criteria($class, array $args = []);
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
        return \App\Category::class;
    }

    // Optional method, global rules
    public function boot()
    {
        $this->findBy('active', true);
        $this->orderBy('created_at', 'desc');
    }

    // Other methods
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

    ...
}
```

Finally, use the repository in the controller:

```php
use App\Repositories\CategoryRepository;

class CategoriesController extends Controller
{
    protected $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    ...
}
```

## Usage methods

Find all results.
<br>
Methods ```all``` and ```get``` generate the same result:

```php
$results = $this->repository->all();
$results = $this->repository->get();
```

Find first result:

```php
$result = $this->repository->where([['abc', '!=', 'def']])->first(); // Fire ModelNotFoundException (firstOrFail)
$result = $this->repository->findBy('abc', 'def')->first(['*'], false); // Not fire ModelNotFoundException (first)
```

Find all results with pagination:

```php
$results = $this->repository->paginate();
```

Find result by id:

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
    ['price', '>=', '9.90']

])->get();
```

Find using scope predefined in Model:

```php
$results = $this->repository->scopes('scopeName')->get();
$results = $this->repository->scopes(['scope1', 'scope2'])->get();
```

Find using custom scope:

```php
$results = $this->repository->scopes(function($repository) {
    $repository->orderBy('created_at', 'desc');
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
$this->repository->where(['active' => false])->where(['payment_failed' => true], 'or')->delete();
```

## Criteria

This package makes it very easy to work with scopes/criteria.
<br>
Create classes to abstract these rules and make them reusable:

```php
namespace App\Repositories\Criteria;

use GuilhermeGonzaga\Repository\Contracts\RepositoryContract as Repository;
use GuilhermeGonzaga\Repository\Criteria\Criteria;

class PopularProducts extends Criteria
{
    public function apply(Repository $repository)
    {
        $repository->where([
            ['purchases', '>', '50']
        ]);
    }
}
```

Receiving arguments:

```php
namespace App\Repositories\Criteria;

use GuilhermeGonzaga\Repository\Contracts\RepositoryContract as Repository;
use GuilhermeGonzaga\Repository\Criteria\Criteria;

class ProductsByCategory extends Criteria
{
    protected $category;

    public function __construct($category)
    {
        $this->category = $category;
    }

    public function apply(Repository $repository)
    {
        $repository->findBy('category_id', $this->category);
    }
}
```
Use the criteria in the controller:

```php
use App\Repositories\ProductRepository;
use App\Repositories\Criteria\PopularProducts;
use App\Repositories\Criteria\ProductsByCategory;
use App\Repositories\Criteria\ProductsByCategories;

class ProductsController extends Controller
{
    protected $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $this->repository->criteria(PopularProducts::class);
    }

    public function showByCategory($category)
    {
        $this->repository->criteria(ProductsByCategory::class, [$category]);
    }

    public function showByCategories($category1, $category2)
    {
        $this->repository->criteria(ProductsByCategories::class, [$category1, $category2]);
    }
}
```

## Other methods

In addition to the methods that are available in this package, you can call any method default Model. When you call a method that does not exist in this package, automatically call the method in the Model.

Ex:

```php
$results = $this->repository->orderBy('created_at', 'desc')->get();
$results = $this->repository->whereIn('category_id', [2, 4, 6])->all();
$results = $this->repository->whereBetween('votes', [10, 100])->get();
```

## Credits

This package is largely inspired by <a href="https://github.com/andersao/l5-repository">this</a> great package by @andersao.
