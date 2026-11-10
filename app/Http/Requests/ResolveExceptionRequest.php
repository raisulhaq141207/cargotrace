<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isAdmin() || $this->user()->isOperator());
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:RESOLVED,INVESTIGATING'],
            'resolved_at' => ['nullable', 'date'],
            'resolution_notes' => ['required', 'string', 'max:1000'],
        ];
    }
}
