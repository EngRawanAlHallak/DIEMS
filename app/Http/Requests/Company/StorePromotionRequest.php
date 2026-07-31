<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type'                => 'required|in:discount,bundle',
            'product_ids'         => 'required|array|min:1',
            'product_ids.*'       => 'exists:products,id',
            'discount_percentage' => 'required_if:type,discount|nullable|integer|min:1|max:100',
            'total_package_price' => 'required_if:type,bundle|nullable|numeric|min:0',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'discount_percentage.required_if' => 'يرجى تحديد نسبة الخصم لهذا النوع من العروض.',
            'total_package_price.required_if' => 'يرجى تحديد سعر الحزمة الإجمالي.',
            'product_ids.min' => 'يجب اختيار منتج واحد على الأقل لتفعيل العرض.',
        ];
    }
}
