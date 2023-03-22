<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Post extends Model
{
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable =[
        'genre',
        'type',
        'name',
        'summary',
        'topic_type',
        'search_url',
        'event_start_date',
        'event_end_date',
        'event_start_time',
        'event_end_time',
        'media_type',
        'media_url',
        'action_type',
        'action_url',
        'coupon_code',
        'redeem_online_url',
        'terms_conditions',
        'state',
        'programmed_date',
        'user_id',
        'fiche_id',
        'prix_max',
        'prix_min',
        'prix_produit',
        'calltoaction',
        'catprod_id',
        'type_envoi',
        'nameaction'
    ];
    use  LogsActivity;
    protected static $logName = 'Post';
    protected static $logAttributes = ['id',
        'genre',
        'type',
        'name',
        'summary',
        'topic_type',
        'search_url',
        'event_start_date',
        'event_end_date',
        'event_start_time',
        'event_end_time',
        'media_type',
        'media_url',
        'action_type',
        'action_url',
        'coupon_code',
        'redeem_online_url',
        'terms_conditions',
        'state',
        'programmed_date',
        'user_id',
        'fiche_id',
        'prix_max',
        'prix_min',
        'prix_produit',
        'created_at',
        'updated_at'];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Post";
    }
    public function fiche(){
        return $this->belongsTo(Fiche::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }

}
