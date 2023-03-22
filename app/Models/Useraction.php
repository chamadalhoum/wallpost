<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Useraction extends Model
{
    use HasFactory;
    use LogsActivity;
    protected $fillable =[
        'modifAction',
        'oldContent',
        'newContent',
        'user_id'
    ];
    protected static $logName = 'useraction';
    protected static $logAttributes = ['id', 'modifAction',
        'oldContent',
        'newContent',
        'user_id','name','created_at','updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} useraction";
    }

}
