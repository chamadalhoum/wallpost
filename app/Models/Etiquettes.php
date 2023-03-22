<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Etiquettes extends Model
{
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable = [

        'name',
        'state'


    ];
    use  LogsActivity;
    protected static $logName = 'Etiquettes';
    protected static $logAttributes = ['id',

        'name',
        'state'
        ,'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Etiquettes";
    }
    public function etiquetgroupes(){
        return $this->hasMany(Etiquetgroupe::class);
    }

}
