<?php

namespace App\Http\Requests\Caretaker;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'caretaker';
    }

    public function rules(): array
    {
        return [
            'completion_notes' => ['required', 'string'],
            'completion_photo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'completion_notes.required' => 'Completion notes are required before marking a task complete.',
        ];
    }
}