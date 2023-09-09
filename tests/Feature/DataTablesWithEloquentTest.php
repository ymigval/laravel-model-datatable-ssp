<?php

namespace Ymigval\LaravelModelToDatatables\Tests\Feature;

use Workbench\App\Models\Customer;
use Ymigval\LaravelModelToDatatables\Tests\TestCase;

class DataTablesWithEloquentTest extends TestCase
{
    /**
     * Test DataTables output as a response object.
     */
    public function test_output_response()
    {
        $result = (new Customer())->datatable(['first_name', 'last_name', 'phone']);

        $this->assertIsObject($result);
    }

    /**
     * Test DataTables output as an array.
     */
    public function test_output_array()
    {
        $result = (new Customer())->datatable(['first_name', 'last_name', 'phone'], [], 'array');

        $this->assertIsArray($result);
    }

    /**
     * Test DataTables output as JSON.
     */
    public function test_output_json()
    {
        $result = (new Customer())->datatable(['first_name', 'last_name', 'phone'], [], 'json');

        $this->assertIsString($result);
    }

    /**
     * Test formatting a column in DataTables.
     */
    public function test_format_column()
    {
        $result = (new Customer())->datatable(
            [
                'first_name',
                'last_name',
                'phone' => function ($column) {
                    return '+1' . $column;
                },
            ]
        );

        $this->assertStringContainsString('+1', $result);
    }

    /**
     * Test column definition with a compound field.
     */
    public function test_column_definition_with_compound_field()
    {
        $result = (new Customer())->datatable(
            [
                'first_name',
                'last_name' => function ($column, $row) {
                    return $row->first_name . ' ' . $column;
                },
            ]
        );

        $this->assertIsObject($result);
    }

    /**
     * Test adding additional columns to DataTables.
     */
    public function test_add_additional_column()
    {
        $result = (new Customer())->datatable(
            [
                'first_name',
                'last_name',
                'this_is_an_additional_column' => function () {
                    return 'additional column #1';
                },
                function () {
                    return 'additional column #2';
                },
            ]
        );

        $this->assertIsObject($result);
    }

    /**
     * Test adding fields to the context for DataTables.
     */
    public function test_add_fields_in_context()
    {
        $result = (new Customer())->datatable(
            [
                'first_name' => function ($column, $row) {
                    return $column . ' ' . $row->last_name;
                },
            ],
            ['last_name']
        );

        $this->assertIsObject($result);
    }

    /**
     * Test DataTables column definition using a callback.
     */
    public function test_column_definition_callback()
    {
        $result = (new Customer())->datatable(function () {
            return ['first_name', 'last_name', 'phone'];
        });

        $this->assertIsObject($result);
    }

    /**
     * Test DataTables initialization using a static call.
     */
    public function test_static_call()
    {
        $result = Customer::datatable(function () {
            return ['first_name', 'last_name', 'phone'];
        });

        $this->assertIsObject($result);
    }

    /**
     * Test DataTables with union query.
     */
    public function test_use_union()
    {
        $result = Customer::join('business', 'business.id_customer', '=', 'customers.id')
            ->datatable(function () {
                return ['customers.first_name', 'customers.last_name', 'business.name'];
            });

        $this->assertIsObject($result);
    }

    /**
     * Test DataTables with a union query and aliases using a database query.
     */
    public function test_using_union_with_aliases()
    {
        $result = Customer::join('business', 'business.id_customer', '=', 'customers.id')
            ->datatable(
                [
                    'customers.first_name AS f_name',
                    'customers.last_name AS l_name',
                    'business.name AS aaa',
                ]
                , ['customers.phone AS contact' => ['orderable' => false, 'searchable' => true]]
            );

        $this->assertIsObject($result);
    }

    /**
     * Test DataTables with relations.
     *
     * Note: Using relations has limitations and requires careful handling.
     * - Do not use a relation field as a column identifier.
     * - To use the value of a relation field, you must use a formatting function.
     * - Relation fields are not sortable or searchable.
     *
     * Note: You must add the localKey field used in the relation to the column definition
     * or fields in the context.
     */
    public function test_use_relations()
    {
        $result = Customer::with('business')
            ->datatable(
                [
                    'first_name',
                    'last_name',
                    function ($field, $row) {
                        return $row->business->name;
                    },
                ],
                ['id']// 'id' is the localKey field specified in the relation with 'business'
            );

        $this->assertIsObject($result);
    }
}
