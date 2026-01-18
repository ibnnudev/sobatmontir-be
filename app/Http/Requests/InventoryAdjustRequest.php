<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryAdjustRequest extends FormRequest
{
    public function authorize()
    {
        // Authorization handled in controller (Gate)
        return true;
    }

    public function rules()
    {
        return [
            'product_id' => 'required|exists:products,id',
            'real_qty' => 'required|integer|min:0',
            'reason' => 'required|string|min:5',
        ];
    }
}
