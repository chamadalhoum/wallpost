<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;
class Etiquetgroupesfiche extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $fillable = [

        'state',
        'fiche_id',
        'etiquetgroupe_id',
        


    ];
}
