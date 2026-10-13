{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc></url>
@foreach ($projects as $project)
    <url><loc>{{ route('projects.show', $project) }}</loc><lastmod>{{ $project->updated_at?->toAtomString() }}</lastmod></url>
@endforeach
@if ($notes->isNotEmpty())
    <url><loc>{{ route('notes.index') }}</loc></url>
@endif
@foreach ($notes as $note)
    <url><loc>{{ route('notes.show', $note) }}</loc><lastmod>{{ $note->updated_at?->toAtomString() }}</lastmod></url>
@endforeach
</urlset>
