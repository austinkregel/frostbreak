<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Version extends Model
{
    use HasFactory;

    protected $table = 'marketplace_versions';

    protected $fillable = [
        'semantic_version',
        'extra',
        'requires',
        'requires_dev',
        'suggests',
        'time',
        'provides',
        'conflicts',
        'replaces',
        'tags',
        'installation_commands',
        'description',
        'hash',
        'license',
        'package_id',
        'dist_url',
        'released_at',
    ];

    protected $casts = [
        'extra' => 'array',
        'requires' => 'array',
        'requires_dev' => 'array',
        'suggests' => 'array',
        'provides' => 'array',
        'conflicts' => 'array',
        'replaces' => 'array',
        'tags' => 'array',
        'installation_commands' => 'array',
        'released_at' => 'datetime',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function getCacheLocation()
    {
        $version = Str::slug($this->semantic_version ?? 'latest');

        $packageDestination = str_replace('.', '/', $this->package->code);

        return $packageDestination . '/' . $version . '.zip';
    }
}
