<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Groupe extends Model
{
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable =[
        'name',
        'color',
        'state'
    ];
    use  LogsActivity;
    protected static $logName = 'Groupe';
    protected static $logAttributes = ['id',
        'name',
        'color',
        'state'
        ,'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Groupe";
    }
    public function etiquetgroupes(){
        return $this->hasMany(Etiquetgroupe::class);
    }
}
