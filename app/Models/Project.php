<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $table = 'marketplace_projects';

    protected $fillable = [
        'name',
        'license_id',
    ];

    public function packages()
    {
        return $this->morphedByMany(Package::class, 'resourceable', 'marketplace_resourceables');
    }
}
