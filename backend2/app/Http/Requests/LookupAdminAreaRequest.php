<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LookupAdminAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            /**
             * خط العرض
             *
             * @example 33.513
             */
            'lat' => ['required', 'numeric'],
            /**
             * خط الطول
             *
             * @example 36.292
             */
            'lon' => ['required', 'numeric'],
        ];
    }

    public function lat(): float
    {
        return (float) $this->validated('lat');
    }

    public function lon(): float
    {
        return (float) $this->validated('lon');
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json(
            ['detail' => 'lat و lon مطلوبان ويجب أن يكونا أرقامًا.'],
            400
        ));
    }
}
