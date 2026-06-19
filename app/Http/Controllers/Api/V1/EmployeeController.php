<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEmployeeRequest;
use App\Http\Requests\Api\V1\UpdateEmployeeRequest;
use App\Http\Resources\Api\V1\EmployeeCollection;
use App\Http\Resources\Api\V1\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $query = Employee::query()->with(['department', 'user']);

        if ($request->has('department_id')) {
            $query->byDepartment($request->department_id);
        }
        if ($request->has('search')) {
            $query->search($request->search);
        }
        if ($request->has('status')) {
            $query->where('employment_status', $request->status);
        }

        $employees = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(min($request->get('per_page', 15), 100));

        return $this->successResponse(
            new EmployeeCollection($employees)
        );
    }

    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('view', $employee);

        $employee->load(['department', 'user', 'vehicles']);

        return $this->successResponse(
            new EmployeeResource($employee)
        );
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());

        return $this->successResponse(
            new EmployeeResource($employee),
            'Employee created successfully.',
            201
        );
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $employee->update($request->validated());

        return $this->successResponse(
            new EmployeeResource($employee),
            'Employee updated successfully.'
        );
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        $employee->delete();

        return $this->successResponse(
            null,
            'Employee deleted successfully.'
        );
    }

    public function active(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::active()
            ->with(['department'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return $this->successResponse(
            EmployeeResource::collection($employees)
        );
    }
}