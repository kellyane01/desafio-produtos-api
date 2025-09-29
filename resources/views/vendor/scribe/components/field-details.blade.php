@php
    $html ??= []; $class = $html['class'] ?? null;
@endphp
<b style="line-height: 2;"><code>{{ $name }}</code></b>&nbsp;&nbsp;
@if($type)<small>{{ $type }}</small>@endif&nbsp;
@if($isInput && !$required)<i>opcional</i>@endif &nbsp;
@if($isInput && empty($hasChildren))
    @php
        $isList = Str::endsWith($type, '[]');
        $fullName = str_replace('[]', '.0', $fullName ?? $name);
        $baseType = $isList ? substr($type, 0, -2) : $type;
        // Ignore the first '[]': the frontend will take care of it
        while (\Str::endsWith($baseType, '[]')) {
            $fullName .= '.0';
            $baseType = substr($baseType, 0, -2);
        }
        // When the body is an array, the item names will be ".0.thing"
        $fullName = ltrim($fullName, '.');
        $inputType = match($baseType) {
            'number', 'integer' => 'number',
            'file' => 'file',
            default => 'text',
        };
    @endphp
    @if($type === 'boolean')
        <label data-endpoint="{{ $endpointId }}" style="display: none">
            <input type="radio" name="{{ $fullName }}"
                   value="{{$component === 'body' ? 'true' : 1}}"
                   data-endpoint="{{ $endpointId }}"
                   data-component="{{ $component }}" @if($class)class="{{ $class }}"@endif
            >
            <code>true</code>
        </label>
        <label data-endpoint="{{ $endpointId }}" style="display: none">
            <input type="radio" name="{{ $fullName }}"
                   value="{{$component === 'body' ? 'false' : 0}}"
                   data-endpoint="{{ $endpointId }}"
                   data-component="{{ $component }}" @if($class)class="{{ $class }}"@endif
            >
            <code>false</code>
        </label>
    @elseif($isList)
        <input type="{{ $inputType }}" style="display: none"
               @if($inputType === 'number')step="any"@endif
               name="{{ $fullName."[0]" }}" @if($class)class="{{ $class }}"@endif
               data-endpoint="{{ $endpointId }}"
               data-component="{{ $component }}">
        <input type="{{ $inputType }}" style="display: none"
               name="{{ $fullName."[1]" }}" @if($class)class="{{ $class }}"@endif
               data-endpoint="{{ $endpointId }}"
               data-component="{{ $component }}">
    @else
        <input type="{{ $inputType }}" style="display: none"
               @if($inputType === 'number')step="any"@endif
               name="{{ $fullName }}" @if($class)class="{{ $class }}"@endif
               data-endpoint="{{ $endpointId }}"
               value="{!! (isset($example) && (is_string($example) || is_numeric($example))) ? $example : '' !!}"
               data-component="{{ $component }}">
    @endif
@endif
<br>
@php
    if($example !== null && $example !== '' && !is_array($example)) {
        $exampleAsString = $example;
        if (is_bool($example)) {
            $exampleAsString = $example ? "true" : "false";
        }
        $description .= " Exemplo: `$exampleAsString`";
    }
    $description = str_replace(
        [
            'Must be a valid email address.',
            'Must be at least 0.',
            'Must be at least 1.',
            'Must not be greater than 100.',
            'Must not be greater than 255 characters.',
            'Must be a valid date.',
            'Must be a date after or equal to <code>from</code>.',
            'Must be a date after or equal to &lt;code&gt;from&lt;/code&gt;.',
        ],
        [
            'Deve ser um endereço de e-mail válido.',
            'Deve ser no mínimo 0.',
            'Deve ser no mínimo 1.',
            'Não deve ser maior que 100.',
            'Não deve ultrapassar 255 caracteres.',
            'Deve ser uma data válida.',
            'Deve ser uma data igual ou posterior a <code>from</code>.',
            'Deve ser uma data igual ou posterior a &lt;code&gt;from&lt;/code&gt;.',
        ],
        $description
    );
@endphp
{!! Parsedown::instance()->text(trim($description)) !!}
@if(!empty($enumValues))
Deve ser um dos valores:
<ul style="list-style-type: square;">{!! implode(" ", array_map(fn($val) => "<li><code>$val</code></li>", $enumValues)) !!}</ul>
@endif
