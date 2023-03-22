<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Statistique extends Model
{
    use HasFactory, LogsActivity;
    protected $primaryKey='id';
    protected $fillable =[
        'metricUnspecified',
        'all',
        'queriesDirect',
        'queriesIndirect',
        'queriesChain',
        'viewsMaps',
        'viewsSearch',
        'actionsWebsite',
        'actionsPhone',
        'actionsDrivingDirections',
        'photosViewsMerchant',
        'photosViewsCustomers',
        'photosCountMerchant',
        'photosCountCustomers',
        'localPostViewsSearch',
        'localPostActions',
        'date',
        'fiche_id',
      
    ];

    protected static $logName = 'Statistique';
    protected static $logAttributes = ['id', 'metricUnspecified',
        'all',
        'queriesDirect',
        'queriesIndirect',
        'queriesChain',
        'viewsMaps',
        'viewsSearch',
        'actionsWebsite',
        'actionsPhone',
        'actionsDrivingDirections',
        'photosViewsMerchant',
        'photosViewsCustomers',
        'photosCountMerchant',
        'photosCountCustomers',
        'localPostViewsSearch',
        'localPostActions',
        'statistiques_daily',
        'fiche_id','created_at','updated_at','nameaction'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Statistique";
    }
    public function fiche()
    {
        return $this->belongsTo('App\Models\Fiche');
    }
}
