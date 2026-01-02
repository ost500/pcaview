<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Church extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the departments for the church.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
