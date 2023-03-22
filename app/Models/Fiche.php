<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Traits\LogsActivity;

class Fiche extends Model
{
    use LogsActivity;
    use HasFactory;

    protected $primaryKey = 'id';
    protected $fillable = [
        'logo',
        'description',
        'locationName',
        'state',
        'name',
        'url_map',
        'storeCode',
        'closedatestrCode',
        'primaryPhone',
        'adwPhone',
        'labels',
        'additionalPhones',
        'websiteUrl',
        'etatwebsite',
        'email',
        'latitude',
        'longitude',
        'OpenInfo_status',
        'address',
        'city',
        'placeId',
        'country',
        'postalCode',
        'OpenInfo_opening_date',
        'OpenInfo_canreopen',
        'otheradress',
        'franchises_id',
        'methodverif',
        'etat',
        'notification'
    ];
    protected static $logName = 'Fiche';
    protected static $logAttributes = [
        'id',
        'logo',
        'description',
        'locationName',
        'name',
        'url_map',
        'storeCode',
        'closedatestrCode',
        'primaryPhone',
        'adwPhone',
        'labels',
        'additionalPhones',
        'websiteUrl',
        'etatwebsite',
        'email',
         'placeId',
        'latitude',
        'longitude',
        'OpenInfo_status',
        'address',
        'city',
        'country',
        'postalCode',
        'OpenInfo_opening_date',
        'OpenInfo_canreopen',
        'otheradress',
        'franchises_id', 
       'methodverif',
    ];
    //affiche juste les champs que modifier
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} fiche";
    }
    public function franchises()
    {

        return $this->hasMany(Franchise::class,'id','franchises_id','socialReason');
    }
    public function attibutes()
    {
        return $this->hasMany(Attribute::class);
    }
    public function categories()
    {

        return $this->hasMany(Categorie::class);
    }
    public function avis()
    {

        return $this->hasMany(Avi::class);
    }
    public function states()
    {
        return $this->hasMany(State::class);
    }
    public function statistique()
    {
        return $this->hasMany(Statistique::class);
    }
    public function posts(){
        return $this->hasMany(Post::class);
    }
    public function metadatas(){
        return $this->hasMany(Metadata::class);
    }
    public function fichehours(){
        return $this->hasMany(Fichehour::class);
    }
    public function ficheusers(){
        return $this->hasMany(Fichehour::class);
    }
   public function avisreponses(){
        return $this->hasMany(Avisreponse::class);
   }
   public function photos(){
        return $this->hasMany(Photo::class);
   }
   public function documents(){
        return $this->hasMany(Document::class);
   }
    public function etiquetgroupes(){
        return $this->hasMany(Etiquetgroupe::class);
    }
}
