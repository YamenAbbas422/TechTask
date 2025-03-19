<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class CustomerController
 *
 * Manages customer CRUD operations within the authenticated user's tenant.
 *
 * @package App\Http\Controllers
 */
class CustomerController extends Controller
{
    use MessageTrait;

    /**
     * Retrieve all customers for the authenticated user's tenant.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse List of customers.
     *
     * @example
     * GET /api/customers
     *
     * @response 200 {
     *    "success": true,
     *    "data": [
     *        {
     *            "id": 1,
     *            "name": "Customer 1",
     *            "email": "customer1@example.com",
     *            "phone": "123456789",
     *            "tenant_id": 2
     *        }
     *    ],
     *    "message": "Customers retrieved successfully."
     * }
     */
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $customers = Customer::where('tenant_id', $tenantId)->get();
        return $this->sendResponse(CustomerResource::collection($customers), 'Customers retrieved successfully.');
    }

    /**
     * Store a new customer.
     *
     * @authenticated
     *
     * @param \Illuminate\Http\Request $request The request containing customer details.
     * @return \Illuminate\Http\JsonResponse The created customer.
     *
     * @example
     * POST /api/customers
     * {
     *    "name": "Customer 2",
     *    "email": "customer2@example.com",
     *    "phone": "987654321"
     * }
     *
     * @response 201 {
     *    "success": true,
     *    "data": {
     *        "id": 2,
     *        "name": "Customer 2",
     *        "email": "customer2@example.com",
     *        "phone": "987654321",
     *        "tenant_id": 2
     *    },
     *    "message": "Customer created successfully."
     * }
     *
     * @response 422 {
     *    "success": false,
     *    "message": "Validation Error",
     *    "errors": {
     *        "email": ["The email has already been taken."]
     *    }
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|string|email|max:255|unique:customers',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $input = $request->merge([
            'tenant_id' => Auth::user()->tenant_id,
        ])->all();

        $customer = Customer::create($input);
        return $this->sendResponse(new CustomerResource($customer), 'Customer created successfully.');
    }

    /**
     * Retrieve a specific customer by ID.
     *
     * @authenticated
     *
     * @param int $id The customer ID.
     * @return \Illuminate\Http\JsonResponse The requested customer details.
     *
     * @example
     * GET /api/customers/1
     *
     * @response 200 {
     *    "success": true,
     *    "data": {
     *        "id": 1,
     *        "name": "Customer 2",
     *        "email": "customer2@example.com",
     *        "phone": "123456789",
     *        "tenant_id": 2
     *    },
     *    "message": "Customer retrieved successfully."
     * }
     *
     * @response 404 {
     *    "success": false,
     *    "message": "Customer not found."
     * }
     */
    public function show(string $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return $this->sendError('Customer not found.');
        }
        return $this->sendResponse(new CustomerResource($customer), 'Customer retrieved successfully.');
    }

    /**
     * Update an existing customer's details.
     *
     * @authenticated
     *
     * @param \Illuminate\Http\Request $request The request containing updated customer details.
     * @param int $id The customer ID.
     * @return \Illuminate\Http\JsonResponse The updated customer.
     *
     * @example
     * PUT /api/customers/1
     * {
     *    "name": "Customer 1 Updated",
     *    "email": "customer1updated@example.com",
     *    "phone": "111222333"
     * }
     *
     * @response 200 {
     *    "success": true,
     *    "data": {
     *        "id": 1,
     *    "name": "Customer 1 Updated",
     *    "email": "customer1updated@example.com",
     *        "phone": "111222333",
     *        "tenant_id": 2
     *    },
     *    "message": "Customer updated successfully."
     * }
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return $this->sendError('Customer not found.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|string|email|max:255|unique:customers',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $customer->name = $request->name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->save();

        return $this->sendResponse(new CustomerResource($customer), 'Customer retrieved successfully.');
    }

    /**
     * Delete a customer.
     *
     * @authenticated
     *
     * @param int $id The customer ID.
     * @return \Illuminate\Http\JsonResponse Success or error message.
     *
     * @example
     * DELETE /api/customers/1
     *
     * @response 200 {
     *    "success": true,
     *    "message": "Customer deleted successfully."
     * }
     *
     * @response 404 {
     *    "success": false,
     *    "message": "Customer not found."
     * }
     */
    public function destroy(string $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return $this->sendError('Customer not found.');
        }
        $customer->delete();
        return $this->sendResponse([], 'Customer deleted successfully.');
    }
}
