<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $share->shareable->getShareTitle() ?? 'Shared link' }}</title>

    <meta property="og:title" content="{{ $share->shareable->getShareTitle() ?? '' }}" />
    <meta property="og:description" content="{{ $share->shareable->getShareDescription() ?? '' }}" />
    <meta property="og:image" content="{{ $share->shareable->getShareImageUrl() ?? asset('images/default-share.png') }}" />
    <meta property="og:url" content="{{ url('/s/'.$share->short_code) }}" />
    <meta property="og:type" content="article" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $share->shareable->getShareTitle() ?? '' }}" />
    <meta name="twitter:description" content="{{ $share->shareable->getShareDescription() ?? '' }}" />
    <meta name="twitter:image" content="{{ $share->shareable->getShareImageUrl() ?? asset('images/default-share.png') }}" />

    <meta name="robots" content="noindex,follow">
</head>
<body>
<h1>{{ $share->shareable->getShareTitle() ?? 'Shared' }}</h1>
<p>{{ $share->shareable->getShareDescription() ?? '' }}</p>
</body>
</html>
