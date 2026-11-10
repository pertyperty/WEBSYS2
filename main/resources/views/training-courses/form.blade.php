<x-layout title="Manage training course">
    <a href="{{ route('training-courses.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to courses</a>
    <h1 class="mb-6 mt-4 text-3xl font-bold">{{ $course->exists ? 'Edit course' : 'Add course' }}</h1>
    <form method="POST" action="{{ $course->exists ? route('training-courses.update', $course) : route('training-courses.store') }}" class="panel max-w-2xl space-y-5">
        @csrf
        @if($course->exists)
            @method('PUT')
        @endif
        <div>
            <label for="title" class="text-sm font-medium">Course title</label>
            <input id="title" name="title" type="text" value="{{ old('title', $course->title) }}" class="field" maxlength="255" required>
        </div>
        <div>
            <label for="description" class="text-sm font-medium">Description</label>
            <textarea id="description" name="description" class="field" rows="5" maxlength="5000">{{ old('description', $course->description) }}</textarea>
        </div>
        <div>
            <label for="qualification_id" class="text-sm font-medium">Training qualification</label>
            <select id="qualification_id" name="qualification_id" class="field" required>
                <option value="">Select a qualification</option>
                @foreach($qualifications as $qualification)
                    <option value="{{ $qualification->id }}" @selected(old('qualification_id', $course->qualification_id) == $qualification->id)>{{ $qualification->name }} / {{ $qualification->safetyDomain->name }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-slate-500">The qualification cannot change after anyone enrolls.</p>
        </div>
        <div>
            <label for="status" class="text-sm font-medium">Publication status</label>
            <select id="status" name="status" class="field">
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $course->status->value) === $status->value)>{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-slate-500">Only published courses accept enrollment. Archived courses remain in history.</p>
        </div>
        <button type="submit" class="button">Save course</button>
        <a href="{{ route('training-courses.index') }}" class="button-secondary">Cancel</a>
    </form>
</x-layout>
