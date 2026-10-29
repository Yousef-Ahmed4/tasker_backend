@extends('layouts.dashboard')

@section('title', 'إدارة الأقسام والمواسم - لوحة التحكم')
@section('header_title', 'إدارة الأقسام (المشاريع) والمواسم (مساحات العمل)')

@section('content')
<div class="space-y-10">
    <!-- 1. Projects / Departments Section (الأقسام والمشاريع) -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl">📂</span>
                    <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">المشاريع النشطة والأقسام الرئيسية (Active Projects)</h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    الأقسام التي ينتمي إليها المستخدمون وتحتوي على المواسم ومساحات العمل
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300">
                    {{ $projects->count() }} قسم
                </span>
                <button type="button" onclick="document.getElementById('newProjectModal').classList.remove('hidden')"
                    class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20 transition-all flex items-center gap-1.5">
                    <span>➕</span>
                    <span>إضافة قسم جديد</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($projects as $proj)
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between hover:border-brand-500/50 transition-all group">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                                📂
                            </span>
                            <span class="text-xs font-semibold text-slate-400">{{ $proj->creator?->name ?? 'System' }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button"
                                onclick="openEditProjectModal({{ json_encode(['id' => $proj->id, 'name' => $proj->name, 'description' => $proj->description]) }})"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                title="تعديل بيانات القسم">
                                ✏️
                            </button>
                            <form action="{{ route('dashboard.projects.destroy', $proj->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا القسم؟ سيتم حذف جميع المواسم والمهام التابعة له!')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="حذف القسم">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>
                    <h3 class="font-extrabold text-base text-slate-900 dark:text-white">{{ $proj->name }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                        {{ $proj->description ?: 'لا يوجد وصف مدخل لهذا القسم.' }}
                    </p>
                </div>

                <div class="mt-5 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs font-semibold text-slate-500">
                    <div>المواسم: <span class="font-extrabold text-slate-800 dark:text-slate-200">{{ $proj->workspaces_count }}</span></div>
                    <div>المهام: <span class="font-extrabold text-brand-600 dark:text-brand-400">{{ $proj->tasks_count }}</span></div>
                </div>
            </div>
            @empty
            <div class="col-span-3 p-8 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs text-slate-400">
                لا توجد أقسام مسجلة حتى الآن. انقر على "إضافة قسم جديد" للبدء.
            </div>
            @endforelse
        </div>
    </div>

    <!-- 2. Workspaces / Seasons Section (المواسم ومساحات العمل) -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl">🏢</span>
                    <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">مساحات العمل المعينة والمواسم (Workspaces & Seasons)</h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    إدارة المواسم وتعيين المستخدمين وأدوارهم وربط كل موسم بقسمه المحدد
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300">
                    {{ $workspaces->total() }} موسم
                </span>
                <button type="button" onclick="document.getElementById('newWorkspaceModal').classList.remove('hidden')"
                    class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20 transition-all flex items-center gap-1.5">
                    <span>➕</span>
                    <span>إضافة موسم جديد</span>
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left rtl:text-right text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">الموسم / مساحة العمل</th>
                            <th class="px-6 py-4">المشروع التابع له / القسم</th>
                            <th class="px-6 py-4">الأعضاء المعينون</th>
                            <th class="px-6 py-4">عدد المهام</th>
                            <th class="px-6 py-4">المنشئ</th>
                            <th class="px-6 py-4 text-right rtl:text-left">الإجراءات والتحكم</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($workspaces as $ws)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $ws->name }}</div>
                                @if($ws->description)
                                <div class="text-[11px] text-slate-400 line-clamp-1 mt-0.5">{{ $ws->description }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-800/50">
                                    📂 {{ $ws->project?->name ?? 'بدون قسم' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center -space-x-2 rtl:space-x-reverse overflow-hidden">
                                    @foreach($ws->users->take(5) as $m)
                                    <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 border-2 border-white dark:border-slate-900 flex items-center justify-center text-[10px] font-bold text-slate-700 dark:text-slate-300" title="{{ $m->name }} ({{ $m->pivot->role }})">
                                        {{ strtoupper(substr($m->name, 0, 1)) }}
                                    </div>
                                    @endforeach
                                    @if($ws->users->count() > 5)
                                    <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 border-2 border-white dark:border-slate-900 flex items-center justify-center text-[10px] font-bold text-slate-400">
                                        +{{ $ws->users->count() - 5 }}
                                    </div>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-1">({{ $ws->users->count() }} عضو)</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-extrabold text-brand-600 dark:text-brand-400">{{ $ws->tasks_count }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $ws->creator?->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 text-right rtl:text-left">
                                <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                    <!-- Edit Workspace -->
                                    <button type="button"
                                        onclick="openEditWorkspaceModal({{ json_encode([
                                            'id' => $ws->id,
                                            'name' => $ws->name,
                                            'project_id' => $ws->project_id,
                                            'description' => $ws->description,
                                            'users' => $ws->users->pluck('pivot.role', 'id')->toArray()
                                        ]) }})"
                                        class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-colors flex items-center gap-1"
                                        title="تعديل الموسم وتعيين الأعضاء">
                                        <span>✏️</span>
                                        <span>تعديل</span>
                                    </button>

                                    <!-- Delete Workspace -->
                                    <form action="{{ route('dashboard.workspaces.destroy', $ws->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الموسم؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 hover:bg-rose-100 text-xs font-bold transition-colors" title="حذف الموسم">
                                            🗑️
                                        </button>
                                    </form>

                                    <!-- View Tasks Link -->
                                    <a href="{{ route('dashboard.tasks', ['workspace_id' => $ws->id]) }}" class="px-2.5 py-1.5 rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300 text-xs font-bold transition-colors">
                                        المهام &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400 text-xs font-medium">
                                لا توجد مواسم / مساحات عمل مسجلة حتى الآن.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $workspaces->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: New Project (إضافة قسم جديد) -->
