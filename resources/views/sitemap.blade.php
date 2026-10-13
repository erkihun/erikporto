{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($localized as $page)
@foreach ($page['urls'] as $url)
    <url>
        <loc>{{ $url }}</loc>
@if ($page['lastmod'])
        <lastmod>{{ $page['lastmod']->toAtomString() }}</lastmod>
@endif
@foreach ($page['urls'] as $locale => $alternate)
        <xhtml:link rel="alternate" hreflang="{{ $locale }}" href="{{ $alternate }}"/>
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $page['urls']['en'] }}"/>
    </url>
@endforeach
@endforeach
@if ($notes->isNotEmpty())
    <url><loc>{{ route('notes.index') }}</loc></url>
@endif
@foreach ($notes as $note)
    <url><loc>{{ route('notes.show', $note) }}</loc><lastmod>{{ $note->updated_at?->toAtomString() }}</lastmod></url>
@endforeach
@if ($now)
    <url><loc>{{ route('now') }}</loc><lastmod>{{ $now->updated_at?->toAtomString() }}</lastmod></url>
@endif
</urlset>
