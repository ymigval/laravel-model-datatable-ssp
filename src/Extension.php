<?php

namespace Ymigval\LaravelModelToDatatables;

use Closure;

class Extension
{
    /**
     * Initialize DataTables transformation.
     *
     * @param  Builder     $builder
     * @param  array|Closure $columnDefs
     * @param  array       $fieldsInContext
     * @param  string|null $output
     * @return \Illuminate\Http\JsonResponse|array|string
     */
    public static function init($builder, $columnDefs, array $fieldsInContext,  ? string $output = null)
    {
        $dataTables = new DataTables($builder, $columnDefs, $fieldsInContext);
        return $dataTables->transform($output);
    }

    /**
     * Callable function for initializing DataTables transformation.
     *
     * @return Closure
     */
    public function __invoke()
    {
        return function ($columnDefs, array $fieldsInContext = [],  ? string $output = null) {
            return Extension::init(
                $this,
                $columnDefs,
                $fieldsInContext,
                $output
            );
        };
    }
}
