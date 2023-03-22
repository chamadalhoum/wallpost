<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Pay extends Model
{
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable = [

        'code',
        'alpha2',
        'pays',


    ];
    use  LogsActivity;
    protected static $logName = 'Pays';
    protected static $logAttributes = ['id',
        'code',
        'alpha2',
        'pays',
        'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Pays";
    }

}
