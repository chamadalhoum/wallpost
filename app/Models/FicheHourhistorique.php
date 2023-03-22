<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class FicheHourhistorique extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'id';
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
        'fichehours_id',
      
    ];
    protected static $logName = 'Horairehistorique';
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
    'user_id',
    'fichehours_id',
     'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Horairehistorique";
    }

    public function fichehours()
    {
        return $this->belongsTo(Fichehour::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
