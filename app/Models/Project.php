<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory, Searchable;

    protected $table = 'marketplace_projects';

    protected $fillable = [
        'name',
        'license_id',
        'owner',
        'owner_id',
        'owner_type',
    ];

    protected $casts = [
        'owner_id' => 'integer',
    ];

    public function toSearchableArray()
    {
        return $this->toArray();
    }

    public function packages()
    {
        return $this->morphedByMany(Package::class, 'resourceable', 'marketplace_resourceables');
    }
    public function plugins()
    {
        return $this->packages()->whereJsonContains('keywords', 'plugin');
    }
    public function themes()
    {
        return $this->packages()->whereJsonContains('keywords', 'theme');
    }

    public function user()
    {
        return $this->morphTo('owner');
    }
    public function owner()
    {
        return $this->morphTo('owner', 'owner_type', 'owner_id');
    }
}
