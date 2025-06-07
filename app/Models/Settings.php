<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    // Define fillable fields for Laravel Eloquent
    protected $fillable = [
        // Add your settings fields here, e.g.:
        // 'theme', 'site_name', 'admin_email', etc.
    ];

    /**
     * Example: Get available theme options (stub for future implementation)
     *
     * @return array
     */
    public static function getThemeOptions(): array
    {
        // Implement logic to fetch available themes if needed
        return [];
    }
}
