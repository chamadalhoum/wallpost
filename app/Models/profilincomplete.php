<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class profilincomplete extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $casts = [
        'statestorelocatore' => 'json',
    ];
    protected $fillable = [
        'storeCode',
        'description',
        'labels',
        'primaryPhone',
        'websiteUrl',
        'adwPhone',
        'locationName',
        'regularHours',
        'serviceArea',
        'specialHours',
        'address',
        'attributesUrl',
        'Service',
        'attributes',
        'moreHours',
        'total',
        'totalfiche',
        'Photo',
        'Post',
        'title',
        'states',
        'etat',
        'fiche_id',
        'statestorelocatore',
        'logostorelocatore',
        'statuspost',
        'nombrejour',
        'vuemaps',
        'vuesearch',
        'TotalAvis',
        'TotalRate',
        'notification',
        'etiquette'
    ];
}
