<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $share->shareable->getShareTitle() ?? 'Opening...' }}</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div id="share-landing" style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif">
    <div style="max-width:720px;padding:24px;border-radius:12px;border:1px solid #eee;text-align:center">
        <h1 style="margin-bottom:8px">{{ $share->shareable->getShareTitle() ?? '' }}</h1>
        <p style="margin-bottom:16px;color:#555">{{ $share->shareable->getShareDescription() ?? '' }}</p>
        <div style="display:flex;gap:8px;justify-content:center">
            <a id="open-now" href="{{ $target }}" style="padding:10px 16px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none">Open original</a>
            <button id="copy-link" style="padding:10px 16px;border-radius:8px;border:1px solid #ddd">Copy link</button>
        </div>
    </div>
</div>

<script>
    setTimeout(() => { window.location.href = "{{ $target }}"; }, 2000);

    document.getElementById('copy-link').addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText("{{ url('/s/'.$share->short_code) }}");
            alert('Link copied');
        } catch { alert('Copy failed'); }
    });
</script>
</body>
</html>
