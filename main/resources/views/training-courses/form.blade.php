<x-layout :title="$course->exists ? 'Edit course' : 'Add course'">
    <a href="{{ route('training-courses.index') }}" class="back-link"><x-icon name="arrow-left" class="size-4" />Course catalog</a>
    <div class="page-heading mt-3">
        <div><h1 class="text-2xl font-bold sm:text-3xl">{{ $course->exists ? 'Edit course' : 'Add course' }}</h1></div>
    </div>
    <form method="POST" action="{{ $course->exists ? route('training-courses.update', $course) : route('training-courses.store') }}" class="panel max-w-2xl space-y-5">
        @csrf
        @if($course->exists)
            @method('PUT')
        @endif
        <x-input name="title" label="Course title" :value="$course->title" maxlength="255" required />
        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description" class="field" rows="4" maxlength="5000">{{ old('description', $course->description) }}</textarea>
        </div>
        <div>
            <label for="qualification_id">Qualification</label>
            <select id="qualification_id" name="qualification_id" class="field" required>
                <option value="" disabled @selected(! old('qualification_id', $course->qualification_id))>Select a qualification</option>
                @foreach($qualifications as $qualification)
                    <option value="{{ $qualification->id }}" @selected(old('qualification_id', $course->qualification_id) == $qualification->id)>{{ $qualification->name }} ({{ $qualification->safetyDomain->name }})</option>
                @endforeach
            </select>
            <p class="field-help">Locked once anyone enrolls.</p>
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status" class="field">
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $course->status->value) === $status->value)>{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>
            <p class="field-help">Only published courses accept enrollments.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="submit" class="button">Save course</button>
            <a href="{{ route('training-courses.index') }}" class="button-secondary">Cancel</a>
        </div>
    </form>
</x-layout>
