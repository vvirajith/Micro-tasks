<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Email is absent on purpose: changing it must go through a verification
     * flow rather than a plain profile update.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'country_code' => ['sometimes', 'required', 'string', 'size:2', 'alpha'],
            'preferred_language' => ['sometimes', 'required', 'string', 'max:10', 'regex:/^[a-z]{2}(-[A-Z]{2})?$/'],

            // Clients only; ignored for every other role.
            'company_name' => ['sometimes', 'required', 'string', 'min:2', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-]{5,18}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('country_code'))) {
            $this->merge(['country_code' => mb_strtoupper(trim($this->input('country_code')))]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number format is invalid.',
            'preferred_language.regex' => 'The preferred language must be a valid language code, such as "en" or "pt-BR".',
        ];
    }
}
