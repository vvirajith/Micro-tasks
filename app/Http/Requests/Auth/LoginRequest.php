<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * Label stored against the issued Sanctum token so users can later
     * recognise and revoke individual sessions.
     */
    public function deviceName(): string
    {
        $name = $this->input('device_name');

        if (is_string($name) && trim($name) !== '') {
            return mb_substr(trim($name), 0, 100);
        }

        return mb_substr($this->userAgent() ?: 'api-client', 0, 100);
    }
}
