<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\CustomerContact;
use App\Models\ProductVariant;

class StoreCustomerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('variant_id') && !$this->filled('product_id')) {
            $productId = ProductVariant::where('variant_id', $this->input('variant_id'))
                ->value('product_id');

            if ($productId) {
                $this->merge(['product_id' => $productId]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'first_name'          => ['required', 'string', 'max:100'],
            'last_name'           => ['required', 'string', 'max:100'],
            'email_address'       => ['required', 'email', 'max:225'],

            'subject_select'      => [
                'required',
                Rule::in([
                    CustomerContact::SUBJECT_GENERAL,
                    CustomerContact::SUBJECT_SUPPORT,
                    CustomerContact::SUBJECT_FEEDBACK,
                ]),
            ],

            'message_description' => ['required', 'string'],
            'product_search'      => ['nullable', 'string', 'max:225'],
            'product_id'          => ['nullable', 'integer', 'exists:products,product_id'],
            'variant_id'          => ['nullable', 'integer', 'exists:product_variants,variant_id'],

            'order_id'            => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $variantId = $this->input('variant_id');
            $productId = $this->input('product_id');

            if ($variantId && $productId) {
                $ok = ProductVariant::where('variant_id', $variantId)
                    ->where('product_id', $productId)
                    ->exists();

                if (!$ok) {
                    $validator->errors()->add('product_search', 'Please select a valid product from the list.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'first_name.required'          => 'Please enter your first name.',
            'last_name.required'           => 'Please enter your last name.',
            'email_address.required'       => 'Please enter your e-mail address.',
            'email_address.email'          => 'Please enter a valid e-mail address.',
            'subject_select.required'      => 'Please select a subject.',
            'message_description.required' => 'Please enter your message.',
        ];
    }
}
