<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class State extends Model
{
    use HasFactory;

    use HasFactory, LogsActivity;

    protected $primaryKey = 'id';
    protected $fillable = [
        'isGoogleUpdated',
        'isDuplicate',
        'isSuspended',
        'canUpdate',
        'canDelete',
        'isVerified',
        'needsReverification',
        'isPendingReview',
        'isDisabled',
        'isPublished',
        'isDisconnected',
        'isLocalPostApiDisabled',
        'canModifyServiceList',
        'canHaveFoodMenus',
        'hasPendingEdits',
        'hasPendingVerification',
        'canOperateHealthData',
        'canOperateLodgingData',
        'fiche_id',
        'created_at',
        'updated_at'
    ];
    protected static $logName = 'states';
    protected static $logAttributes = ['id', 'isGoogleUpdated',
        'isDuplicate',
        'isSuspended',
        'canUpdate',
        'canDelete',
        'isVerified',
        'needsReverification',
        'isPendingReview',
        'isDisabled',
        'isPublished',
        'isDisconnected',
        'isLocalPostApiDisabled',
        'canModifyServiceList',
        'canHaveFoodMenus',
        'hasPendingEdits',
        'hasPendingVerification',
        'canOperateHealthData',
        'canOperateLodgingData',
        'fiche_id',
        'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} states";
    }
    public function fiche()
{
    return $this->belongsTo(Fiche::class);
}
}

