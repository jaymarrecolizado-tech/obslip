<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $query = Vehicle::latest();

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $paginator = $query->paginate($perPage);

        return $this->paginatedResponse(VehicleResource::collection($paginator));
    }

    public function store(VehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return $this->successResponse(
            new VehicleResource($vehicle),
            'Vehicle created.',
            201
        );
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return $this->successResponse(new VehicleResource($vehicle));
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle->update($request->validated());

        return $this->successResponse(
            new VehicleResource($vehicle),
            'Vehicle updated.'
        );
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return $this->successResponse(null, 'Vehicle deleted.');
    }
}
