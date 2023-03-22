<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accountagence extends Model
{
    use HasFactory;
    protected $primaryKey='id';
    protected $fillable=[
        'account',
        'name',
        'franchise_id'
        ];
}
