@extends('layouts.dashboard')

@section('title', __('dashboard.users_title'))
@section('header_title', __('dashboard.users_header'))

@section('content')
<div class="space-y-6">
    <!-- Top Filter & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">
                {{ $isTrashed ? __('dashboard.deleted_users') : __('dashboard.all_platform_users') }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                إدارة المستخدمين والأقسام (المشاريع) والمواسم (مساحات العمل) والأدوار والمسؤوليات
            </p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Active / Trashed Filter Tabs -->
            <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1 text-xs font-bold">
                <a href="{{ route('dashboard.users') }}"
                    class="px-3 py-1.5 rounded-lg transition-all {{ !$isTrashed ? 'bg-white dark:bg-slate-900 text-brand-600 dark:text-brand-300 shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                    {{ __('dashboard.active_users') }} ({{ $activeCount }})
                </a>
                <a href="{{ route('dashboard.users', ['trashed' => 1]) }}"
                    class="px-3 py-1.5 rounded-lg transition-all {{ $isTrashed ? 'bg-white dark:bg-slate-900 text-rose-600 dark:text-rose-400 shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                    {{ __('dashboard.deleted_users') }} ({{ $trashedCount }})
                </a>
            </div>

            <button type="button" onclick="document.getElementById('newUserModal').classList.remove('hidden')"
                class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20 transition-all flex items-center gap-2">
                <span>➕</span>
                <span>{{ __('dashboard.new_user_btn') }}</span>
            </button>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left rtl:text-right text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-4">{{ __('dashboard.user_col') }}</th>
                        <th class="px-5 py-4">القسم / المشروع</th>
                        <th class="px-5 py-4">المواسم / مساحات العمل</th>
                        <th class="px-5 py-4">{{ __('dashboard.role_col') }}</th>
                        <th class="px-5 py-4">{{ __('dashboard.user_type_col') }}</th>
                        <th class="px-5 py-4">{{ __('dashboard.responsibilities_col') }}</th>
                        <th class="px-5 py-4">{{ __('dashboard.tasks_activity_col') }}</th>
                        <th class="px-5 py-4 text-right rtl:text-left">{{ __('dashboard.actions_col') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($users as $u)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                        <!-- User Name & Email & Code -->
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 text-white font-bold flex items-center justify-center text-xs shadow-sm flex-shrink-0">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 dark:text-white">{{ $u->name }}</span>
                                        @if($u->code)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                            #{{ $u->code }}
                                        </span>
                                        @endif
                                    </div>
                                    <span class="block text-xs text-slate-400">{{ $u->email }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Department / Project -->
                        <td class="px-5 py-4">
                            @if($u->project)
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                📂 {{ $u->project->name }}
                            </span>
                            @else
                            <span class="text-xs text-slate-400 italic">—</span>
                            @endif
                        </td>

                        <!-- Workspaces / المواسم -->
                        <td class="px-5 py-4">
                            @if($u->workspaces->count() > 0)
                            <div class="flex flex-wrap gap-1 max-w-[220px]">
                                @foreach($u->workspaces as $ws)
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700" title="الدور: {{ $ws->pivot->role }}">
                                    🏢 {{ $ws->name }}
                                    <span class="text-[9px] font-mono opacity-70">({{ $ws->pivot->role }})</span>
                                </span>
                                @endforeach
                            </div>
                            @else
                            <span class="text-xs text-slate-400 italic">لا توجد مواسم مسندة</span>
                            @endif
                        </td>

                        <!-- Role -->
                        <td class="px-5 py-4">
                            @foreach($u->roles as $role)
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $role->name === 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ ucfirst($role->name) }}
                            </span>
                            @endforeach
                        </td>

                        <!-- User Type -->
                        <td class="px-5 py-4">
                            @if($u->userType)
                            <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-brand-50 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300 border border-brand-200 dark:border-brand-800 uppercase">
                                {{ $u->userType->type }}
                            </span>
                            @else
                            <span class="text-xs text-slate-400 italic">{{ __('dashboard.none') }}</span>
                            @endif
                        </td>

                        <!-- Responsibilities Array -->
                        <td class="px-5 py-4">
                            @if($u->userType && !empty($u->userType->respnsapity))
                            <div class="flex flex-wrap gap-1 max-w-[180px]">
                                @foreach($u->userType->respnsapity as $resp)
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    {{ $resp }}
                                </span>
                                @endforeach
                            </div>
                            @else
                            <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>

                        <!-- Activity -->
                        <td class="px-5 py-4">
                            <div class="text-xs text-slate-600 dark:text-slate-300 space-y-0.5">
                                <div>{{ __('dashboard.created_count') }} <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $u->created_tasks_count }}</span></div>
                                <div>{{ __('dashboard.fixed_count') }} <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $u->fixed_tasks_count }}</span></div>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="px-5 py-4 text-right rtl:text-left">
                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                @if(!$isTrashed)
                                <!-- Edit Full User Details -->
                                <button type="button"
                                    onclick="openEditUserModal({{ json_encode([
                                        'id' => $u->id,
                                        'name' => $u->name,
                                        'email' => $u->email,
                                        'code' => $u->code,
                                        'project_id' => $u->project_id,
                                        'role' => $u->roles->first()?->name ?? 'user',
                                        'type' => $u->userType?->type ?? 'normal',
                                        'respnsapity' => implode(', ', (array)($u->userType?->respnsapity ?? [])),
                                        'workspaces' => $u->workspaces->pluck('pivot.role', 'id')->toArray()
                                    ]) }})"
                                    class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/50 text-xs font-bold transition-colors flex items-center gap-1"
                                    title="تعديل المستخدم بالكامل">
                                    <span>✏️</span>
                                    <span>تعديل</span>
                                </button>

                                <form action="{{ route('dashboard.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('{{ __('dashboard.confirm_delete_user') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1.5 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 hover:bg-rose-100 text-xs font-bold transition-colors">
                                        🗑️
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('dashboard.users.restore', $u->id) }}" method="POST">
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
                        <td colspan="8" class="px-6 py-8 text-center text-slate-400 text-xs font-medium">{{ __('dashboard.no_users_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="newUserModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-xl w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 my-8">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="font-extrabold text-base text-slate-900 dark:text-white">{{ __('dashboard.new_user_btn') }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5">إضافة مستخدم جديد وتحديد القسم (المشروع) والمواسم (مساحات العمل) والصلاحيات</p>
                </div>
                <button type="button" onclick="document.getElementById('newUserModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    ✕
                </button>
            </div>

            <form action="{{ route('dashboard.users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="أحمد محمد"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('dashboard.email_address') }} <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" required placeholder="user@domain.com"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('dashboard.password') }} <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required placeholder="على الأقل 8 أحرف"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('dashboard.code') }} (كود الموظف)</label>
                        <input type="text" name="code" placeholder="مثال: EMP-101"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Project (القسم) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                            القسم / المشروع الأساسي <span class="text-rose-500">*</span>
                        </label>
                        <select name="project_id" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-brand-500">
                            <option value="">-- اختر القسم --</option>
                            @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">الدور والصلاحية</label>
                        <select name="role"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-brand-500">
                            @foreach($roles as $r)
                            <option value="{{ $r->name }}">{{ ucfirst($r->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- User Type -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">نوع المستخدم الوظيفي</label>
                        <select name="type"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-brand-500">
                            <option value="normal">مستخدم عادي (Normal)</option>
                            <option value="admin">مسؤول (Admin)</option>
                            <option value="it">دعم فني (IT)</option>
                            <option value="manager">مدير (Manager)</option>
                            <option value="technician">فني (Technician)</option>
                        </select>
                    </div>

                    <!-- Responsibilities -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">المسؤوليات التخصصية</label>
                        <input type="text" name="respnsapity" placeholder="مثال: network, device, focus"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <!-- Workspaces (المواسم) Selection -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <label class="block text-xs font-extrabold text-slate-700 dark:text-slate-300 mb-1.5">
                        المواسم / مساحات العمل المسندة (Workspaces)
                    </label>
                    <p class="text-[11px] text-slate-400 mb-2">حدد المواسم التي يمكن للمستخدم الوصول إليها مع دوره في كل موسم:</p>
                    <div class="max-h-40 overflow-y-auto space-y-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        @foreach($workspaces as $ws)
                        <div class="flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700/50">
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-800 dark:text-slate-200">
                                <input type="checkbox" name="workspaces[]" value="{{ $ws->id }}" class="rounded text-brand-600 focus:ring-brand-500">
                                <span>{{ $ws->name }}</span>
                                <span class="text-[10px] text-slate-400">({{ $ws->project?->name ?? 'بدون قسم' }})</span>
                            </label>
                            <select name="workspace_roles[{{ $ws->id }}]" class="text-[11px] py-1 px-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                <option value="viewer">مشاهد (viewer)</option>
                                <option value="editor">محرر (editor)</option>
                                <option value="creator">منشئ (creator)</option>
                                <option value="admin">مسؤول موسم (admin)</option>
                                <option value="it">فني (it)</option>
                            </select>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="document.getElementById('newUserModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-400">
                        {{ __('dashboard.cancel') }}
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20">
                        إضافة المستخدم
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-xl w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 my-8">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="font-extrabold text-base text-slate-900 dark:text-white" id="editModalTitle">تعديل بيانات المستخدم</h3>
                    <p class="text-xs text-slate-400 mt-0.5">تعديل بيانات المستخدم والقسم (المشروع) والمواسم والمسؤوليات</p>
                </div>
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    ✕
                </button>
            </div>

            <form id="editUserForm" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="editName" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('dashboard.email_address') }} <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" id="editEmail" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">كلمة المرور الجديدة (اتركها فارغة للتخطي)</label>
                        <input type="password" name="password" placeholder="اترك فارغاً للإبقاء على الحالية"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('dashboard.code') }} (كود الموظف)</label>
                        <input type="text" name="code" id="editCode" placeholder="مثال: EMP-101"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Project (القسم) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                            القسم / المشروع الأساسي <span class="text-rose-500">*</span>
                        </label>
                        <select name="project_id" id="editProjectId" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-brand-500">
                            <option value="">-- اختر القسم --</option>
                            @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">الدور والصلاحية</label>
                        <select name="role" id="editRole"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-brand-500">
                            @foreach($roles as $r)
                            <option value="{{ $r->name }}">{{ ucfirst($r->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- User Type -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">نوع المستخدم الوظيفي</label>
                        <select name="type" id="editType"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-brand-500">
                            <option value="normal">مستخدم عادي (Normal)</option>
                            <option value="admin">مسؤول (Admin)</option>
                            <option value="it">دعم فني (IT)</option>
                            <option value="manager">مدير (Manager)</option>
                            <option value="technician">فني (Technician)</option>
                        </select>
                    </div>

                    <!-- Responsibilities -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">المسؤوليات التخصصية</label>
                        <input type="text" name="respnsapity" id="editRespnsapity" placeholder="مثال: network, device, focus"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <!-- Workspaces (المواسم) Selection -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <label class="block text-xs font-extrabold text-slate-700 dark:text-slate-300 mb-1.5">
                        المواسم / مساحات العمل المسندة (Workspaces)
                    </label>
                    <p class="text-[11px] text-slate-400 mb-2">تعديل المواسم المسندة لهذا المستخدم ودوره في كل موسم:</p>
                    <div class="max-h-40 overflow-y-auto space-y-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700" id="editWorkspacesList">
                        @foreach($workspaces as $ws)
                        <div class="flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700/50">
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-800 dark:text-slate-200">
                                <input type="checkbox" name="workspaces[]" value="{{ $ws->id }}" id="edit_ws_{{ $ws->id }}" class="rounded text-brand-600 focus:ring-brand-500">
                                <span>{{ $ws->name }}</span>
                                <span class="text-[10px] text-slate-400">({{ $ws->project?->name ?? 'بدون قسم' }})</span>
                            </label>
                            <select name="workspace_roles[{{ $ws->id }}]" id="edit_ws_role_{{ $ws->id }}" class="text-[11px] py-1 px-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                <option value="viewer">مشاهد (viewer)</option>
                                <option value="editor">محرر (editor)</option>
                                <option value="creator">منشئ (creator)</option>
                                <option value="admin">مسؤول موسم (admin)</option>
                                <option value="it">فني (it)</option>
                            </select>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-400">
                        {{ __('dashboard.cancel') }}
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20">
                        حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditUserModal(user) {
        document.getElementById('editModalTitle').innerText = 'تعديل بيانات المستخدم: ' + user.name;
        document.getElementById('editName').value = user.name || '';
        document.getElementById('editEmail').value = user.email || '';
        document.getElementById('editCode').value = user.code || '';
        document.getElementById('editProjectId').value = user.project_id || '';
        document.getElementById('editRole').value = user.role || 'user';
        document.getElementById('editType').value = user.type || 'normal';
        document.getElementById('editRespnsapity').value = user.respnsapity || '';

        // Reset all workspace checkboxes & roles
        @foreach($workspaces as $ws)
            const chk_{{ $ws->id }} = document.getElementById('edit_ws_{{ $ws->id }}');
            const roleSel_{{ $ws->id }} = document.getElementById('edit_ws_role_{{ $ws->id }}');
            if (chk_{{ $ws->id }}) chk_{{ $ws->id }}.checked = false;
            if (roleSel_{{ $ws->id }}) roleSel_{{ $ws->id }}.value = 'viewer';
        @endforeach

        // Set user's assigned workspaces
        if (user.workspaces) {
            Object.keys(user.workspaces).forEach(function(wsId) {
                const chk = document.getElementById('edit_ws_' + wsId);
                const roleSel = document.getElementById('edit_ws_role_' + wsId);
                if (chk) chk.checked = true;
                if (roleSel) roleSel.value = user.workspaces[wsId] || 'viewer';
            });
        }

        document.getElementById('editUserForm').action = '/dashboard/users/' + user.id + '/update';
        document.getElementById('editUserModal').classList.remove('hidden');
    }
</script>
@endpush
