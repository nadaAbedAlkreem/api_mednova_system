<?php
namespace App\Services\api;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadService
{
    public function upload(UploadedFile $file, string $path = 'uploads', ?string $disk = 'public', ?string $subFolder = null): string
    {
         if ($subFolder) {
            $path = rtrim($path, '/') . '/' . trim($subFolder, '/');
        }
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($path, $filename, $disk);
    }


    public function delete(?string $filePath, ?string $disk = 'public'): void
    {
        if ($filePath && Storage::disk($disk)->exists($filePath)) {
            Storage::disk($disk)->delete($filePath);
        }
    }






}
