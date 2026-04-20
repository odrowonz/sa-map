@php
    /** @var \App\Models\NjkTemplate|null $template */
@endphp

<div class="space-y-5">
    <div>
        <label for="njk-title" class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('sa.njk.title_label') }}</label>
        <input
            id="njk-title"
            name="title"
            type="text"
            value="{{ old('title', $template?->title ?? '') }}"
            required
            maxlength="255"
            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 focus:outline-none"
        />
        @error('title')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="njk-filename" class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('sa.njk.filename_label') }}</label>
        <input
            id="njk-filename"
            name="filename"
            type="text"
            value="{{ old('filename', $template?->filename ?? '') }}"
            required
            maxlength="255"
            placeholder="tz.njk"
            class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 focus:outline-none"
        />
        <p class="mt-1 text-xs text-slate-500">{{ __('sa.njk.filename_hint') }}</p>
        @error('filename')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ __('sa.njk.body_label') }}</p>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('sa.njk.body_editor_hint') }}</p>
        <div
            id="njk-cm-mount"
            class="mt-1.5 overflow-hidden rounded-xl border border-slate-200 shadow-sm"
            data-msg-valid="{{ __('sa.njk.syntax_valid') }}"
            data-msg-invalid-prefix="{{ __('sa.njk.syntax_error_prefix') }}"
            data-aria-label="{{ __('sa.njk.body_label') }}"
        ></div>
        <textarea
            id="njk-body"
            name="body"
            required
            class="sr-only"
            tabindex="-1"
            aria-hidden="true"
        >{{ old('body', $template?->body ?? '') }}</textarea>
        <p id="njk-syntax-status" class="mt-2 hidden text-sm" role="status"></p>
        @error('body')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
