@php
    $staff = auth()->user()->role === \App\Enums\UserRole::Staff;
    $workspace = ! $staff || auth()->user()->isAssignedStaff();
    $primary = [
        ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Overview', 'icon' => 'overview', 'ability' => null],
        ['route' => $staff ? 'employees.show' : 'employees.index', 'pattern' => 'employees.*', 'label' => $staff ? 'My readiness' : 'People', 'icon' => 'people', 'ability' => 'view-employees'],
        ['route' => 'qualification-matrix', 'pattern' => 'qualification-matrix', 'label' => 'Qualification matrix', 'icon' => 'matrix', 'ability' => 'view-employees'],
        ['route' => 'credentials.index', 'pattern' => 'credentials.*', 'label' => $staff ? 'My documents' : 'Documents', 'icon' => 'document', 'ability' => 'view-credentials'],
    ];
@endphp
<nav aria-label="Main navigation" class="flex h-full flex-col gap-7 px-4 py-5">
    @if($workspace)
        <div>
            <p class="nav-group-label">Workspace</p>
            <ul class="mt-2 space-y-1">
                @foreach($primary as $item)
                    @if(! $item['ability'] || auth()->user()->can($item['ability']))
                        <li><a href="{{ $item['route'] === 'employees.show' ? route($item['route'], auth()->user()) : route($item['route']) }}" class="nav-link {{ request()->routeIs($item['pattern']) ? 'nav-link-active' : '' }}" @if(request()->routeIs($item['pattern'])) aria-current="page" @endif><x-icon :name="$item['icon']" />{{ $item['label'] }}</a></li>
                    @endif
                @endforeach
            </ul>
        </div>
        @can('view-training')
            <div>
                <p class="nav-group-label">Training</p>
                <ul class="mt-2 space-y-1">
                    <li><a href="{{ route('training-courses.index') }}" class="nav-link {{ request()->routeIs('training-courses.*') ? 'nav-link-active' : '' }}" @if(request()->routeIs('training-courses.*')) aria-current="page" @endif><x-icon name="training" />Course catalog</a></li>
                    <li><a href="{{ route('training-enrollments.index') }}" class="nav-link {{ request()->routeIs('training-enrollments.*') ? 'nav-link-active' : '' }}" @if(request()->routeIs('training-enrollments.*')) aria-current="page" @endif><x-icon name="check" />Training records</a></li>
                </ul>
            </div>
        @endcan
        @can('view-audits')
            <div>
                <p class="nav-group-label">Follow-up & reports</p>
                <ul class="mt-2 space-y-1">
                    @foreach(['audits' => 'Audits', 'findings' => 'Corrective work', 'reports' => 'Reports'] as $section => $label)
                        <li><a href="{{ route($section.'.index') }}" class="nav-link {{ request()->routeIs($section.'.*') ? 'nav-link-active' : '' }}" @if(request()->routeIs($section.'.*')) aria-current="page" @endif><x-icon :name="$section === 'findings' ? 'check' : 'document'" />{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endcan
        @can('manage-requirements')
            <details class="navigation-settings" @if(request()->routeIs('admin.*', 'regions.*', 'facilities.*', 'job-roles.*', 'qualifications.*', 'safety-domains.*')) open @endif>
                <summary class="nav-link cursor-pointer"><x-icon name="settings" />Administration<x-icon name="chevron" class="ml-auto size-4" /></summary>
                <ul class="mt-2 space-y-1 pl-7">
                    @can('manage-users')<li><a class="nav-sub-link" href="{{ route('admin.users') }}" @if(request()->routeIs('admin.*')) aria-current="page" @endif>Accounts & access</a></li>@endcan
                    <li><a class="nav-sub-link" href="{{ route('job-roles.index') }}" @if(request()->routeIs('job-roles.*')) aria-current="page" @endif>Jobs & requirements</a></li>
                    @can('manage-organization')
                        @foreach(['regions' => 'Regions', 'facilities' => 'Facilities', 'safety-domains' => 'Safety domains', 'qualifications' => 'Qualifications'] as $route => $label)
                            <li><a class="nav-sub-link" href="{{ route($route.'.index') }}" @if(request()->routeIs($route.'.*')) aria-current="page" @endif>{{ $label }}</a></li>
                        @endforeach
                    @endcan
                </ul>
            </details>
        @endcan
    @else
        <a href="{{ route('onboarding') }}" class="nav-link nav-link-active"><x-icon name="people" />Account setup</a>
    @endif
    <div class="mt-auto px-3 pt-6 text-xs leading-relaxed text-slate-500">Safety compliance,<br>one clear step at a time.</div>
</nav>
