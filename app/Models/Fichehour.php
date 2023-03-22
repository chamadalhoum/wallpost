<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Fichehour extends Model
{
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable = [

        'type',
        'open_date',
        'close_date',
        'open_time',
        'close_time',
        'specialhours_start_date',
        'specialhours_end_date',
        'specialhours_open_time',
        'specialhours_close_time',
        'new_content',
        'fiche_id',
        'user_id',
        "state"
    ];
    use  LogsActivity;
    protected static $logName = 'Fichehour';
    protected static $logAttributes = ['id',
        'type',
        'open_date',
        'close_date',
        'open_time',
        'close_time',
        'specialhours_start_date',
        'specialhours_end_date',
        'specialhours_open_time',
        'specialhours_close_time',
        'new_content',
        'fiche_id',
        'user_id'
        ,'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Fichehour";
    }

    public function fiche(){
        return $this->belongsTo(Fiche::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function fichehourhistorique(){
        return $this->hasMany(Fichehour::class);
    }
}
