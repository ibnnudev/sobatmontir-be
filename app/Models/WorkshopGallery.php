<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopGallery extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'workshop_id',
        'image_url',
        'caption',
    ];

    public function workshop()
    {
        return $this->belongsTo(Workshop::class, 'workshop_id');
    }
}
