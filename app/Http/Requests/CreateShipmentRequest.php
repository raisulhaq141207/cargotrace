<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isAdmin() || $this->user()->isOperator());
    }

    public function rules(): array
    {
        return [
            'shipment_number' => ['nullable', 'string', 'max:50', 'unique:shipments,shipment_number'],
            'customer_id' => ['required', 'exists:users,id'],
            'carrier_id' => ['required', 'exists:carriers,id'],
            'origin_id' => ['required', 'exists:locations,id'],
            'destination_id' => ['required', 'different:origin_id', 'exists:locations,id'],
            'cargo_type' => ['nullable', 'string', 'max:50'],
            'weight' => ['required', 'numeric', 'min:0.01'],
            'volume' => ['nullable', 'numeric', 'min:0.01'],
            'current_status' => ['nullable', 'in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED,CANCELLED'],
            'expected_departure' => ['required', 'date'],
            'expected_arrival' => ['required', 'date', 'after:expected_departure'],
            'estimated_arrival' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.declared_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.hs_code' => ['nullable', 'string', 'max:30'],
        ];
    }
}
