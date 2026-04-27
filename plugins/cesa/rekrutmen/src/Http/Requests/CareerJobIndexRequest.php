<?php

namespace Cesa\Rekrutmen\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CareerJobIndexRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 12;

    public const MAX_PER_PAGE = 50;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:100'],
            'q'        => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'limit'    => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search !== '' ? $search : null;
    }

    public function locationFilter(): ?string
    {
        $location = trim((string) $this->input('location', ''));

        return $location !== '' ? $location : null;
    }

    public function perPage(): int
    {
        return min($this->integer('per_page', self::DEFAULT_PER_PAGE), self::MAX_PER_PAGE);
    }

    protected function prepareForValidation(): void
    {
        $normalizedInput = [];

        if (! $this->filled('search') && $this->filled('q')) {
            $normalizedInput['search'] = $this->input('q');
        }

        if (! $this->filled('per_page') && $this->filled('limit')) {
            $normalizedInput['per_page'] = $this->input('limit');
        }

        if ($normalizedInput !== []) {
            $this->merge($normalizedInput);
        }
    }
}
