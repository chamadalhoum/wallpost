<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Poststats extends Model
{
    use HasFactory;
    use LogsActivity;
    protected $table = 'post_stats';
    protected $primaryKey = 'id';
    protected $fillable = [
        'localPostViewsSearch',
        'localPostActions',
        'date',
        'post_fiche_id',
        'created_at',
        'updated_at',
    ];
    protected static $logName = 'Poststats';
    protected static $logAttributes = ['id',
    'localPostViewsSearch',
    'localPostActions',
    'date',
    'post_fiche_id',
    'created_at',
    'updated_at',
 ];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Poststats";
    }

    public function etiquetgroupes()
    {
        return $this->hasMany(Etiquetgroupe::class);
    }
}
