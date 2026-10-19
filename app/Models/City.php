<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    protected $table = 'cities';
    public $timestamps = false;
    protected $fillable = ['zip_code', 'city', 'id_county'];

    protected function casts(): array
    {
        return ['zip_code' => 'integer', 'id_county' => 'integer'];
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class, 'id_county');
    }
}
