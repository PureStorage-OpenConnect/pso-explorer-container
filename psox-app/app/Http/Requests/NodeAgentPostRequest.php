<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NodeAgentPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'ip' => 'required|ipv4',
            'result' => 'required|boolean',
            'node' => 'required|string|min:2|max:255',
        ];
    }
}
