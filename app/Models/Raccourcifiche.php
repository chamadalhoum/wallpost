<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Raccourcifiche extends Model
{
   
    use HasFactory;

    protected $table = 'raccourcifiches';
    protected $primaryKey = 'id';
    protected $fillable = [
        'raccourci_id',
        'fiche_id',
        'created_at',
        'updated_at',
    ];
    protected static $logName = 'raccourcifiches';
    protected static $logAttributes = ['id',
    'raccourci_id',
    'fiche_id',
  
    'created_at',
    'updated_at',
 ];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Raccourcifiches";
    }

   
}
