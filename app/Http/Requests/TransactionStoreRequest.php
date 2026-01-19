<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionStoreRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->can('transaction.create');
    }

    public function rules()
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|string|in:CASH,QRIS',
            'paid_amount' => 'required_if:payment_method,CASH|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ];
    }
}
