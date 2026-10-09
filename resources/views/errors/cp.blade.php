<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }}</title>
    {{-- Inline, so the page still draws when whatever broke was the asset pipeline. --}}
    <style>
        :root {
            color-scheme: light dark;
            --bg: #f3f5f8;
            --surface: #fff;
            --border: #e1e5ea;
            --text: #1f2933;
            --muted: #606d7b;
            --link: #2563eb;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #15191e;
                --surface: #1f252c;
                --border: #333c46;
                --text: #e6e9ed;
                --muted: #9aa5b1;
                --link: #7aa7ff;
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: grid;
            place-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            background: var(--bg);
            color: var(--text);
            font: 16px/1.5 system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        main {
            width: 100%;
            max-width: 32rem;
            padding: 32px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.06);
        }

        .code {
            margin: 0 0 4px;
            color: var(--muted);
            font-size: 14px;
            font-variant-numeric: tabular-nums;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 20px;
            line-height: 1.3;
        }

        p {
            margin: 0;
            color: var(--muted);
            overflow-wrap: anywhere;
        }

        a {
            display: inline-block;
            margin-top: 24px;
            color: var(--link);
        }
    </style>
</head>
<body>
    <main>
        <p class="code">{{ $status }}</p>
        <h1>{{ $title }}</h1>
        @if ($message)
            <p>{{ $message }}</p>
        @endif
        <a href="{{ \CraftCms\Cms\cp_url('dashboard') }}">{{ \CraftCms\Cms\t('Dashboard') }}</a>
    </main>
</body>
</html>
