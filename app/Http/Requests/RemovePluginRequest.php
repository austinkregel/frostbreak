<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;

class RemovePluginRequest extends FormRequest
{
    public function authorize()
    {
        $project = $this->route('project');
        return $this->user() && $this->user()->can('update', $project);
    }

    public function rules()
    {
        return [
            'id' => 'required|integer|exists:marketplace_packages,id',
        ];
    }
}

