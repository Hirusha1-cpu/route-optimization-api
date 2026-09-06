<?php

namespace App\Http\Requests;

use App\Models\Delivery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['assigned', 'in_transit', 'failed', 'pending'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $delivery = $this->route('delivery');

            if ($delivery && ! $delivery->canTransitionTo($this->input('status'))) {
                $validator->errors()->add(
                    'status',
                    "Cannot transition delivery from '{$delivery->status}' to '{$this->input('status')}'."
                );
            }
        });
    }
}