<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackingUpdateLocationRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->hasRole(['mechanic_mobile', 'mechanic_in_shop', 'owner']);
    }

    public function rules()
    {
        return [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ];
    }
}
