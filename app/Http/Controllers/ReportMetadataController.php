<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Reports\DatabaseSchemaService;

class ReportMetadataController extends Controller
{
    public function __invoke(
        DatabaseSchemaService $schema
    ) {
        return response()->json(
            $schema->modules()
        );
    }
}
