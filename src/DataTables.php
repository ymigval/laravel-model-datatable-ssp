<?php

namespace Ymigval\LaravelModelToDatatables;

use Closure;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use ReflectionFunction;
use Ymigval\LaravelModelToDatatables\Exceptions\DataTablesColumnDefErrorException;
use Ymigval\LaravelModelToDatatables\Exceptions\DataTablesInvalidArgumentException;
use Ymigval\LaravelModelToDatatables\Exceptions\DataTablesNoColumnDefException;

class DataTables
{
    /**
     * Instance of the query builder
     *
     * @var Builder
     */
    protected $builder;

    /**
     * Request for querying data
     *
     * @var \Illuminate\Http\Request
     */
    protected $requestQuery;

    /**
     * Contains field mapping
     *
     * @var array
     */
    protected $fieldMapping = [];

    /**
     * Contains fields to include in the context
     *
     * @var array
     */
    protected $fieldsInContext = [];

    /**
     * Contains all resolved fields
     *
     * @var array
     */
    protected $fields = [];

    public function __construct($builder, $fieldMapping, array $fieldsInContext = [])
    {
        $this->builder = $builder;
        $this->setFieldMapping($fieldMapping);
        $this->setRequestQuery(Request::instance());
        $this->setFieldsInContext($fieldsInContext);
        $this->homogenizeFieldMapping();
        $this->homogenizeFieldsInContext();
        $this->resolveFields();
    }

    /**
     * Transform a DataTables request
     * The default output is 'response'
     *
     * @param  string  $output Output mode: response | array | json
     * @return \Illuminate\Http\JsonResponse|array|string
     */
    public function transform($output = 'response')
    {
        $data = [];

        $data['draw'] = (int) $this->getRequestQuery('draw');
        $data['recordsTotal'] = $this->builder->count();
        $data['recordsFiltered'] = $data['recordsTotal'];

        $builder = $this->withSearch($this->builder);
        $builder = $this->withOrder($builder);
        $builder = $this->withPagination($builder);

        if ($builder->count()) {
            $data['recordsFiltered'] = $builder->count();
        }

        foreach (
            $builder
                ->get(static::fieldsWithAlias(array_keys($this->fields))) as $row
        ) {
            $column = [];
            foreach ($this->fieldMapping as $field => $format) {
                $column[] = $format(static::isMarker($field) ? null : $row->{$this->fields[$field]['alia']}, $row);
            }

            $data['data'][] = $column;
        }

        if ($output == 'json') {
            return json_encode($data);
        } elseif ($output == 'array') {
            return $data;
        } else {
            return Response::json($data);
        }
    }

    /**
     * Set the field mapping
     *
     * @param  array | Closure  $fieldMapping
     * @return void
     *
     * @throws DataTablesNoColumnDefException | DataTablesInvalidArgumentException
     */
    protected function setFieldMapping($fieldMapping)
    {
        if (gettype($fieldMapping) === 'object' && $fieldMapping instanceof Closure) {
            $fieldMapping = call_user_func($fieldMapping);

            if (! is_array($fieldMapping)) {
                $fieldMapping = [];
            }
        } else {
            if (gettype($fieldMapping) !== 'array') {
                throw new DataTablesInvalidArgumentException();
            }
        }

        if (count($fieldMapping) === 0) {
            throw new DataTablesNoColumnDefException();
        }

        $this->fieldMapping = $fieldMapping;
    }

    /**
     * Set the fields in the context
     *
     * @return void
     */
    protected function setFieldsInContext(array $fieldsInContext = [])
    {
        $this->fieldsInContext = $fieldsInContext;
    }

    /**
     * Set the request query
     *
     * @return void
     */
    protected function setRequestQuery(\Illuminate\Http\Request $requestQuery)
    {
        if (! $this->isDatatablesRequest($requestQuery)) {
            $this->requestQuery = new FactoryRequest(count($this->fieldMapping));
        } else {
            $this->requestQuery = $requestQuery;
        }
    }

    /**
     * Get a request query item
     *
     * @param  string  $key
     * @return string|array|null
     */
    protected function getRequestQuery($key)
    {
        return $this->requestQuery->query($key, null);
    }

    /**
     * Get resolved fields
     *
     * @return array
     */
    protected function getFields()
    {
        return $this->fields;
    }

