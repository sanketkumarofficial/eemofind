<header class="app-header">
    <form class="search" method="get">
        <i class="bi bi-search"></i>
        <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search users, devices, groups, tickets">
    </form>
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.settings.edit', 'theme') }}"><i class="bi bi-circle-half"></i></a>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown"><i class="bi bi-bell"></i></button>
            <div class="dropdown-menu dropdown-menu-end p-2"><span class="dropdown-item-text">No unread notifications</span></div>
        </div>
        <div class="dropdown">
            <button class="btn btn-teal btn-sm" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i></button>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="{{ route('admin.settings.edit', 'general') }}">Settings</a>
                <a class="dropdown-item" href="{{ route('admin.activity-logs.index') }}">Activity</a>
            </div>
        </div>
    </div>
</header>