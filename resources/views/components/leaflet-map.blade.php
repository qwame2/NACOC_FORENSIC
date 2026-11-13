@props([
    'id' => 'forensicLeafletMap',
    'latitude' => null,
    'longitude' => null,
    'zoom' => 7,
    'markers' => [],
    'tileUrl' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    'height' => '480px',
    'class' => '',
])

<div
    id="{{ $id }}"
    data-leaflet-map="true"
    data-latitude='@json($latitude)'
    data-longitude='@json($longitude)'
    data-zoom='@json($zoom)'
    data-markers='@json($markers)'
    data-tile-url="{{ $tileUrl }}"
    data-attribution="{{ $attribution }}"
    role="region"
    aria-label="Interactive Forensic Case Location Map"
    style="height: {{ $height }}; min-height: 440px; width: 100%;"
    {{ $attributes->merge(['class' => 'relative w-full z-0 ' . $class]) }}
></div>

