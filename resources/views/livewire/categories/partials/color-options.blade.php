@php
    $colors = [
        '#FF6B6B',
        '#F4A261',
        '#F9C74F',
        '#90BE6D',
        '#43AA8B',
        '#4D96FF',
        '#98FBCB',
        '#6A4C93',
        '#B5179E',
        '#FFB5C0',
        '#ADB5BD',
        '#FFFFFF',
    ];
@endphp

@php
    $selectedColor = $color ?? null;
@endphp

<div class="d-flex flex-wrap gap-2">
    @foreach ($colors as $option)
        @php
            $isSelected = $selectedColor === $option;
            $inputId = 'color_option_' . $loop->index;
        @endphp
        <input
            id="{{ $inputId }}"
            type="radio"
            name="color"
            class="visually-hidden"
            wire:model.live="color"
            value="{{ $option }}"
        >
        <label class="d-inline-flex align-items-center" for="{{ $inputId }}" style="cursor: pointer;">
            <span
                class="rounded-circle {{ $isSelected ? 'border border-3 shadow-sm' : 'border' }}"
                style="width: 32px; height: 32px; background: {{ $option }}; border-color: {{ $isSelected ? '#212529' : '#6c757d' }};"
            ></span>
        </label>
    @endforeach
</div>
