<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Serviceshistorique extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $fillable = [
        'serviceId',
        'displayName',
        'user_id',
        'description',
        'prix',
        'typeservice',
        'categorie_id',
        'service_id',
        'state'
    ];
    use  LogsActivity;

    protected static $logName = 'Serviceshistorique';
    protected static $logAttributes = ['id', 'serviceId',
        'displayName',
        'user_id','description','prix', 'typeservice','service_id',
        'state',
        'categorie_id', 'created_at', 'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Serviceshistorique";
    }

}
