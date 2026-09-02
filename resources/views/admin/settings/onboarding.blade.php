@extends('layouts.admin')

@section('title', 'Onboarding Slides Settings')
@section('heading', 'Onboarding Slides')
@section('subheading', 'Manage dynamic mobile app first-launch onboarding slides')

@section('content')
<div class="space-y-6">
    {{-- Top Action Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <div>
            <h3 class="text-base font-bold text-slate-800">Mobile App Onboarding Screens</h3>
            <p class="text-xs text-slate-400 mt-0.5">Slides created here are delivered dynamically to mobile apps via <code class="text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded font-mono">GET /api/v1/onboarding</code> and <code class="text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded font-mono">GET /api/v1/app-config</code>.</p>
        </div>
        <button onclick="document.getElementById('addSlideModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white font-semibold text-sm rounded-xl shadow-sm hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Slide
        </button>
    </div>

    {{-- Slides List Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($slides as $slide)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col justify-between">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono {{ $slide->is_active ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-400' }}">
                        Slide #{{ $slide->sort_order }} • {{ $slide->is_active ? 'Active' : 'Disabled' }}
                    </span>
                </div>

                @if($slide->image_url)
                <div class="h-40 bg-slate-50 rounded-xl overflow-hidden flex items-center justify-center border border-slate-100">
                    <img src="{{ $slide->image_url }}" alt="{{ $slide->title }}" class="h-full w-full object-contain p-2">
                </div>
                @else
                <div class="h-40 bg-indigo-50/50 rounded-xl border border-dashed border-indigo-200 flex flex-col items-center justify-center text-indigo-400 space-y-2">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span class="text-xs font-medium text-indigo-600">Vector App Illustration</span>
                </div>
                @endif

                <div>
                    <h4 class="text-base font-bold text-slate-800">{{ $slide->title }}</h4>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $slide->description }}</p>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                <button onclick="editSlide({{ json_encode($slide) }})"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Slide
                </button>

                <form method="POST" action="{{ route('admin.settings.onboarding.destroy', $slide->id) }}" onsubmit="return confirm('Are you sure you want to delete this slide?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-rose-500 hover:text-rose-700 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="md:col-span-3 bg-white p-12 text-center rounded-2xl border border-slate-100">
            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <p class="text-sm font-semibold text-slate-700">No onboarding slides found</p>
            <p class="text-xs text-slate-400 mt-1">Click "Add New Slide" above to create your first mobile onboarding slide.</p>
        </div>
        @endforelse
    </div>
</div>

{{-- Add Slide Modal --}}
<div id="addSlideModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-800">Add New Onboarding Slide</h3>
            <button onclick="document.getElementById('addSlideModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.settings.onboarding.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Slide Title</label>
                <input type="text" name="title" required placeholder="e.g. Instant Airtime & Data"
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Description / Subtitle</label>
                <textarea name="description" rows="3" required placeholder="Write a short explanatory message for mobile users..."
                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="1" min="1" required
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                    <select name="is_active" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="1">Active</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Illustration / Banner Graphic <span class="text-slate-400 font-normal">(Optional)</span></label>
                <input type="file" name="image" accept="image/*" class="w-full text-xs border border-slate-200 rounded-lg p-2 bg-slate-50">
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addSlideModal').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">Save Slide</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Slide Modal --}}
<div id="editSlideModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-800">Edit Onboarding Slide</h3>
            <button onclick="document.getElementById('editSlideModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <form id="editSlideForm" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Slide Title</label>
                <input type="text" id="edit_title" name="title" required
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Description / Subtitle</label>
                <textarea id="edit_description" name="description" rows="3" required
                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sort Order</label>
                    <input type="number" id="edit_sort_order" name="sort_order" min="1" required
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                    <select id="edit_is_active" name="is_active" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="1">Active</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Illustration / Banner Graphic <span class="text-slate-400 font-normal">(Optional)</span></label>
                <input type="file" name="image" accept="image/*" class="w-full text-xs border border-slate-200 rounded-lg p-2 bg-slate-50">
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editSlideModal').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">Update Slide</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function editSlide(slide) {
    document.getElementById('editSlideForm').action = '/admin/settings/onboarding/' + slide.id;
    document.getElementById('edit_title').value = slide.title;
    document.getElementById('edit_description').value = slide.description;
    document.getElementById('edit_sort_order').value = slide.sort_order;
    document.getElementById('edit_is_active').value = slide.is_active ? '1' : '0';
    document.getElementById('editSlideModal').classList.remove('hidden');
}
</script>
@endsection
