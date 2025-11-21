@props([
    'name' => 'type',
    'id' => 'type',
    'value' => null,
    'label' => 'Type',
    'help' => null,
    'options' => [
        'title' => 'Title',
        'rich_text' => 'Rich text',
        'url' => 'URL',
        'email' => 'Email',
        'phone_number' => 'Phone number',
        'select' => 'Select (single choice)',
    ],
])

<div class="stack">
    @if ($label)
        <label for="{{ $id }}">{{ $label }}</label>
    @endif

    <select id="{{ $id }}" name="{{ $name }}">
        <option value="" disabled {{ $value === null || $value === '' ? 'selected' : '' }}>Select type</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" {{ $optionValue === $value ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($help)
        <p class="muted">{{ $help }}</p>
    @endif
</div>
