<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()?->can('create', Project::class)
            ?? false;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}

