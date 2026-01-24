<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueueBookRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() !== null;
    }

    public function rules()
    {
        return [
            'workshop_id' => 'required|exists:workshops,id',
        ];
    }
}
