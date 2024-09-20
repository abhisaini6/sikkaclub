<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incrementor extends Model
{
    protected $table = 'incrementor'; // Make sure this is the correct table name
    protected $fillable = ['same'];   // Make sure 'same' is fillable
}
