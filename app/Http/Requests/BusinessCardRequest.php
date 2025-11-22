<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BusinessCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:2'],
            'images.*' => ['required', 'file', 'mimes:jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Upload 1-2 images via multipart form-data field "images[]"',
            'images.array' => 'Upload 1-2 images via multipart form-data field "images[]"',
            'images.min' => 'Upload 1-2 images via multipart form-data field "images[]"',
            'images.max' => 'Exactly 1 or 2 images are required',
            'images.*.required' => 'Exactly 1 or 2 images are required',
            'images.*.file' => 'Images must be JPG, JPEG, or PNG files.',
            'images.*.mimes' => 'Images must be JPG, JPEG, or PNG files.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $message = $validator->errors()->first();

        throw new HttpResponseException(
            response()->json(['error' => $message], 400)
        );
    }
}
