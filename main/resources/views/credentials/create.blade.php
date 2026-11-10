<x-layout title="Submit evidence">
    <a href="{{ route('credentials.index') }}" class="text-link">Back to documents</a>
    <h1 class="mb-2 mt-3 text-3xl font-bold">Submit evidence</h1>
    <p class="page-intro mb-7">Upload your certificate or document for review.</p>
    @php
        $formState = [
            'qualificationId' => (string) old('qualification_id', request('qualification', '')),
            'enrollmentId' => (string) old('employee_training_id', ''),
            'issuedAt' => old('issued_at', ''),
            'qualifications' => $qualifications->map(fn ($qualification) => $qualification->only(['id', 'requires_expiry', 'requires_training'])),
            'enrollments' => $enrollments->map(fn ($enrollment) => ['id' => $enrollment->id, 'qualification_id' => $enrollment->trainingCourse->qualification_id]),
        ];
    @endphp
    <form method="POST" action="{{ route('credentials.store') }}" enctype="multipart/form-data" class="panel max-w-2xl space-y-6" x-data="evidenceForm(@js($formState))">
        @csrf
        <div>
            <label for="qualification_id">Qualification</label>
            <select id="qualification_id" name="qualification_id" class="field" required x-model="qualificationId" @change="changeQualification()">
                <option value="" disabled>Select a qualification</option>
                @foreach($qualifications as $qualification)
                    <option value="{{ $qualification->id }}" @selected(old('qualification_id', request('qualification')) == $qualification->id)>{{ $qualification->name }} ({{ $qualification->safetyDomain->name }})</option>
                @endforeach
            </select>
            @error('qualification_id')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div x-show="requiresTraining" x-cloak>
            <label for="employee_training_id">Training enrollment</label>
            <select id="employee_training_id" name="employee_training_id" class="field" x-model="enrollmentId" :disabled="!requiresTraining" :required="requiresTraining" aria-describedby="enrollment-help">
                <option value="" disabled>Select your enrollment</option>
                @foreach($enrollments as $enrollment)
                    <option value="{{ $enrollment->id }}" x-show="qualificationId == '{{ $enrollment->trainingCourse->qualification_id }}'" :disabled="qualificationId != '{{ $enrollment->trainingCourse->qualification_id }}'" @selected(old('employee_training_id') == $enrollment->id)>{{ $enrollment->trainingCourse->title }} ({{ ucfirst($enrollment->status->value) }})</option>
                @endforeach
            </select>
            <p id="enrollment-help" class="field-help">Training must be completed before this evidence can be approved.</p>
            <p class="notice notice-warning mt-3" x-show="matchingEnrollments.length === 0">You need an enrollment for this qualification. <a href="{{ route('training-courses.index') }}" class="font-semibold underline">Browse courses</a></p>
            @error('employee_training_id')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label for="issued_at">Issue date</label><input id="issued_at" name="issued_at" type="date" value="{{ old('issued_at') }}" x-model="issuedAt" max="{{ now('Asia/Manila')->toDateString() }}" class="field" required>@error('issued_at')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="expires_at">Expiry date <span class="font-normal text-slate-500" x-show="!requiresExpiry">(optional)</span></label><input id="expires_at" name="expires_at" type="date" value="{{ old('expires_at') }}" :required="requiresExpiry" :min="issuedAt" class="field">@error('expires_at')<p class="field-error">{{ $message }}</p>@enderror</div>
        </div>
        <div>
            <label for="file">Evidence file</label>
            <input id="file" name="file" type="file" accept="application/pdf,image/jpeg,image/png" class="field file:mr-3 file:rounded-md file:border-0 file:bg-teal-50 file:px-3 file:py-1 file:text-sm file:font-semibold file:text-teal-900" required aria-describedby="file-help">
            <p id="file-help" class="field-help">PDF, JPEG or PNG, up to {{ number_format($maxUploadKiB / 1024, 1) }} MiB. Only authorized users can access your file.</p>
            @error('file')<p class="field-error">{{ $message }} Re-select your file to try again.</p>@enderror
        </div>
        <div class="flex flex-wrap gap-3"><button type="submit" class="button">Submit for review<x-icon name="arrow" class="size-4" /></button><a href="{{ route('credentials.index') }}" class="button-secondary">Cancel</a></div>
    </form>
</x-layout>
