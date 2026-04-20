@php
    $variant = $variant ?? 'light';
    $order = ['kk', 'ru', 'en'];
    $available = config('app.available_locales', ['ru', 'en', 'kk']);
    $locales = array_values(array_filter($order, fn ($l) => in_array($l, $available, true)));
    $labels = ['kk' => 'ҚАЗ', 'ru' => 'РУС', 'en' => 'ENG'];
    $current = app()->getLocale();
    $groupClass =
        $variant === 'dark'
            ? 'inline-flex items-center rounded-lg border border-slate-600 bg-slate-900/60 p-0.5 shadow-inner'
            : 'inline-flex items-center rounded-lg border border-slate-200 bg-white p-0.5 shadow-sm';
@endphp
<form
    method="POST"
    action="{{ route('locale.update') }}"
    class="{{ $groupClass }}"
    role="group"
    aria-label="{{ __('sa.locale_switcher.label') }}"
>
    @csrf
    @foreach ($locales as $loc)
        @php
            $active = $current === $loc;
            $btnClass =
                $variant === 'dark'
                    ? ($active
                        ? 'rounded-md bg-blue-500 px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-white shadow'
                        : 'rounded-md px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-300 hover:bg-slate-700 hover:text-white')
                    : ($active
                        ? 'rounded-md bg-slate-800 px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-white shadow-sm'
                        : 'rounded-md px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 hover:bg-slate-100');
        @endphp
        <button
            type="submit"
            name="locale"
            value="{{ $loc }}"
            class="{{ $btnClass }}"
            @if ($active) aria-current="true" @endif
        >
            {{ $labels[$loc] }}
        </button>
    @endforeach
</form>
