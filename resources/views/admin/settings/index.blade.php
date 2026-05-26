@extends('layouts.admin')

@section('title', 'Settings')
@section('heading', 'Settings')
@section('subheading', 'Manage API providers, service toggles, and platform configuration')

@section('content')

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf

    @php $activeTab = request('tab', array_key_first($groups)); @endphp

    {{-- Tabs --}}
    <div class="flex gap-2 mb-5 flex-wrap">
        @foreach($groups as $key => $group)
        <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
           class="px-4 py-2 text-sm font-medium rounded-xl transition-colors
                  {{ $activeTab === $key ? 'bg-vtu-primary text-white' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:border-vtu-primary hover:text-vtu-primary' }}">
            {{ $group['label'] }}
        </a>
        @endforeach
    </div>

    {{-- Active tab content --}}
    @foreach($groups as $gKey => $group)
    @if($activeTab === $gKey)
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-outfit mb-5">{{ $group['label'] }}</h3>

        <div class="space-y-5">
            @foreach($group['keys'] as $key => $def)
            @php $currentValue = $settings[$key] ?? ''; @endphp

            <div class="flex flex-col sm:flex-row sm:items-center gap-3 py-3 border-b border-slate-50 dark:border-slate-800 last:border-0">
                <label class="sm:w-56 flex-shrink-0 text-sm font-medium text-slate-600 dark:text-slate-400">
                    {{ $def['label'] }}
                    <span class="block text-[11px] font-mono text-slate-400 dark:text-slate-600">{{ $key }}</span>
                </label>

                @if(($def['type'] ?? '') === 'toggle')
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="{{ $key }}" value="0">
                    <input type="checkbox" name="{{ $key }}" value="1" {{ $currentValue == '1' ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer
                                peer-checked:after:translate-x-full peer-checked:after:border-white
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5
                                after:transition-all peer-checked:bg-vtu-primary"></div>
                    <span class="ml-3 text-sm text-slate-500 dark:text-slate-400 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-400">
                        {{ $currentValue == '1' ? 'Enabled' : 'Disabled' }}
                    </span>
                </label>

                @elseif(isset($def['options']))
                <select name="{{ $key }}"
                        class="flex-1 max-w-xs px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    @foreach($def['options'] as $opt)
                    <option value="{{ $opt }}" {{ $currentValue === $opt ? 'selected' : '' }}>{{ ucfirst($opt) }}</option>
                    @endforeach
                </select>

                @else
                <input type="text" name="{{ $key }}" value="{{ $currentValue }}"
                       class="flex-1 max-w-xs px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                @endif
            </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-semibold bg-vtu-primary text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-500/20">
                Save Settings
            </button>
        </div>
    </div>
    @endif
    @endforeach

</form>

@endsection
