<x-layout title="Submit evidence">
    <a href="{{ route('credentials.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to credentials</a>
    <h1 class="mb-6 mt-4 text-3xl font-bold">Submit evidence</h1>
    <form method="POST" action="{{ route('credentials.store') }}" enctype="multipart/form-data" class="panel max-w-2xl space-y-5">
        @csrf
        <div>
            <label for="qualification_id" class="text-sm font-medium">Qualification</label>
            <select id="qualification_id" name="qualification_id" class="field" required>
                <option value="">Choose an active qualification</option>
                @foreach($qualifications as $qualification)
                    <option value="{{ $qualification->id }}" @selected(old('qualification_id') == $qualification->id)>{{ $qualification->name }} / {{ $qualification->safetyDomain->name }} / {{ $qualification->requires_expiry ? 'Expiry required' : 'Expiry optional' }} / {{ $qualification->requires_training ? 'Training required' : 'No course required' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="employee_training_id" class="text-sm font-medium">Your enrollment (training qualifications only)</label>
            <select id="employee_training_id" name="employee_training_id" class="field">
                <option value="">No training enrollment</option>
                @foreach($enrollments as $enrollment)
                    <option value="{{ $enrollment->id }}" @selected(old('employee_training_id') == $enrollment->id)>{{ $enrollment->trainingCourse->title }} / {{ $enrollment->trainingCourse->qualification->name }} / {{ ucfirst($enrollment->status->value) }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-slate-500">Choose your enrollment for the selected qualification. You may submit while training is pending; approval requires recorded completion.</p>
        </div>
        <div>
            <label for="issued_at" class="text-sm font-medium">Issue date (Asia/Manila)</label>
            <input id="issued_at" name="issued_at" type="date" value="{{ old('issued_at') }}" max="{{ now('Asia/Manila')->toDateString() }}" class="field" required>
        </div>
        <div>
            <label for="expires_at" class="text-sm font-medium">Expiry date (required for expiring qualifications)</label>
            <input id="expires_at" name="expires_at" type="date" value="{{ old('expires_at') }}" class="field">
        </div>
        <div>
            <label for="file" class="text-sm font-medium">Evidence file</label>
            <input id="file" name="file" type="file" accept="application/pdf,image/jpeg,image/png" class="field p-3" required>
            <p class="mt-2 text-xs text-slate-500">PDF, JPEG, or PNG; up to {{ number_format($maxUploadKiB / 1024, 1) }} MiB. Files stay private. Re-select the file if validation fails.</p>
        </div>
        <p class="text-sm text-slate-600">Submitting evidence does not grant approval or eligibility. Renewals create separate submissions and preserve earlier reviews.</p>
        <button type="submit" class="button">Submit for review</button>
    </form>
</x-layout>
