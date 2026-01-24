<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscoveryReviewRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'workshop_id' => 'required|exists:workshops,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ];
    }
}
