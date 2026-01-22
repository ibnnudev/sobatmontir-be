<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueueServeRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->can('queue.call');
    }

    public function rules()
    {
        return [
            'ticket_code' => 'required',
        ];
    }
}
