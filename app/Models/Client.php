<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory, HasUlids;

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function activityEvents(): HasManyThrough
    {
        return $this->through('projects')->has('activityEvents');
    }

    /**
     * @return HasMany<ActivityReport, $this>
     */
    public function activityReports(): HasMany
    {
        return $this->hasMany(ActivityReport::class);
    }

    protected function casts(): array
    {
        return [
            'address' => AsCollection::class,
            'contacts' => AsCollection::class,
        ];
    }
}
