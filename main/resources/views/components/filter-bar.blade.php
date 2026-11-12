@props(['action'])
<div class="mb-6">
    <form method="GET" action="{{ $action }}" data-filter-form aria-label="Search and filter results" class="grid items-end gap-4 sm:flex sm:flex-wrap"
          @submit.prevent="if (!composing) apply()" @input="typing($event)" @change="changed($event)"
          @compositionstart="startComposition()" @compositionend="endComposition($event)">
        {{ $slot }}
        <div class="flex flex-wrap items-center gap-3">
            <button class="button-secondary" type="submit" x-show="!ready">Update results</button>
            <a class="text-link" href="{{ $action }}" @click.prevent="reset()">Clear filters</a>
        </div>
    </form>
    <p class="mt-3 text-xs text-slate-500" x-show="ready" x-cloak>Results update as you type or choose a filter.</p>
    <p class="mt-2 text-xs font-medium text-teal-800" :class="{ 'sr-only': !busy }" role="status" aria-live="polite" aria-atomic="true" x-text="busy ? 'Updating results...' : message" x-cloak></p>
    <div class="mt-3 text-sm text-rose-800" x-show="error" x-cloak role="alert">
        <p x-text="error"></p>
        <button class="text-link" type="button" @click="apply()">Try again</button>
        <a class="text-link ml-3" href="{{ request()->fullUrl() }}" @click.prevent="window.location.reload()">Reload this page</a>
    </div>
</div>
