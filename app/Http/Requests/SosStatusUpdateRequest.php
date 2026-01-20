<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SosStatusUpdateRequest extends FormRequest
{
    public function authorize()
    {
        // Only mechanics who accepted the job can update status
        return $this->user() && $this->user()->can('sos.accept');
    }

    public function rules()
    {
        return [
            'status' => 'required|in:ON_THE_WAY,ARRIVED,PROCESSING,DONE,CANCELLED',
        ];
    }
}
