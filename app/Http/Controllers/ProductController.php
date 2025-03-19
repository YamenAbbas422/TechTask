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
 * Manages CRUD operations for products within the authenticated user's tenant.
 *
 * @package App\Http\Controllers
 */
class ProductController extends Controller
{
    use MessageTrait;

    /**
     * Retrieve all products for the authenticated user's tenant.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse List of products.
     *
     * @example
     * GET /api/products
     *
     * @response 200 {
     *    "success": true,
     *    "data": [
     *        {
     *            "id": 1,
     *            "name": "Product A",
     *            "description": "Description of product A",
     *            "price": 99.99,
     *            "stock_quantity": 10,
     *            "tenant_id": 2
     *        }
     *    ],
     *    "message": "Products retrieved successfully."
     * }
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
     * @authenticated
     *
     * @param \Illuminate\Http\Request $request The request containing product details.
     * @return \Illuminate\Http\JsonResponse The created product.
     *
     * @example
     * POST /api/products
     * {
     *    "name": "New Product",
     *    "description": "This is a sample product",
     *    "price": 199.99,
     *    "stock_quantity": 15
     * }
     *
     * @response 201 {
     *    "success": true,
     *    "data": {
     *        "id": 3,
     *        "name": "New Product",
     *        "description": "This is a sample product",
     *        "price": 199.99,
     *        "stock_quantity": 15,
     *        "tenant_id": 2
     *    },
     *    "message": "Product created successfully."
     * }
     *
     * @response 422 {
     *    "success": false,
     *    "message": "Validation Error",
     *    "errors": {
     *        "name": ["The name field is required."]
     *    }
     * }
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
     * @authenticated
     *
     * @param int $id The product ID.
     * @return \Illuminate\Http\JsonResponse The requested product details.
     *
     * @example
     * GET /api/products/1
     *
     * @response 200 {
     *    "success": true,
     *    "data": {
     *        "id": 1,
     *        "name": "Product A",
     *        "description": "Description of product A",
     *        "price": 99.99,
     *        "stock_quantity": 10,
     *        "tenant_id": 2
     *    },
     *    "message": "Product retrieved successfully."
     * }
     *
     * @response 404 {
     *    "success": false,
     *    "message": "Product not found."
     * }
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
     * @authenticated
     *
     * @param \Illuminate\Http\Request $request The request containing updated product details.
     * @param int $id The product ID.
     * @return \Illuminate\Http\JsonResponse The updated product.
     *
     * @example
     * PUT /api/products/1
     * {
     *    "name": "Updated Product",
     *    "description": "Updated description",
     *    "price": 149.99,
     *    "stock_quantity": 20
     * }
     *
     * @response 200 {
     *    "success": true,
     *    "data": {
     *        "id": 1,
     *        "name": "Updated Product",
     *        "description": "Updated description",
     *        "price": 149.99,
     *        "stock_quantity": 20,
     *        "tenant_id": 2
     *    },
     *    "message": "Product updated successfully."
     * }
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
     * @authenticated
     *
     * @param int $id The product ID.
     * @return \Illuminate\Http\JsonResponse Success or error message.
     *
     * @example
     * DELETE /api/products/1
     *
     * @response 200 {
     *    "success": true,
     *    "message": "Product deleted successfully."
     * }
     *
     * @response 404 {
     *    "success": false,
     *    "message": "Product not found."
     * }
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
