<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the church that owns the department.
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Get the contents for the department (legacy one-to-many).
     * @deprecated Use contents() many-to-many relationship instead
     */
    public function contentsLegacy(): HasMany
    {
        return $this->hasMany(Contents::class);
    }

    /**
     * Get the contents for the department (many-to-many).
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Contents::class, 'content_department', 'department_id', 'content_id')
            ->withTimestamps()
            ->orderByDesc('published_at');
    }
}