    /**
     * Homogenize field mapping
     *
     * @return void
     */
    protected function homogenizeFieldMapping()
    {
        $fieldMapping = [];
        foreach ($this->fieldMapping as $field => $format) {

            // Add a return function to the field definition if it doesn't have one
            if (! ($format instanceof Closure)) {
                $fieldMapping[trim($format)] = function ($f, $r) {
                    return $f;
                };

                continue;
            } elseif (is_numeric($field) && $format instanceof Closure) {
                // If the definition has no field, add a marker "{n}"
                $fieldMapping['{'.trim($field).'}'] = $format;

                continue;
            } else {
                $fieldMapping[trim($field)] = $format;
            }
        }

        $this->fieldMapping = $fieldMapping;

        // Inspect the field mapping
        $this->inspectFieldMapping();
    }

    /**
     * Homogenize the fields in the context
     *
     * @return void
     */
    protected function homogenizeFieldsInContext()
    {
        $defaultOptions = ['orderable' => false, 'searchable' => false];

        foreach ($this->fieldsInContext as $field => $options) {

            // Add default options if the field has no defined options
            if (is_numeric($field)) {
                $this->fieldsInContext[trim($options)] = $defaultOptions;
                unset($this->fieldsInContext[$field]);

                continue;
            }

            // If the field has options, merge them with the default options
            if (is_array($options)) {
                $this->fieldsInContext[trim($field)] = array_merge($defaultOptions, $options);

                continue;
            }
        }
    }

    /**
     * Inspect field mapping
     *
     * @return void
     *
     * @throws DataTablesColumnDefErrorException
     */
    protected function inspectFieldMapping()
    {
        $inspectRow = 0;
        foreach ($this->fieldMapping as $field => $format) {
            $inspectRow++;

            if (is_string($field) && ! empty($field) && ! is_numeric($field) && is_callable($format)) {
                continue;
            }

            throw new DataTablesColumnDefErrorException($inspectRow);
        }
    }

    /**
     * Resolve the fields in field mapping and additional fields
     *
     * @return void
     */
    protected function resolveFields()
    {
        // Field mapping
        foreach (array_keys($this->fieldMapping) as $field) {
            if (static::isMarker($field)) {
                continue;
            }

            $unstructureField = static::unstructureField($field);
            $this->fields[$field] = [
                'field' => $unstructureField[0],
                'alia' => isset($unstructureField[1]) ? $unstructureField[1] : static::resolveCompoundFields($unstructureField[0]),
            ];
        }

        // Fields in context
        foreach ($this->fieldsInContext as $field => $options) {
            if (is_numeric($field)) {
                $field = $options;
            }

            $unstructureField = static::unstructureField($field);
            $this->fields[$field] = [
                'field' => $unstructureField[0],
                'alia' => isset($unstructureField[1]) ? $unstructureField[1] : static::resolveCompoundFields($unstructureField[0]),
            ];
        }
    }

    /**
     * Apply ordering to the query
     *
     * @param  Builder  $builder
     * @return Builder
     */
    public function withOrder($builder)
    {
        $fieldMapping = array_keys($this->fieldMapping);

        foreach (($this->getRequestQuery('order') ?? []) as $orderColumn) {
            $columnIndexInRequest = $orderColumn['column'];

            $field = $fieldMapping[$columnIndexInRequest] ?? null;
            $direction = $orderColumn['dir'];

            if ($field) {
                if (((string) $this->getRequestQuery('columns')[$columnIndexInRequest]['orderable']) === 'true') {
                    $orderFields = $this->usedRowField($this->fieldMapping[$field]);

                    if (static::isMarker($field) === false) {
                        $orderFields = array_merge([$field], $orderFields);
                        $orderFields = array_unique($orderFields);
                    }

                    foreach ($orderFields as $targetField) {

                        // If it's a field in the context, check if it's orderable
                        if (array_key_exists($targetField, $this->fieldsInContext)) {
                            if ($this->fieldsInContext[$targetField]['orderable'] === false) {
                                continue;
                            }
                        }

                        $builder->orderBy($this->fields[$targetField]['field'] ?? $targetField, $direction);
                    }
                }

            }
        }

        return $builder;
    }

