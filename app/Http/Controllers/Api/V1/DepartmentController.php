<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDepartmentRequest;
use App\Http\Requests\Api\V1\UpdateDepartmentRequest;
use App\Http\Resources\Api\V1\DepartmentCollection;
use App\Http\Resources\Api\V1\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $query = Department::query()->with(['head', 'employeesCount']);

        if ($request->has('active_only')) {
            $query->when($request->boolean('active_only'), fn ($q) => $q->active());
        }

        $departments = $query->orderBy('name')
            ->paginate(min($request->get('per_page', 15), 100));

        return $this->successResponse(
            new DepartmentCollection($departments)
        );
    }

    public function show(Department $department): JsonResponse
    {
        $this->authorize('view', $department);

        $department->load(['head', 'employees', 'passSlips']);

        return $this->successResponse(
            new DepartmentResource($department)
        );
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        return $this->successResponse(
            new DepartmentResource($department),
            'Department created successfully.',
            201
        );
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $this->authorize('update', $department);

        $department->update($request->validated());

        return $this->successResponse(
            new DepartmentResource($department),
            'Department updated successfully.'
        );
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->authorize('delete', $department);

        $department->delete();

        return $this->successResponse(
            null,
            'Department deleted successfully.'
        );
    }

    public function active(): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::active()
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            DepartmentResource::collection($departments)
        );
    }
}