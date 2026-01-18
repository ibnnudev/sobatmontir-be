<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required_if:is_service,false|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'is_service' => 'boolean',
        ];
    }
}
