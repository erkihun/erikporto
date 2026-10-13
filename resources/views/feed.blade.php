{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>{{ $author }} · Notes</title>
    <link href="{{ route('notes.index') }}"/>
    <link rel="self" href="{{ route('feed') }}"/>
    <id>{{ route('notes.index') }}</id>
    <updated>{{ $updated->toAtomString() }}</updated>
    <author><name>{{ $author }}</name></author>
@foreach ($notes as $note)
    <entry>
        <title>{{ $note->title }}</title>
        <link href="{{ route('notes.show', $note) }}"/>
        <id>{{ route('notes.show', $note) }}</id>
        <published>{{ $note->published_at->toAtomString() }}</published>
        <updated>{{ $note->updated_at->toAtomString() }}</updated>
        <summary>{{ $note->excerpt }}</summary>
        <content type="html">{{ $note->body_html }}</content>
    </entry>
@endforeach
</feed>
