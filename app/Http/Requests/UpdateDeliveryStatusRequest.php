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
            // 💡 'delivered' status එක මෙතැනටත් ඇතුළත් කරමු, එවිට internal state machine validator එකෙන්ම 'Direct change not allowed' error එක ලස්සනට handle කරගත හැක.
            'status' => ['required', Rule::in(['assigned', 'in_transit', 'failed', 'pending', 'delivered'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $delivery = $this->route('delivery');
            $newStatus = $this->input('status');

            // 🚨 🚀 Business Rule Guard: direct 'delivered' status එකට මාරු කිරීම තහනම්! [5.F]
            if ($newStatus === 'delivered') {
                $validator->errors()->add(
                    'status',
                    "Direct status change to 'delivered' is not allowed. You must use the payment confirmation endpoint."
                );
                return;
            }

            if ($delivery && ! $delivery->canTransitionTo($newStatus)) {
                $validator->errors()->add(
                    'status',
                    "Cannot transition delivery from '{$delivery->status}' to '{$newStatus}'."
                );
            }
        });
    }
}
