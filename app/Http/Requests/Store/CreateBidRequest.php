<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class CreateBidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 認証はミドルウェアで処理済み
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'minPrice' => [
                'required',
                'numeric',
                'min:1',
                'max:10000000', // 1000万円以下
            ],
            'maxPrice' => [
                'required',
                'numeric',
                'min:1',
                'max:10000000', // 1000万円以下
                'gte:minPrice', // minPrice以上
            ],
            'message' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'minPrice.required' => '最低価格は必須です',
            'minPrice.numeric' => '最低価格は数値で入力してください',
            'minPrice.min' => '最低価格は1円以上で入力してください',
            'minPrice.max' => '最低価格は1000万円以下で入力してください',

            'maxPrice.required' => '最高価格は必須です',
            'maxPrice.numeric' => '最高価格は数値で入力してください',
            'maxPrice.min' => '最高価格は1円以上で入力してください',
            'maxPrice.max' => '最高価格は1000万円以下で入力してください',
            'maxPrice.gte' => '最高価格は最低価格以上である必要があります',

            'message.string' => 'メッセージは文字列で入力してください',
            'message.max' => 'メッセージは1000文字以内で入力してください',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'minPrice' => '最低価格',
            'maxPrice' => '最高価格',
            'message' => 'メッセージ',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator);
    }
}
