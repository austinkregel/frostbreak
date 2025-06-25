<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;

class ShowProjectRequest extends FormRequest
{
    public function authorize()
    {
        $project = $this->route('project');
        return $this->user() && $this->user()->can('view', $project);
    }

    public function rules()
    {
        return [];
    }
}

