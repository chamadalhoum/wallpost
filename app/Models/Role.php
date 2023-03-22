<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Role extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'nameenglais'
    ];
    protected static $logName = 'roles';
    protected static $logAttributes = ['id','name' ,'nameenglais','created_at','updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} role";
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function acces()
    {
        return $this->hasMany(Acce::class);
    }


}
