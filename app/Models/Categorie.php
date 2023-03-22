<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Categorie extends Model
{
    use HasFactory;
    protected $fillable=[
        'categorieId',
        'displayName',
        'type',
        'user_id',
        'fiche_id',
        "state"
    ];
    use  LogsActivity;
    protected static $logName = 'Categorie';
    protected static $logAttributes = ['id',
        'categorieId',
        'displayName',
        'type',
        'user_id',
        'fiche_id',
        'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Categorie";
    }
    public function fiche()
    {
        return $this->belongsTo(Fiche::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
