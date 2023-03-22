<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicearea extends Model
{
    use HasFactory;
     protected $fillable = [
        'businessType',
        'name',
        'placeId',
        'radiusKm',
        'latitude',
        'longitude',
         'pays',
         'zone',
        'fiche_id',
        "state"
    ];
}
