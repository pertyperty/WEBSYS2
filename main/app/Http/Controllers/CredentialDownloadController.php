<?php

namespace App\Http\Controllers;

use App\Models\CredentialDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CredentialDownloadController extends Controller
{
    public function __invoke(Request $request, int $document): StreamedResponse
    {
        $record = CredentialDocument::visibleTo($request->user())->findOrFail($document);
        try {
            $stream = Storage::disk('local')->readStream($record->private_path);
        } catch (FilesystemException $exception) {
            report($exception);
            abort(404, 'The stored file is unavailable. Contact an administrator.');
        }
        abort_unless(is_resource($stream), 404, 'The stored file is unavailable. Contact an administrator.');
        $extension = match ($record->mime_type) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'pdf',
        };

        return response()->streamDownload(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 'credential-'.$record->reference.'.'.$extension, [
            'Content-Type' => $record->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ]);
    }
}
