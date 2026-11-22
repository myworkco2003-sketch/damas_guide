<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexAdminAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * نفس سلوك Django: القيمة غير الصحيحة بتنتجاهل بدون خطأ.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'level' => $this->intOrNull($this->query('level')),
            'parent_id' => $this->intOrNull($this->query('parent_id')),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            /** المستوى الإداري: 8 قطاع، 9 بلدية، 10 حي */
            'level' => ['nullable', 'integer'],
            /** رقم المنطقة الأب */
            'parent_id' => ['nullable', 'integer'],
        ];
    }

    public function level(): ?int
    {
        return $this->validated('level');
    }

    public function parentId(): ?int
    {
        return $this->validated('parent_id');
    }

    private function intOrNull(mixed $value): ?int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);

        return $int === false ? null : $int;
    }
}
