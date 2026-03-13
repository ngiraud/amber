<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Activity\ScanActivitySources;
use App\Http\Requests\Activity\SyncActivityRequest;
use Illuminate\Http\JsonResponse;

class SyncActivityController extends Controller
{
    public function __invoke(SyncActivityRequest $request, ScanActivitySources $action): JsonResponse
    {
        $count = $action->handle(
            $request->getSince(),
            $request->getUntil(),
            collect([$request->getSourceType()]),
        )->count();

        return response()->json(['count' => $count]);
    }
}
