<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SosRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->can('sos.create');
    }

    public function rules()
    {
        return [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'problem' => 'required|string',
        ];
    }
}
