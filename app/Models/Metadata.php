<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Notifications\Notifiable;

class Metadata extends Model
{
        use HasFactory, LogsActivity,Notifiable;
     protected $table = 'metadatas';
     protected $primaryKey = 'id';
       
    protected $fillable=[
        'metadatasId',
        'locationName',
        'placeId',
        'access',
        'type',
        'mapsUrl',
        'newReviewUrl',
        'fiche_id'
    ];
    use  LogsActivity;
    protected static $logName = 'Metadatas';
    protected static $logAttributes = ['id',
        'metadatasId',
        'locationName',
        'placeId',
        'access',
        'type',
        'mapsUrl',
        'newReviewUrl',
        'fiche_id'
        ,'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} metadatas";
    }
}
