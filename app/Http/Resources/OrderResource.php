<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->product ? [
                'product_name' => $this->product->name,
                'description' => $this->product->description,
                'price' => $this->product->price,
                'quantity' => $this->product->stock_quantity,
            ] : Null,
            'customer' => $this->customer ? [
                'customer_name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone
            ] : Null,
            'quantity' => $this->quantity,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'tenant' => $this->tenant ? [
                'tenant_name' => $this->tenant->name,
            ] : Null,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}
