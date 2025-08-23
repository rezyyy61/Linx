<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\Value\ProfileValueReorderRequest;
use App\Http\Requests\Profile\Value\ProfileValueStoreRequest;
use App\Http\Requests\Profile\Value\ProfileValueUpdateRequest;
use App\Services\Profile\ProfileValueService;
use Illuminate\Http\Request;

class ValueController extends Controller
{
    public function index(Request $request, ProfileValueService $service)
    {
        $values = $service->list($request->user());

        return response()->json(['ok' => true, 'data' => ['values' => $values]]);
    }

    public function store(ProfileValueStoreRequest $request, ProfileValueService $service)
    {
        $value = $service->create($request->user(), $request->validated());

        return response()->json(['ok' => true, 'data' => ['value' => $value]], 201);
    }

    public function update(ProfileValueUpdateRequest $request, ProfileValueService $service, int $id)
    {
        $value = $service->update($request->user(), $id, $request->validated());

        return response()->json(['ok' => true, 'data' => ['value' => $value]]);
    }

    public function destroy(Request $request, ProfileValueService $service, int $id)
    {
        $service->delete($request->user(), $id);

        return response()->json(['ok' => true]);
    }

    public function reorder(ProfileValueReorderRequest $request, ProfileValueService $service)
    {
        $values = $service->reorder($request->user(), $request->validated('ids'));

        return response()->json(['ok' => true, 'data' => ['values' => $values]]);
    }
}
