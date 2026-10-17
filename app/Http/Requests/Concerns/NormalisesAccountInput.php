<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Facades\App;

trait NormalisesAccountInput
{
    /**
     * DNS lookups catch throwaway domains but need network access, so they are
     * only enforced in production where that cost is acceptable.
     *
     * @return array<int, string>
     */
    protected function emailRules(): array
    {
        return [
            'required',
            'string',
            App::isProduction() ? 'email:rfc,dns' : 'email:rfc',
            'max:255',
            'unique:users,email',
        ];
    }

    /**
     * Normalise case/whitespace before validation so that unique checks and
     * stored values agree regardless of how the client formatted the input.
     */
    protected function prepareForValidation(): void
    {
        $normalised = [];

        if (is_string($this->input('email'))) {
            $normalised['email'] = mb_strtolower(trim($this->input('email')));
        }

        if (is_string($this->input('country_code'))) {
            $normalised['country_code'] = mb_strtoupper(trim($this->input('country_code')));
        }

        if ($normalised !== []) {
            $this->merge($normalised);
        }
    }
}
