<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Document extends Model
{  use  LogsActivity;
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable = [
        'type',
        'file',
        'fiche_id',
        'user_id',
    ];

    protected static $logName = 'Documents';
    protected static $logAttributes = ['id',
        'type',
        'file',
        'fiche_id',
        'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Documents";
    }
    public function Fiche(){
        return $this->belongsTo(Fiche::class);
    }
}
