<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    protected $table = 'counties';
    public $timestamps = false;
    protected $fillable = ['name'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'id_county');
    }
}
