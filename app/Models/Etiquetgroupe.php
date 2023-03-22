<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Etiquetgroupe extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $fillable = [

        'state',
        'groupe_id',
        'etiquette_id',
        'fiche_id'


    ];

    use  LogsActivity;

    protected static $logName = 'Etiquetgroupe';
    protected static $logAttributes = ['id',

        'state',
        'groupe_id',
        'etiquette_id',
        'fiche_id'
        , 'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Etiquetgroupe";
    }



    public function groupe()
    {
        return $this->belongsTo(Groupe::class);
    }
    public function fiche()
    {
        return $this->belongsTo(Fiche::class);
    }
    public function etiquette()
    {
        return $this->belongsTo(Etiquette::class);
    }
}
