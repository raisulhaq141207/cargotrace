<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isAdmin() || $this->user()->isOperator());
    }

    public function rules(): array
    {
        $shipmentId = $this->route('shipment')?->id ?? $this->route('id');

        return [
            'carrier_id' => ['sometimes', 'exists:carriers,id'],
            'origin_id' => ['sometimes', 'exists:locations,id'],
            'destination_id' => ['sometimes', 'exists:locations,id'],
            'cargo_type' => ['sometimes', 'string', 'max:50'],
            'weight' => ['sometimes', 'numeric', 'min:0.01'],
            'volume' => ['nullable', 'numeric', 'min:0.01'],
            'current_status' => ['sometimes', 'in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED,CANCELLED'],
            'expected_departure' => ['sometimes', 'date'],
            'expected_arrival' => ['sometimes', 'date'],
            'estimated_arrival' => ['nullable', 'date'],
            'actual_departure' => ['nullable', 'date'],
            'actual_arrival' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
