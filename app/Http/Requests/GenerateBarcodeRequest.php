<?php

namespace App\Http\Requests;

use App\Services\BarcodeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'    => ['required', 'string', Rule::in(BarcodeService::TYPES)],
            // Only digits, letters, spaces and a few safe symbols are allowed.
            'code'    => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9\-\s\.\$\/\+%]+$/'],
            'addon'   => ['nullable', 'string', 'regex:/^\d{2}$|^\d{5}$/'],
            'width'   => ['nullable', Rule::in(['fine', 'medio', 'largo'])],
            'height'  => ['nullable', 'integer', 'min:20', 'max:300'],
            'dpi'     => ['nullable', 'integer', Rule::in([150, 300, 600])],
            'show_text' => ['nullable', 'boolean'],
            'color'   => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
            // formats[] used only for the download endpoint.
            'formats'   => ['nullable', 'array'],
            'formats.*' => [Rule::in(['svg', 'eps', 'pdf', 'jpeg'])],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex'  => 'The code contains invalid characters.',
            'addon.regex' => 'The add-on must contain exactly 2 or 5 digits.',
        ];
    }

    /** Options array consumed by BarcodeService. */
    public function barcodeOptions(): array
    {
        return array_filter([
            'addon'     => $this->input('addon'),
            'width'     => $this->input('width', 'medio'),
            'height'    => $this->input('height'),
            'show_text' => $this->boolean('show_text', true),
            'color'     => $this->input('color'),
        ], fn ($v) => $v !== null);
    }
}
