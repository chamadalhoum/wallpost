<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Ficheuser extends Model
{
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable=[
        'fiche_id',
        'franchise_id',
        'user_id',
        'namefiche',
        'role_id',
        'pendingInvitation'
        ];
    protected static $logName = 'Ficheuser';
    protected static $logAttributes = ['id','franchise_id','user_id','fiche_id','created_at','updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Ficheuser";
    }

}
