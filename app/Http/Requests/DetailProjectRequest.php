<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;

class DetailProjectRequest extends FormRequest
{
    public function authorize()
    {
//        $packageIds = $this->get('id', []);
//
//        if (!is_array($packageIds)) {
//            $packageIds = [$packageIds];
//        }
//
//        $project = Project::whereIn('license_id', $packageIds)->get();
//        return $project && $this->user() && $this->user()->can('view', $project);
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|exists:marketplace_projects,id',
        ];
    }
}

