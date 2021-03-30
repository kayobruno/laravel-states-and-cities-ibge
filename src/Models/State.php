<?php

namespace Kayo\StatesAndCitiesIbge\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = ['name', 'acronym', 'ibge_id', 'ibge_region_id', 'region_acronym', 'region_name'];

    /**
     * @return HasMany
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
