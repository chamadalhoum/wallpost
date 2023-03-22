<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Avi extends Model
{
    use HasFactory;

    protected $fillable = [

        'code',
        'title',
        'content',
        'rating',
        'photo',
        'date',
        'fiche_id',
    ];
    use  LogsActivity;
    protected static $logName = 'Avis';
    protected static $logAttributes = ['id',
        'code',
        'title',
        'content',
        'rating',
        'photo',
        'date',
        'fiche_id',
        'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Avis";
    }
    public function fiche()
    {
        return $this->belongsTo(Fiche::class);
    }
}
