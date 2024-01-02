<?php

namespace Ymigval\LaravelModelToDatatables;

/**
 * Create a mock DataTables request
 */
class FactoryRequest
{
    /**
     * Number of columns to generate
     *
     * @var int
     */
    protected $numberOfColumns;

    public function __construct(int $numberOfColumns)
    {
        $this->numberOfColumns = $numberOfColumns;
    }

    /**
     * Retrieve a query string item from the request
     *
     * @return array|string|null
     */
    public function query(?string $key = null, ?string $default = null)
    {
        if (is_null($key)) {
            return $this->generateDefinition();
        }

        return $this->generateDefinition()[$key] ?? $default;
    }

    /**
     * Generate mock request parameters
     *
     * @return array
     */
    protected function generateDefinition()
    {
        return [
            'draw' => 0,
            'columns' => $this->generateColumns(),
            'order' => [],
            'start' => 0,
            'length' => -1,
            'search' => [
                'value' => null,
                'regex' => false,
            ],
        ];
    }

    /**
     * Generate mock columns
     *
     * @return array
     */
    protected function generateColumns()
    {
        $columns = [];

        for ($i = 0; $i < $this->numberOfColumns; $i++) {
            $columns[] = [
                'data' => $i,
                'name' => null,
                'searchable' => true,
                'orderable' => true,
                'search' => [
                    'value' => null,
                    'regex' => false,
                ],
            ];
        }

        return $columns;
    }
}
