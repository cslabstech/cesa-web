<?php

namespace Cesa\Rekrutmen\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectWhatsAppAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name'         => ['nullable', 'string', 'max:100'],
            'mode'         => ['nullable', 'string', 'in:qr,pairing'],
            'phone_number' => ['nullable', 'required_if:mode,pairing', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.required_if' => 'Isi nomor WhatsApp HP untuk mendapatkan kode pairing.',
        ];
    }
}
