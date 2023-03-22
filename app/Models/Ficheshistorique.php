<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ficheshistorique extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $fillable = [
        'fiche_id',
        'logo',
        'description',
        'locationName',
        'state',
        'name',
        'url_map',
        'storeCode',
        'closedatestrCode',
        'primaryPhone',
        'adwPhone',
        'labels',
        'additionalPhones',
        'websiteUrl',
        'etatwebsite',
        'email',
        'latitude',
        'longitude',
        'OpenInfo_status',
        'address',
        'city',
        'placeId',
        'country',
        'postalCode',
        'OpenInfo_opening_date',
        'OpenInfo_canreopen',
        'otheradress',
        'franchises_id',
        'methodverif',
        'etat'
    ];
}
