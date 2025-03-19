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
     * @return \Illuminate\Http\JsonResponse List of orders.
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
     * @param \Illuminate\Http\Request $request The request containing order details.
     * @return \Illuminate\Http\JsonResponse The created order.
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
     * @param string $id The order ID.
     * @return \Illuminate\Http\JsonResponse The requested order details.
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
     * @param \Illuminate\Http\Request $request The request containing updated order details.
     * @param string $id The order ID.
     * @return \Illuminate\Http\JsonResponse The updated order.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return $this->sendError('Order not found.');
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'integer|min:1',
            'status' => 'string|in:pending,processed,shipped,delivered,canceled',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        if ($request->has('quantity')) {
            $product = Product::find($order->product_id);
            if ($product->stock_quantity + $order->quantity < $request->quantity) {
                return $this->sendError('Quantity Error', ['quantity' => 'Not enough stock available'], 422);
            }

            // Adjust stock quantity
            $product->increment('stock_quantity', $order->quantity);
            $product->decrement('stock_quantity', $request->quantity);

            $order->quantity = $request->quantity;
            $order->total_price = $request->quantity * $product->price;
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
     * @param string $id The order ID.
     * @return \Illuminate\Http\JsonResponse Success or error message.
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
     * @param \Illuminate\Http\Request $request The request containing the new status.
     * @param string $id The order ID.
     * @return \Illuminate\Http\JsonResponse The updated order status.
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