    /**
     * Apply search to the query
     *
     * @param  Builder  $builder
     * @return Builder
     */
    public function withSearch($builder)
    {
        $globalSearchField = [];
        $specificSearchField = [];
        $columnIndexInRequest = -1;

        foreach ($this->fieldMapping as $field => $format) {
            $columnInRequest = $this->getRequestQuery('columns')[++$columnIndexInRequest] ?? null;

            if ($columnInRequest && ((string) $columnInRequest['searchable']) === 'true') {

                $searchField = $this->usedRowField($format);

                if (static::isMarker($field) === false) {
                    $searchField = array_merge([$field], $searchField);
                    $searchField = array_unique($searchField);
                }

                if (! empty($this->getRequestQuery('search')['value']) || ! empty($columnInRequest['search']['value'])) {
                    if (! empty($columnInRequest['search']['value'])) {
                        // Specific field search
                        $specificSearchField = array_merge($specificSearchField, array_fill_keys($searchField, $columnInRequest['search']['value']));
                    } else {
                        // Global search
                        $globalSearchField = array_merge($globalSearchField, array_fill_keys($searchField, $this->getRequestQuery('search')['value'] ?? ''));
                    }
                }
            }
        }

        $hasSpecificFields = count($specificSearchField) ? true : false;

        // Specific search takes precedence over global search
        $search = $hasSpecificFields ? $specificSearchField : $globalSearchField;

        $builder->where(function ($query) use ($search, $hasSpecificFields) {
            foreach ($search as $targetField => $value) {
                // If it's a field in the context, check if it's searchable
                if (array_key_exists($targetField, $this->fieldsInContext)) {
                    if ($this->fieldsInContext[$targetField]['searchable'] === false) {
                        continue;
                    }
                }

                if ($hasSpecificFields) {
                    $query->where($this->fields[$targetField]['field'] ?? $targetField, 'LIKE', "%{$value}%");
                } else {
                    $query->orWhere($this->fields[$targetField]['field'] ?? $targetField, 'LIKE', "%{$value}%");
                }
            }
        });

        return $builder;
    }

    /**
     * Apply pagination to the query
     *
     * @param  Builder  $builder
     * @return Builder
     */
    public function withPagination($builder)
    {
        if (is_numeric($this->getRequestQuery('length')) && intval($this->getRequestQuery('length')) >= 0) {
            $builder->take($this->getRequestQuery('length'));

            $start = 0;
            if (is_numeric($this->getRequestQuery('start')) && intval($this->getRequestQuery('start')) >= 0) {
                $start = $this->getRequestQuery('start');
            }

            $builder->skip($start);
        }

        return $builder;
    }

    /**
     * Check if it's a marker
     *
     * @return bool
     */
    protected static function isMarker($field)
    {
        if (preg_match('/\{\d+\}/', $field)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the fields used with the $row parameter in the field mapping
     *
     * @return array
     */
    protected function usedRowField(Closure $format)
    {
        $reflection = new ReflectionFunction($format);
        $filename = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();

        $source = file_get_contents($filename);

        //$paramField = (isset($reflection->getParameters()[0])) ? $reflection->getParameters()[0]->getName() : null;

        $paramRow = (isset($reflection->getParameters()[1])) ? $reflection->getParameters()[1]->getName() : null;

        $definition = '';
        if ($source !== false) {
            $sourceLines = explode("\n", $source);
            $definitionLines = array_slice($sourceLines, $startLine - 1, $endLine - $startLine + 1);
            $definition = implode("\n", $definitionLines);
        }

        $traceField = [];
        foreach ($this->fields as $field) {

            $fieldProperty = Str::of('$')
                ->append($paramRow)
                ->append('->')
                ->append($field['alia']);

            if (Str::contains($definition, $fieldProperty)) {
                $traceField[] = $field['field'];
            }
        }

        return $traceField;
    }

    /**
     * Check if the request is a DataTables request
     *
     * @return bool
     */
    protected function isDatatablesRequest(\Illuminate\Http\Request $requestQuery)
    {
        if (
            $requestQuery->isNotFilled('draw') ||
            $requestQuery->isNotFilled('columns') ||
            $requestQuery->isNotFilled('start') ||
            $requestQuery->isNotFilled('length')
        ) {
            return false;
        } else {
            if (
                ! is_numeric($requestQuery->draw) ||
                ! is_array($requestQuery->columns) ||
                ! is_numeric($requestQuery->start) ||
                ! is_numeric($requestQuery->length)
            ) {
                return false;
            } else {
                if (count($requestQuery->columns) === 0) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }

    /**
     * Unstructure field
     *
     * @return array
     */
    protected static function unstructureField(string $field)
    {
        $pattern = '/(?:\sAS\s|\s+)/i';

        return preg_split($pattern, $field);
    }

    /**
     * Transform fields with aliases
     *
     * @return array
     */
    protected static function fieldsWithAlias(array $field)
    {
        return array_map(function ($field) {
            //If you already have aliases do not add custom aliases
            if (count(static::unstructureField($field)) > 1) {
                return $field;
            } else {
                return $field.' AS '.static::resolveCompoundFields($field);
            }
        }, $field);
    }

    /**
     * Resolve compound fields
     *
     * @return string
     */
    protected static function resolveCompoundFields(string $field)
    {
        // Retrieve the part after the last period
        if (strpos($field, '.') !== false) {
            return substr(strrchr($field, '.'), 1);
        } else {
            return $field; // If there is no period, the complete value is returned
        }
    }
}
