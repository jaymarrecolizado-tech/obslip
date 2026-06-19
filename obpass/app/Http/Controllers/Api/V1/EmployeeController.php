<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $query = Employee::with('department')->latest();

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $paginator = $query->paginate($perPage);

        return $this->paginatedResponse(EmployeeResource::collection($paginator));
    }

    public function store(EmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());
        $employee->load('department');

        return $this->successResponse(
            new EmployeeResource($employee),
            'Employee created.',
            201
        );
    }

    public function show(Employee $employee): JsonResponse
    {
        $employee->load('department');

        return $this->successResponse(new EmployeeResource($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->update($request->validated());
        $employee->load('department');

        return $this->successResponse(
            new EmployeeResource($employee),
            'Employee updated.'
        );
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return $this->successResponse(null, 'Employee deleted.');
    }
}
