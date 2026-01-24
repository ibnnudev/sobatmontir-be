<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscoveryReviewUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'workshop_id' => 'required|exists:workshops,id',
            'image_url' => 'required|url',
            'caption' => 'nullable|string',
        ];
    }
}
