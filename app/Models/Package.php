<?php

namespace App\Models;

use App\Traits\Resource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Package extends Model
{
    use SoftDeletes, Resource, HasFactory;

    protected $table = 'marketplace_packages';

    protected $fillable = [
        'name',
        'description',
        'code',
        'image',
        'author',
        'needs_additional_processing',
        'keywords',
        'is_approved',
        'repository_url',
        'abandoned',
        'git_stars',
        'git_forks',
        'downloads',
        'favers',
        'git_watchers',
        'last_updated_at',
        'packagist_url',
        'latest_version_id',
        'demo_url',
        'product_url',
    ];

    protected $appends = ['hash'];

    protected $casts = [
        'keywords' => 'array',
        'needs_additional_processing' => 'boolean',
        'is_approved' => 'boolean',
        'abandoned' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    public function versions()
    {
        return $this->hasMany(Version::class, 'package_id');
    }

    public function latestVersion()
    {
        return $this->hasOne(Version::class, 'id', 'latest_version_id');
    }

    public function getHashAttribute()
    {
        $latestVersion = $this->versions()->orderByDesc('released_at')->first();

        return $latestVersion->hash ?? null;
    }
}
