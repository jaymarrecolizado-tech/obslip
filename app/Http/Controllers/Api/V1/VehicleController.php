<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreVehicleRequest;
use App\Http\Requests\Api\V1\UpdateVehicleRequest;
use App\Http\Resources\Api\V1\VehicleCollection;
use App\Http\Resources\Api\V1\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);

        $query = Vehicle::query()->with(['owner', 'passSlips']);

        if ($request->has('type')) {
            $query->when($request->type === 'company', fn ($q) => $q->companyVehicles());
            $query->when($request->type === 'personal', fn ($q) => $q->personalVehicles());
        }
        if ($request->has('owner_id')) {
            $query->byOwner($request->owner_id);
        }
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $vehicles = $query->orderBy('plate_number')
            ->paginate(min($request->get('per_page', 15), 100));

        return $this->successResponse(
            new VehicleCollection($vehicles)
        );
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);

        $vehicle->load(['owner.department', 'passSlips']);

        return $this->successResponse(
            new VehicleResource($vehicle)
        );
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return $this->successResponse(
            new VehicleResource($vehicle),
            'Vehicle created successfully.',
            201
        );
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);

        $vehicle->update($request->validated());

        return $this->successResponse(
            new VehicleResource($vehicle),
            'Vehicle updated successfully.'
        );
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return $this->successResponse(
            null,
            'Vehicle deleted successfully.'
        );
    }

    public function available(Request $request): JsonResponse
    {
        $query = Vehicle::active()
            ->companyVehicles()
            ->whereDoesntHave('passSlips', function ($query) use ($request) {
                $query->where('date', $request->date ?? today())
                    ->whereIn('status', ['approved', 'departed']);
            });

        $vehicles = $query->orderBy('plate_number')->get();

        return $this->successResponse(
            VehicleResource::collection($vehicles)
        );
    }
}