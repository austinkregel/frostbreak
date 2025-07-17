<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'github_repo_id',
        'name',
        'full_name',
        'html_url',
        'description',
        'private',
        'default_branch',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->hasOne(Package::class, 'repository_url', 'html_url');
    }
}
