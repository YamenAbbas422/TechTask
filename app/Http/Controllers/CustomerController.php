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
 * Handles CRUD operations for customers.
 *
 * @package App\Http\Controllers
 */
class CustomerController extends Controller
{
    use MessageTrait;

    /**
     * Retrieve all customers belonging to the authenticated user's tenant.
     *
     * @return \Illuminate\Http\JsonResponse List of customers.
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
     * @param \Illuminate\Http\Request $request The request containing customer details.
     * @return \Illuminate\Http\JsonResponse The created customer.
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
     * @param string $id The customer ID.
     * @return \Illuminate\Http\JsonResponse The requested customer details.
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
     * @param \Illuminate\Http\Request $request The request containing updated customer details.
     * @param string $id The customer ID.
     * @return \Illuminate\Http\JsonResponse The updated customer.
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
     * @param string $id The customer ID.
     * @return \Illuminate\Http\JsonResponse Success or error message.
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