<div id="newProjectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="font-extrabold text-base text-slate-900 dark:text-white">إضافة قسم / مشروع جديد</h3>
                <p class="text-xs text-slate-400 mt-0.5">إنشاء قسم رئيسي لربط المستخدمين والمواسم</p>
            </div>
            <button type="button" onclick="document.getElementById('newProjectModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                ✕
            </button>
        </div>

        <form action="{{ route('dashboard.projects.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">اسم القسم / المشروع <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="مثال: قسم تقنية المعلومات"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">الوصف</label>
                <textarea name="description" rows="3" placeholder="وصف مهام ومسؤوليات هذا القسم..."
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('newProjectModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-400">
                    {{ __('dashboard.cancel') }}
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20">
                    حفظ القسم
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Project (تعديل قسم) -->
<div id="editProjectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="font-extrabold text-base text-slate-900 dark:text-white" id="editProjectTitle">تعديل بيانات القسم</h3>
                <p class="text-xs text-slate-400 mt-0.5">تحديث اسم ووصف القسم</p>
            </div>
            <button type="button" onclick="document.getElementById('editProjectModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                ✕
            </button>
        </div>

        <form id="editProjectForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">اسم القسم / المشروع <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="editProjectName" required
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">الوصف</label>
                <textarea name="description" id="editProjectDescription" rows="3"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('editProjectModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-400">
                    {{ __('dashboard.cancel') }}
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20">
                    تحديث القسم
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: New Workspace (إضافة موسم / مساحة عمل جديدة) -->
<div id="newWorkspaceModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-xl w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="font-extrabold text-base text-slate-900 dark:text-white">إضافة موسم / مساحة عمل جديدة</h3>
                <p class="text-xs text-slate-400 mt-0.5">إنشاء مساحة عمل موسمية وربطها بالقسم والأعضاء</p>
            </div>
            <button type="button" onclick="document.getElementById('newWorkspaceModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                ✕
            </button>
        </div>

        <form action="{{ route('dashboard.workspaces.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">اسم الموسم / مساحة العمل <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required 
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">القسم / المشروع التابع له <span class="text-rose-500">*</span></label>
                    <select name="project_id" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-brand-500">
                        <option value="">-- اختر القسم --</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">الوصف</label>
                <textarea name="description" rows="2" placeholder="وصف الموسم أو بيئة العمل..."
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500"></textarea>
            </div>

            <!-- Assign Users to Workspace -->
            <div>
                <label class="block text-xs font-extrabold text-slate-700 dark:text-slate-300 mb-1">
                    الأعضاء المعينون في هذا الموسم
                </label>
                <p class="text-[11px] text-slate-400 mb-2">اختر المستخدمين الذين يتبعون هذا الموسم وحدد دور كل منهم:</p>
                <div class="max-h-48 overflow-y-auto space-y-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                    @foreach($users as $user)
                    <div class="flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700/50">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-800 dark:text-slate-200">
                            <input type="checkbox" name="users[]" value="{{ $user->id }}" class="rounded text-brand-600 focus:ring-brand-500">
                            <span>{{ $user->name }}</span>
                            <span class="text-[10px] text-slate-400">({{ $user->email }})</span>
                        </label>
                        <select name="roles[{{ $user->id }}]" class="text-[11px] py-1 px-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300">
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
                <button type="button" onclick="document.getElementById('newWorkspaceModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-400">
                    {{ __('dashboard.cancel') }}
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20">
                    حفظ الموسم
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 4: Edit Workspace (تعديل موسم / مساحة عمل) -->
<div id="editWorkspaceModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-xl w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="font-extrabold text-base text-slate-900 dark:text-white" id="editWorkspaceTitle">تعديل الموسم / مساحة العمل</h3>
                <p class="text-xs text-slate-400 mt-0.5">تحديث بيانات الموسم وإدارة الأعضاء والصلاحيات</p>
            </div>
            <button type="button" onclick="document.getElementById('editWorkspaceModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                ✕
            </button>
        </div>

        <form id="editWorkspaceForm" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">اسم الموسم / مساحة العمل <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="editWorkspaceName" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">القسم / المشروع التابع له <span class="text-rose-500">*</span></label>
                    <select name="project_id" id="editWorkspaceProjectId" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-brand-500">
                        <option value="">-- اختر القسم --</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">الوصف</label>
                <textarea name="description" id="editWorkspaceDescription" rows="2"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-brand-500"></textarea>
            </div>

            <!-- Assign Users to Workspace -->
            <div>
                <label class="block text-xs font-extrabold text-slate-700 dark:text-slate-300 mb-1">
                    الأعضاء المعينون في هذا الموسم
                </label>
                <p class="text-[11px] text-slate-400 mb-2">تعديل المستخدمين المسندين وأدوارهم:</p>
                <div class="max-h-48 overflow-y-auto space-y-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                    @foreach($users as $user)
                    <div class="flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700/50">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-800 dark:text-slate-200">
                            <input type="checkbox" name="users[]" value="{{ $user->id }}" id="edit_ws_user_{{ $user->id }}" class="rounded text-brand-600 focus:ring-brand-500">
                            <span>{{ $user->name }}</span>
                            <span class="text-[10px] text-slate-400">({{ $user->email }})</span>
                        </label>
                        <select name="roles[{{ $user->id }}]" id="edit_ws_user_role_{{ $user->id }}" class="text-[11px] py-1 px-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300">
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
                <button type="button" onclick="document.getElementById('editWorkspaceModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-400">
                    {{ __('dashboard.cancel') }}
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20">
                    تحديث بيانات الموسم
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditProjectModal(project) {
        document.getElementById('editProjectTitle').innerText = 'تعديل القسم: ' + project.name;
        document.getElementById('editProjectName').value = project.name || '';
        document.getElementById('editProjectDescription').value = project.description || '';
        document.getElementById('editProjectForm').action = '/dashboard/projects/' + project.id + '/update';
        document.getElementById('editProjectModal').classList.remove('hidden');
    }

    function openEditWorkspaceModal(ws) {
        document.getElementById('editWorkspaceTitle').innerText = 'تعديل الموسم: ' + ws.name;
        document.getElementById('editWorkspaceName').value = ws.name || '';
        document.getElementById('editWorkspaceProjectId').value = ws.project_id || '';
        document.getElementById('editWorkspaceDescription').value = ws.description || '';

        // Reset all user checkboxes & roles
        @foreach($users as $u)
            const chk_{{ $u->id }} = document.getElementById('edit_ws_user_{{ $u->id }}');
            const roleSel_{{ $u->id }} = document.getElementById('edit_ws_user_role_{{ $u->id }}');
            if (chk_{{ $u->id }}) chk_{{ $u->id }}.checked = false;
            if (roleSel_{{ $u->id }}) roleSel_{{ $u->id }}.value = 'viewer';
        @endforeach

        // Set workspace's assigned users
        if (ws.users) {
            Object.keys(ws.users).forEach(function(uId) {
                const chk = document.getElementById('edit_ws_user_' + uId);
                const roleSel = document.getElementById('edit_ws_user_role_' + uId);
                if (chk) chk.checked = true;
                if (roleSel) roleSel.value = ws.users[uId] || 'viewer';
            });
        }

        document.getElementById('editWorkspaceForm').action = '/dashboard/workspaces/' + ws.id + '/update';
        document.getElementById('editWorkspaceModal').classList.remove('hidden');
    }
</script>
@endpush
