<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Franchise extends Model
{
    use LogsActivity;
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable =[
        'socialReason',
        'state',
        'logo',
        'type',
        'name',
        'taxRegistration',
        'statutFiscale',
        'tradeRegistry',
        'cinGerant',
        'fax',
        'phone',
        'email',
        'address',
        'postalCode',
        'city',
        'country',
    ];
    protected static $logName = 'franchise';
    protected static $logAttributes = [
        'id',
        'socialReason',
        'state',
        'logo',
        'type',
        'name',
        'taxRegistration',
        'statutFiscale',
        'tradeRegistry',
        'cinGerant',
        'fax',
        'phone',
        'email',
        'address',
        'postalCode',
        'city',
        'country',
    ];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} franchise";
    }
    public function fiche()
    {
        return $this->hasMany(Fiche::class);
    }

}
