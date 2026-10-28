@props(['data'])

@push('structured-data')
    <script type="application/ld+json">{!! \App\Support\StructuredData::toJson($data) !!}</script>
@endpush
