<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;

class DetailProjectRequest extends FormRequest
{
    public function authorize()
    {
        $packageIds = $this->get('id', null);
        $project = Project::find($packageIds);
        return $project && $this->user() && $this->user()->can('view', $project);
    }

    public function rules()
    {
        return [
            'id' => 'required|integer|exists:marketplace_projects,id',
        ];
    }
}

