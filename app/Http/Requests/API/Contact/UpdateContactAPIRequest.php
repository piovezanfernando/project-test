<?php

namespace App\Http\Requests\API\Contact;

use InfyOm\Generator\Request\APIRequest;

class UpdateContactAPIRequest extends APIRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:50|string|max:50|string|max:50',
            'phone' => 'nullable|string|max:20|nullable|string|max:20|nullable|string|max:20',
            'email' => 'nullable|string|max:100|nullable|string|max:100|nullable|string|max:100'
        ];
    }

    public static function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Contact name'],
            'phone' => ['description' => 'Contact phone'],
            'email' => ['description' => 'Contact email']
        ];
    }
}
