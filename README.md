# ymigval/laravel-model-datatable-ssp

Extension designed to seamlessly integrate Laravel models with [server-side DataTables](https://datatables.net/examples/server_side/simple.html). It provides a convenient and efficient way to fetch, transform, and display data from your Laravel models in DataTables.

## Installation

To get started, install the package via Composer:
```bash
composer require ymigval/laravel-model-datatable-ssp
```

## Usage with Eloquent Models

To use DataTables with an Eloquent model, you can create an instance or query your model and call the `datatable()` method with column mappings.

```php
use App\Models\Customer;

return (new Customer())->datatable([
    'first_name', 'last_name', 'phone'
]);
```

Alternatively, you can call the static `datatable()` method:

```php
use App\Models\Customer;

return Customer::datatable([
    'first_name', 'last_name', 'phone'
]);
```

- Replace `Customer` with your Eloquent model.
- `['first_name' => 'first_name', 'last_name' => 'last_name', 'phone' => 'phone']`: Define the mappings of your model's fields.

### Customizing Columns

You can customize field values by providing closures in your column mappings.

```php
use App\Models.Customer;

return Customer::where('type', 'male')->datatable([
    'first_name',
    'last_name',
    'active' => function ($field, $row) {
        return ($field) ? 'Yes' : 'No';
    }
]);
```

- $field: Contains the value of the field in that mapping.
- $row: Contains the values of the fields in the context of the current row.

### Adding Additional Columns

You can add additional columns by using closures:

```php
use App\Models\Customer;

return Customer::datatable([
    'first_name',
    'last_name',
    function () {
        return 'additional column #1';
    },
    function () {
        return 'additional column #2';
    }
]);
```

### Fields in Context

Define model fields in context to access related data or perform custom formatting.

```php
use App\Models\Customer;

return Customer::datatable([
    'first_name' => function ($field, $row) {
        return $field . ' ' . $row->last_name;
    }
], ['last_name']);
```

By default, fields added to the context can be searched and sorted. You can configure this behavior by adding options to the field:

```php
use App\Models\Customer;

return Customer::datatable([
    'first_name' => function ($field, $row) {
        return $field . ' ' . $row->last_name;
    }
], ['last_name' => ['orderable' => true, 'searchable' => false]]);
```

## Usage with Query Builder

You can use DataTable with Query Builder by calling `dataTable()` on a query builder instance.

```php
use Illuminate\Support\Facades\DB;

return DB::table('customers')
    ->datatable([
        'first_name',
        'last_name',
        'phone'
    ]);
```

## Transforming Output Data

You can transform the datatable return into various formats such as `response`, `array`, or `json` by specifying it as the third parameter.

By default, a response is returned.

```php
use App\Models\Customer;

(new Customer())->datatable(
    ['first_name', 'last_name', 'phone'],
    [],
    'array'
);
```

```php
use Illuminate\Support\Facades\DB;

DB::table('customers')->datatable(
    ['first_name', 'last_name', 'phone'],
    [],
    'json'
);
```

## Advanced Usage

### Using Callbacks for Column Mappings

You can use a callback to define columns dynamically.

```php
use App\Models\Customer;

return (new Customer())->datatable(
    function () {
        return ['first_name', 'last_name', 'phone'];
    }
);
```

### Union Queries

Perform union queries with DataTable.

```php
use App\Models\Customer;

return Customer::join('business', 'business.id_customer', '=', 'customers.id')
    ->datatable(
        function () {
            return ['customers.first_name', 'customers.last_name', 'business.name'];
        }
    );
```

You can also add aliases to the fields in the column mapping or fields in context:

```php
use App\Models\Customer;

return Customer::join('business', 'business.id_customer', '=', 'customers.id')
    ->datatable(
        [
            'customers.first_name AS f_name',
            'customers.last_name AS l_name',
            'business.name AS aaa',
        ],
        ['customers.phone AS contact' => ['orderable' => false, 'searchable' => true]]
    );
```

## Using Eloquent Relationships

#### Note on Using Relations

When using relations, there are some limitations:

- Avoid using related fields as column values without a closure.
- To utilize the value of a related field, it should be accessed through a formatting closure. Use the second parameter of the closure to access the value. Remember that the second parameter contains the values of the fields in the context of the current row.
- Related fields cannot be sorted or searched.

Please make sure to add the local key used in the relation to your column mappings or fields in context.

```php
use App\Models\Customer;

return Customer::with('business')
    ->datatable(
        [
            'first_name',
            'last_name',
            function ($field, $row) {
                return $row->business->name;
            },
        ],
        ['id'] // 'id' is the localKey field specified in the relation with 'business'
    );
```

For more usage examples, refer to the test cases.

## Installing DataTables in Your Application

In the official DataTables documentation: https://datatables.net/, you will find the steps to install the library in your application.

Check out the server-side processing examples: https://datatables.net/examples/server_side/simple.html

## Changelog
Please refer to the [CHANGELOG](CHANGELOG.md) for more information about recent changes.

## License
The MIT License (MIT). For more information, please see the [License File](LICENSE).
