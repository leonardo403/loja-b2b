<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function stats(): JsonResponse
    {
        return $this->item($this->dashboard->stats());
    }
}
