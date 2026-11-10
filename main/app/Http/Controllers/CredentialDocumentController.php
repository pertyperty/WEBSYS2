<?php

namespace App\Http\Controllers;

use App\Actions\SelectEffectiveCredential;
use App\Actions\SubmitCredential;
use App\Enums\CredentialStatus;
use App\Http\Requests\StoreCredentialDocumentRequest;
use App\Models\CredentialDocument;
use App\Models\EmployeeTraining;
use App\Models\Qualification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use League\Flysystem\FilesystemException;

class CredentialDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', Rule::enum(CredentialStatus::class)]]);

        return view('credentials.index', [
            'documents' => CredentialDocument::visibleTo($request->user())->with(['employee', 'qualification'])
                ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
                ->orderByDesc('id')->paginate(15)->withQueryString(),
            'statuses' => CredentialStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('credentials.create', [
            'qualifications' => Qualification::active()->whereHas('safetyDomain', fn (Builder $query): Builder => $query->active())
                ->with('safetyDomain')->orderBy('name')->get(),
            'enrollments' => EmployeeTraining::where('employee_id', $request->user()->id)->with('trainingCourse.qualification')->orderByDesc('id')->get(),
            'maxUploadKiB' => config('credentials.max_upload_kib'),
        ]);
    }

    public function store(StoreCredentialDocumentRequest $request, SubmitCredential $submission): RedirectResponse
    {
        $document = $submission->handle($request->user(), $request->file('file'), $request->safe()->only(['qualification_id', 'issued_at', 'expires_at', 'employee_training_id']));

        return to_route('credentials.show', $document)->with('status', 'Evidence submitted for officer review.');
    }

    public function show(Request $request, int $document, SelectEffectiveCredential $selection): View
    {
        $record = CredentialDocument::visibleTo($request->user())
            ->with(['employee', 'qualification.safetyDomain', 'employeeTraining.trainingCourse', 'facility', 'jobRole', 'events.actor'])->findOrFail($document);
        try {
            $available = Storage::disk('local')->exists($record->private_path);
        } catch (FilesystemException $exception) {
            report($exception);
            $available = false;
        }

        return view('credentials.show', [
            'document' => $record,
            'available' => $available,
            'effective' => $selection->find($record->employee, $record->qualification),
        ]);
    }
}
