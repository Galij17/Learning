<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'instructor']) ?? false;
    }

    public function rules(): array
    {
        $courseId = $this->route('course')?->id;

        return [
            'lesson_id' => [
                'required',
                Rule::exists('lessons', 'id')
                    ->whereNull('deleted_at')
                    ->when($courseId !== null, fn ($q) => $q->where('course_id', $courseId)),
            ],
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:pdf,ppt,docx,image,video,link,text'],
            'file' => [
                'nullable',
                'file',
                'max:51200',
                'mimes:pdf,ppt,pptx,doc,docx,jpg,jpeg,png,webp,mp4,webm',
            ],
            'video_url' => ['nullable', 'url:http,https', 'max:500'],
            'content' => ['nullable', 'string', 'max:50000'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'is_published' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            if (in_array($type, ['pdf', 'ppt', 'docx', 'image'], true) && ! $this->hasFile('file') && ! $this->route('material')) {
                $validator->errors()->add('file', 'Berkas materi wajib diunggah untuk tipe materi ini.');
            }
            if ($type === 'video' && ! $this->hasFile('file') && ! $this->filled('video_url') && ! $this->route('material')) {
                $validator->errors()->add('video_url', 'Unggah berkas video atau masukkan URL video pembelajaran.');
            }
            if ($type === 'link' && ! $this->filled('video_url')) {
                $validator->errors()->add('video_url', 'Tautan referensi wajib diisi.');
            }
            if ($type === 'text' && ! $this->filled('content')) {
                $validator->errors()->add('content', 'Konten bacaan wajib diisi.');
            }
        });
    }
}
