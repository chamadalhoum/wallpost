<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Morehours extends Model
{
    use HasFactory;
    protected $fillable=[
        'morehoursId',
        'displayName',
        'localized',
        'user_id',
        'openDay',
        'openTime',
        'closeDay',
        'categorie_id',
        'type',
        'closeTime',
        'fiche_id',
        'state'
    ];
    use  LogsActivity;
    protected static $logName = 'Morehours';
    protected static $logAttributes = ['id',
        'morehoursId',
        'displayName',
        'localized',
        'user_id',
        'openDay',
        'openTime',
        'closeDay',
        'closeTime',
        'categorie_id',
        'type',
        'state',
        'created_at',
        'fiche_id',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Morehours";
    }
}
