<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class OrderController
 *
 * Handles CRUD operations for orders.
 *
 * @package App\Http\Controllers
 */
class OrderController extends Controller
{
    use MessageTrait;
    /**
     * Retrieve all orders belonging to the authenticated user's tenant.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse List of orders.
     *
     * @example
     * GET /api/orders
     *
     * @response 200 {
     *    "success": true,
     *    "data": [
     *        {
     *            "id": 1,
     *            "product_id": 5,
     *            "customer_id": 3,
     *            "quantity": 2,
     *            "total_price": 199.98,
     *            "status": "pending",
     *            "tenant_id": 2
     *        }
     *    ],
     *    "message": "Orders retrieved successfully."
     * }
     */
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $orders = Order::where('tenant_id', $tenantId)->get();
        return $this->sendResponse(OrderResource::collection($orders), 'Orders retrieved successfully.');
    }

    /**
     * Store a new order.
     *
     * @authenticated
     *
     * @param \Illuminate\Http\Request $request The request containing order details.
     * @return \Illuminate\Http\JsonResponse The created order.
     *
     * @example
     * POST /api/orders
     * {
     *    "product_id": 5,
     *    "customer_id": 3,
     *    "quantity": 2
     * }
     *
     * @response 201 {
     *    "success": true,
     *    "data": {
     *        "id": 2,
     *        "product_id": 5,
     *        "customer_id": 3,
     *        "quantity": 2,
     *        "total_price": 199.98,
     *        "status": "pending",
     *        "tenant_id": 2
     *    },
     *    "message": "Order created successfully."
     * }
     *
     * @response 422 {
     *    "success": false,
     *    "message": "Validation Error",
     *    "errors": {
     *        "product_id": ["The selected product_id is invalid."]
     *    }
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'required|exists:customers,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $product = Product::find($request->product_id);

        if ($product->stock_quantity < $request->quantity) {
            return $this->sendError('Quantity Error', ['quantity' => 'Not enough stock available'], 422);
        }

        $totalPrice = $product->price * $request->quantity;
        $order = Order::create([
            'product_id' => $request->product_id,
            'customer_id' => $request->customer_id,
            'quantity' => $request->quantity,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'tenant_id' => Auth::user()->tenant_id
        ]);
        $product->decrement('stock_quantity', $request->quantity);
        return $this->sendResponse(new OrderResource($order), 'Orders created successfully.');
    }

    /**
     * Retrieve a specific order by ID.
     *
     * @authenticated
     *
     * @param int $id The order ID.
     * @return \Illuminate\Http\JsonResponse The requested order details.
     *
     * @example
     * GET /api/orders/1
     *
     * @response 200 {
     *    "success": true,
     *    "data": {
     *        "id": 1,
     *        "product_id": 5,
     *        "customer_id": 3,
     *        "quantity": 2,
     *        "total_price": 199.98,
     *        "status": "pending",
     *        "tenant_id": 2
     *    },
     *    "message": "Order retrieved successfully."
     * }
     */
    public function show(string $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return $this->sendError('Order not found.');
        }
        return $this->sendResponse(new OrderResource($order), 'Order retrieved successfully.');
    }

    /**
     * Update an existing order.
     *
     * @authenticated
     *
     * @param \Illuminate\Http\Request $request The request containing updated order details.
     * @param int $id The order ID.
     * @return \Illuminate\Http\JsonResponse The updated order.
     *
     * @example
     * PUT /api/orders/1
     * {
     *    "quantity": 5,
     *    "status": "processed"
     * }
     *
     * @response 200 {
     *    "success": true,
     *    "data": {
     *        "id": 1,
     *        "product_id": 5,
     *        "customer_id": 3,
     *        "quantity": 5,
     *        "total_price": 499.95,
     *        "status": "processed",
     *        "tenant_id": 2
     *    },
     *    "message": "Order updated successfully."
     * }
     *
     * @response 403 {
     *    "success": false,
     *    "message": "Order cannot be updated once it has been shipped or delivered."
     * }
     *
     * @response 404 {
     *    "success": false,
     *    "message": "Order not found."
     * }
     *
     * @response 422 {
     *    "success": false,
     *    "message": "Validation Error",
     *    "errors": {
     *        "quantity": ["The quantity must be at least 1."]
     *    }
     * }
     */
    public function update(Request $request, string $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return $this->sendError('Order not found.');
        }

        if (in_array($order->status, ['shipped', 'delivered'])) {
            return $this->sendError('Order cannot be updated once it has been shipped or delivered.', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'integer|min:1',
            'status' => 'string|in:pending,processed,shipped,delivered,canceled',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $product = Product::find($order->product_id);
        if (!$product) {
            return $this->sendError('Product associated with this order not found.', [], 404);
        }

        if ($request->has('quantity')) {
            $newQuantity = $request->quantity;
            $oldQuantity = $order->quantity;
            $quantityDifference = $newQuantity - $oldQuantity;
            if ($quantityDifference > 0 && $product->stock_quantity < $quantityDifference) {
                return $this->sendError('Quantity Error', ['quantity' => 'Not enough stock available'], 422);
            }
            if ($quantityDifference > 0) {
                $product->decrement('stock_quantity', $quantityDifference);
            } elseif ($quantityDifference < 0) {
                $product->increment('stock_quantity', abs($quantityDifference));
            }

            $order->quantity = $newQuantity;
            $order->total_price = $newQuantity * $product->price;
        }

        if ($request->has('status')) {
            $order->status = $request->status;
        }

        $order->save();

        return $this->sendResponse(new OrderResource($order), 'Order updated successfully.');
    }

    /**
     * Delete an order.
     *
     * @authenticated
     *
     * @param int $id The order ID.
     * @return \Illuminate\Http\JsonResponse Success or error message.
     *
     * @example
     * DELETE /api/orders/1
     *
     * @response 200 {
     *    "success": true,
     *    "message": "Order deleted successfully."
     * }
     *
     * @response 404 {
     *    "success": false,
     *    "message": "Order not found."
     * }
     */
    public function destroy(string $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return $this->sendError('Order not found.');
        }
        $order->delete();
        return $this->sendResponse([], 'Order deleted successfully.');
    }

    /**
     * Update the status of an order.
     *
     * @authenticated
     *
     * @param \Illuminate\Http\Request $request The request containing the new status.
     * @param int $id The order ID.
     * @return \Illuminate\Http\JsonResponse The updated order status.
     *
     * @example
     * PUT /api/orders/1/status
     * {
     *    "status": "shipped"
     * }
     *
     * @response 200 {
     *    "success": true,
     *    "data": {
     *        "id": 1,
     *        "status": "shipped"
     *    },
     *    "message": "Order status updated successfully."
     * }
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processed,shipped,delivered,canceled',
        ]);

        $order = Order::where('id', $id)->where('tenant_id', Auth::user()->tenant_id)->first();
        if (!$order) {
            return $this->sendError('Order not found.');
        }
        $order->update(['status' => $request->status]);

        return $this->sendResponse(new OrderResource($order), 'Order status updated successfully.');
    }
}
