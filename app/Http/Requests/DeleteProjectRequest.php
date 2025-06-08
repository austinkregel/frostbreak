<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteProjectRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->can('delete', $this->route('project'));
    }

    public function rules()
    {
        return [];
    }
}
