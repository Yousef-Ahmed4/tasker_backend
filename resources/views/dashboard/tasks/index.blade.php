@extends('layouts.dashboard')

@section('title', __('dashboard.tasks_management_title'))
@section('header_title', __('dashboard.tasks_management_header'))

@section('content')
<div class="space-y-8">

    <!-- Top Management Cards: Task Types & Task Statuses -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- 1. Task Types Management Card -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                            🏷️
                        </span>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">{{ __('dashboard.configured_task_types') }}</h3>
                    </div>
                    <button type="button" onclick="document.getElementById('newTypeModal').classList.remove('hidden')"
                        class="px-3 py-1 rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300 text-xs font-bold hover:bg-brand-100 transition-colors">
                        {{ __('dashboard.new_type_btn') }}
                    </button>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">{{ __('dashboard.task_types_sub') }}</p>

                <div class="flex flex-wrap gap-2">
                    @forelse($taskTypes as $tt)
                    <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 flex items-center gap-3">
                        <div>
                            <span class="block text-xs font-bold text-slate-900 dark:text-white">{{ $tt->name }}</span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-[10px] font-mono uppercase font-bold text-brand-600 dark:text-brand-400">{{ $tt->type }}</span>
                                @if($tt->project)
                                <span class="text-[9px] text-slate-400 font-medium">| 📂 {{ $tt->project->name }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                            {{ $tt->tasks_count }}
                        </span>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400">{{ __('dashboard.no_task_types') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 2. Task Statuses Management Card -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs">
                            ⚙️
                        </span>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">{{ __('dashboard.workflow_statuses') }}</h3>
                    </div>
                    <button type="button" onclick="document.getElementById('newStatusModal').classList.remove('hidden')"
                        class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300 text-xs font-bold hover:bg-emerald-100 transition-colors">
                        {{ __('dashboard.new_status_btn') }}
                    </button>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">{{ __('dashboard.task_statuses_sub') }}</p>

                <div class="flex flex-wrap gap-2">
                    @forelse($taskStatuses as $ts)
                    <div class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 flex items-center gap-3">
                        <div>
                            <span class="block text-xs font-bold text-slate-900 dark:text-white">{{ $ts->name }}</span>
                            <span class="block text-[10px] font-bold uppercase {{ $ts->stage === 'completed' ? 'text-emerald-500' : ($ts->stage === 'working' ? 'text-amber-500' : 'text-slate-400') }}">
                                {{ $ts->stage }}
                            </span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                            {{ $ts->tasks_count }}
                        </span>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400">{{ __('dashboard.no_task_statuses') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Filter & List -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">{{ $isTrashed ? __('dashboard.deleted_tasks') : __('dashboard.tasks_directory') }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.showing_tasks_matching', ['count' => $tasks->total()]) }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Active / Trashed Filter Tabs -->
                <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1 text-xs font-bold">
                    <a href="{{ route('dashboard.tasks') }}"
                        class="px-3 py-1.5 rounded-lg transition-all {{ !$isTrashed ? 'bg-white dark:bg-slate-900 text-brand-600 dark:text-brand-300 shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                        {{ __('dashboard.active_tasks') }} ({{ $activeCount }})
                    </a>
                    <a href="{{ route('dashboard.tasks', ['trashed' => 1]) }}"
                        class="px-3 py-1.5 rounded-lg transition-all {{ $isTrashed ? 'bg-white dark:bg-slate-900 text-rose-600 dark:text-rose-400 shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                        {{ __('dashboard.deleted_tasks') }} ({{ $trashedCount }})
                    </a>
                </div>

                <!-- Inline Filters Form -->
                <form action="{{ route('dashboard.tasks') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    @if($isTrashed)
                    <input type="hidden" name="trashed" value="1">
                    @endif
                    <select name="workspace_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200">
                        <option value="">{{ __('dashboard.all_workspaces') }}</option>
                        @foreach($workspaces as $w)
                        <option value="{{ $w->id }}" {{ request('workspace_id') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                        @endforeach
                    </select>

                    <select name="task_type_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200">
                        <option value="">{{ __('dashboard.all_types') }}</option>
                        @foreach($taskTypes as $t)
                        <option value="{{ $t->id }}" {{ request('task_type_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>

                    <select name="priority" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200">
                        <option value="">{{ __('dashboard.all_priorities') }}</option>
                        <option value="Urgent" {{ request('priority') === 'Urgent' ? 'selected' : '' }}>{{ __('dashboard.urgent') }}</option>
                        <option value="High" {{ request('priority') === 'High' ? 'selected' : '' }}>{{ __('dashboard.high') }}</option>
                        <option value="Normal" {{ request('priority') === 'Normal' ? 'selected' : '' }}>{{ __('dashboard.normal') }}</option>
                        <option value="Low" {{ request('priority') === 'Low' ? 'selected' : '' }}>{{ __('dashboard.low') }}</option>
                    </select>

                    @if(request()->hasAny(['workspace_id', 'task_type_id', 'priority']))
                    <a href="{{ route('dashboard.tasks', $isTrashed ? ['trashed' => 1] : []) }}" class="px-2.5 py-1.5 text-xs text-rose-600 font-bold hover:underline">
                        {{ __('dashboard.reset_filter') }}
                    </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left rtl:text-right text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">{{ __('dashboard.title_col') }}</th>
                            <th class="px-6 py-4">{{ __('dashboard.status_col') }}</th>
                            <th class="px-6 py-4">{{ __('dashboard.priority_col') }}</th>
                            <th class="px-6 py-4">{{ __('dashboard.task_type_col_header') }}</th>
                            <th class="px-6 py-4">{{ __('dashboard.workspace_col_header') }}</th>
                            <th class="px-6 py-4">{{ __('dashboard.creator_fixed_col') }}</th>
                            <th class="px-6 py-4">{{ __('dashboard.duration_col') }}</th>
                            <th class="px-6 py-4 text-right rtl:text-left">{{ __('dashboard.actions_col') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($tasks as $task)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $task->title }}</span>
                                <span class="text-[11px] text-slate-400">{{ __('dashboard.position_num', ['pos' => $task->position]) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $stageColor = match($task->status?->stage) {
                                        'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
                                        'working'   => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
                                        default     => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $stageColor }}">
                                    {{ $task->status?->name ?? 'Todo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $pColor = match($task->priority) {
                                        'Urgent' => 'text-rose-600 font-extrabold',
                                        'High'   => 'text-orange-500 font-bold',
                                        'Normal' => 'text-blue-500 font-medium',
                                        default  => 'text-slate-400'
                                    };
                                @endphp
                                <span class="text-xs {{ $pColor }}">{{ $task->priority }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($task->taskType)
                                <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                    {{ $task->taskType->name }} ({{ $task->taskType->type }})
                                </span>
                                @else
                                <span class="text-xs text-slate-400 italic">{{ __('dashboard.none') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ $task->workspace?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500 space-y-0.5">
                                <div>{{ __('dashboard.created_by_label') }} <strong class="text-slate-800 dark:text-slate-200">{{ $task->creator?->name ?? 'System' }}</strong></div>
                                <div>{{ __('dashboard.fixed_by_label') }} <strong class="text-emerald-600 dark:text-emerald-400">{{ $task->fixedBy?->name ?? __('dashboard.unassigned') }}</strong></div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $task->actual_minutes ? $task->actual_minutes . ' ' . __('dashboard.mins') : '—' }}
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-4 text-right rtl:text-left">
                                <div class="flex items-center justify-end gap-2">
                                    @if(!$isTrashed)
                                    <form action="{{ route('dashboard.tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('{{ __('dashboard.confirm_delete_task') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 hover:bg-rose-100 text-xs font-bold transition-colors">
                                            🗑️ {{ __('dashboard.delete') }}
                                        </button>
                                    </form>
                                    @else
                                    <form action="{{ route('dashboard.tasks.restore', $task->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-300 hover:bg-emerald-100 text-xs font-bold transition-colors flex items-center gap-1.5">
                                            <span>♻️</span>
                                            <span>{{ __('dashboard.restore') }}</span>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400 text-xs font-medium">{{ __('dashboard.no_tasks_found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $tasks->links() }}
            </div>
        </div>
    </div>

    <!-- Create Task Type Modal -->
    <div id="newTypeModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
            <h3 class="font-bold text-base text-slate-900 dark:text-white">{{ __('dashboard.create_new_task_type') }}</h3>
            <form action="{{ route('dashboard.tasks.type.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('dashboard.type_name_label') }}</label>
                    <input type="text" name="name" placeholder="e.g. Wi-Fi Infrastructure" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('dashboard.category_type_label') }}</label>
                    <select name="type" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold">
                        <option value="network">{{ __('dashboard.network') }}</option>
                        <option value="device">{{ __('dashboard.device') }}</option>
                        <option value="focus">{{ __('dashboard.focus') }}</option>
                        <option value="other">{{ __('dashboard.other') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">القسم / المشروع التابع له (Project)</label>
                    <select name="project_id"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold">
                        <option value="">بدون قسم محدد (عام)</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('newTypeModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-500">{{ __('dashboard.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 text-white text-xs font-bold">{{ __('dashboard.create_type_btn') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Task Status Modal -->
    <div id="newStatusModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
            <h3 class="font-bold text-base text-slate-900 dark:text-white">{{ __('dashboard.create_new_task_status') }}</h3>
            <form action="{{ route('dashboard.tasks.status.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('dashboard.status_name_label') }}</label>
                    <input type="text" name="name" placeholder="e.g. Under Review" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('dashboard.stage_label') }}</label>
                    <select name="stage" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold">
                        <option value="pending">{{ __('dashboard.stage_pending') }}</option>
                        <option value="working">{{ __('dashboard.stage_working') }}</option>
                        <option value="completed">{{ __('dashboard.stage_completed') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('dashboard.order_index_label') }}</label>
                    <input type="number" name="order" value="5" min="1" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('newStatusModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-500">{{ __('dashboard.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold">{{ __('dashboard.create_status_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
