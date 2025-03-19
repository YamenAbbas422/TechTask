<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\Message;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class ProductController
 *
 * Handles CRUD operations for products.
 *
 * @package App\Http\Controllers
 */
class ProductController extends Controller
{
    use MessageTrait;

    /**
     * Retrieve all products belonging to the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse List of products.
     */
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = Product::where('tenant_id', $tenantId)->get();
        return $this->sendResponse(ProductResource::collection($products), 'Products retrieved successfully.');
    }

    /**
     * Store a new product.
     *
     * @param \Illuminate\Http\Request $request The request containing product details.
     * @return \Illuminate\Http\JsonResponse The created product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'stock_quantity' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $input = $request->all();
        $input['tenant_id'] = Auth::user()->tenant_id;

        $product = Product::create($input);
        return $this->sendResponse(new ProductResource($product), 'Product created successfully.');
    }

    /**
     * Retrieve a specific product by ID.
     *
     * @param string $id The product ID.
     * @return \Illuminate\Http\JsonResponse The requested product details.
     */
    public function show(string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return $this->sendError('Product not found.', [], 404);
        }
        return $this->sendResponse(new ProductResource($product), 'Product retrieved successfully.');
    }

    /**
     * Update an existing product.
     *
     * @param \Illuminate\Http\Request $request The request containing updated product details.
     * @param string $id The product ID.
     * @return \Illuminate\Http\JsonResponse The updated product.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return $this->sendError('Product not found.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'stock_quantity' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock_quantity = $request->stock_quantity;
        $product->save();

        return $this->sendResponse(new ProductResource($product), 'Product updated successfully.');
    }

    /**
     * Delete a product.
     *
     * @param string $id The product ID.
     * @return \Illuminate\Http\JsonResponse Success or error message.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return $this->sendError('Product not found.');
        }
        $product->delete();
        return $this->sendResponse([], 'Product deleted successfully.');
    }
}
