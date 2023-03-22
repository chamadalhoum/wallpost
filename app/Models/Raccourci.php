<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Raccourci extends Model
{
    use HasFactory;
    protected $table = 'raccourcis';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'icon',
        'color',
      
        'created_at',
        'updated_at',
    ];
    protected static $logName = 'raccourcis';
    protected static $logAttributes = ['id',
    'name',
    'icon',
    'color',
    'created_at',
    'updated_at',
 ];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Raccourcis";
    }

}
