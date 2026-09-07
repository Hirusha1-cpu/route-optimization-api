<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'address'       => ['required', 'string', 'max:255'],
            'lat'           => ['required', 'numeric', 'between:-90,90'],
            'lng'           => ['required', 'numeric', 'between:-180,180'],
            // 💡 ලංකාවේ delivery windows සාමාන්‍යයෙන් "09:00" වැනි පැය:මිනිත්තු format එකෙන් එන නිසා:
            'window_start'  => ['required', 'date_format:H:i'],
            'window_end'    => ['required', 'date_format:H:i', 'after:window_start'],
            'cod_amount'    => ['required', 'numeric', 'min:0'],
        ];
    }
}
