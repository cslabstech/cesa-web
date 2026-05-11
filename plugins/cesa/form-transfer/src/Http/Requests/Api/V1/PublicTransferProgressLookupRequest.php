<?php

namespace Cesa\FormTransfer\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class PublicTransferProgressLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'     => Str::lower(trim((string) $this->input('email'))),
            'reference' => trim((string) $this->input('reference')),
        ]);
    }

    public function rules(): array
    {
        return [
            'email'     => ['required', 'email', 'max:191'],
            'reference' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi untuk mengecek progres pengajuan.',
            'email.email'    => 'Email harus menggunakan format yang valid.',
        ];
    }
}
