<?php

namespace Ymigval\LaravelModelToDatatables\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Ymigval\LaravelModelToDatatables\Tests\TestCase;

class DataTablesWithQueryTest extends TestCase
{
    /**
     * Test DataTables output as a response object using a database query.
     */
    public function test_output_response()
    {
        $result = DB::table('customers')->datatable(['first_name', 'last_name', 'phone']);

        $this->assertIsObject($result);
    }

    /**
     * Test DataTables output as an array using a database query.
     */
    public function test_output_array()
    {
        $result = DB::table('customers')->datatable(['first_name', 'last_name', 'phone'], [], 'array');

        $this->assertIsArray($result);
    }

    /**
     * Test DataTables output as JSON using a database query.
     */
    public function test_output_json()
    {
        $result = DB::table('customers')->datatable(['first_name', 'last_name', 'phone'], [], 'json');

        $this->assertIsString($result);
    }

    /**
     * Test formatting a column in DataTables using a database query.
     */
    public function test_format_column()
    {
        $result = DB::table('customers')->datatable(
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
     * Test column definition with a compound field using a database query.
     */
    public function test_column_definition_with_compound_field()
    {
        $result = DB::table('customers')->datatable(
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
     * Test adding additional columns to DataTables using a database query.
     */
    public function test_add_additional_column()
    {
        $result = DB::table('customers')->datatable(
            [
                'first_name',
                'last_name',
                function () {
                    return 'additional column #2';
                },
            ]
        );

        $this->assertIsObject($result);
    }

    /**
     * Test adding fields to the context for DataTables using a database query.
     */
    public function test_add_fields_in_context()
    {
        $result = DB::table('customers')->datatable(
            [
                'first_name' => function ($column, $row) {
                    return $column . ' ' . $row->last_name;
                },
            ], ['last_name']
        );

        $this->assertIsObject($result);
    }

    /**
     * Test DataTables column definition using a callback with a database query.
     */
    public function test_column_definition_callback()
    {
        $result = DB::table('customers')->datatable(function () {
            return ['first_name', 'last_name', 'phone'];
        });

        $this->assertIsObject($result);
    }

    /**
     * Test DataTables with a union query using a database query.
     */
    public function test_use_union()
    {
        $result = DB::table('customers')->join('business', 'business.id_customer', '=', 'customers.id')
            ->datatable(function () {
                return ['customers.first_name', 'customers.last_name', 'business.name'];
            });

        $this->assertIsObject($result);
    }
}