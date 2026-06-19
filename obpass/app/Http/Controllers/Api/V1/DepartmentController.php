<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $query = Department::query()->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $paginator = $query->paginate($perPage);

        return $this->paginatedResponse(DepartmentResource::collection($paginator));
    }

    public function store(DepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        return $this->successResponse(
            new DepartmentResource($department),
            'Department created.',
            201
        );
    }

    public function show(Department $department): JsonResponse
    {
        return $this->successResponse(new DepartmentResource($department));
    }

    public function update(DepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());

        return $this->successResponse(
            new DepartmentResource($department),
            'Department updated.'
        );
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return $this->successResponse(null, 'Department deleted.');
    }
}
