<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Postfichestag extends Model
{
    use HasFactory;
    protected $fillable =[
       
        'post_id',
        'etiquettes_id',
        'groupe_id',
        
        
    ];
    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} Posthistorique";
    }
    public function post(){
        return $this->belongsTo(Post::class);
    }
    public function etiquete(){
        return $this->belongsTo(Etiquette::class);
    }
    public function groupe(){
        return $this->belongsTo(Groupe::class);
    }
}
