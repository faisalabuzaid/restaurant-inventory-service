<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryRequest extends FormRequest
{
    public function rules(): array
    {
        $orderId = $this->route('purchaseOrder')->id;

        return [
            'received_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('purchase_order_lines', 'id')->where('purchase_order_id', $orderId),
            ],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'At least one line is required.',
            'lines.min' => 'At least one line is required.',
            'lines.*.purchase_order_line_id.exists' => 'That line does not belong to this purchase order.',
            'lines.*.purchase_order_line_id.distinct' => 'Each line may appear only once.',
            'lines.*.quantity.gt' => 'Quantity must be greater than zero.',
        ];
    }
}
