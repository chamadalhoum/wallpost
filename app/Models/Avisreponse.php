<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Avisreponse extends Model
{
    use HasFactory;
    use LogsActivity;
    protected $primaryKey = 'id';
    protected $fillable = [
        'reponse',
        'avis_id',
        'user_id',
        'fiche_id',
    ];
    protected static $logName = 'Avisreponse';
    protected static $logAttributes = ['id',
        'reponse',
        'avis_id',
        'user_id',
        'fiche_id',
        'created_at',
        'updated_at', ];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Avisreponse";
    }

    public function avis()
    {
        return $this->belongsTo(Avi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fiches()
    {
        return $this->belongsTo(Fiche::class);
    }
}
