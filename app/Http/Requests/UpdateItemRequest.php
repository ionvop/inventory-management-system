<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $itemId = $this->route('item')->id;
        return [
            'name' => "sometimes|string|max:255|unique:items,name,{$itemId}",
            'unit' => 'sometimes|string|max:50',
            'minimum_stock' => 'sometimes|integer|min:0',
        ];
    }
}
