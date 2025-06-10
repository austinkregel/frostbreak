<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->can('update', $this->route('project'));
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
