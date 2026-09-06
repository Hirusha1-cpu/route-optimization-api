<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDriver() ?? false;
    }

    public function rules(): array
    {
        return [
            'amount_collected' => ['required', 'numeric', 'min:0'],
        ];
    }
}