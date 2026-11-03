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
            --texture: rgb(15 23 42 / 0.08);
            --surface: #fff;
            --border: #e1e5ea;
            --text: #1f2933;
            --muted: #606d7b;
            --red: #dc2626;
            --red-fill: #fef2f2;
            --code-fill: #e7ebf0;
            --link: #2563eb;
            --shadow-multiplier: 1;
            --shadow: 0 10px 15px -3px rgb(0 0 0 / calc(0.1 * var(--shadow-multiplier))),
            0 4px 6px -4px rgb(0 0 0 / calc(0.1 * var(--shadow-multiplier)));
          }

          @media (prefers-color-scheme: dark) {
            :root {
              --bg: #15191e;
              --texture: rgb(255 255 255 / 0.06);
              --surface: #1f252c;
              --border: #333c46;
              --text: #e6e9ed;
              --muted: #9aa5b1;
              --red: #f87171;
              --red-fill: rgb(248 113 113 / 0.12);
              --code-fill: rgb(255 255 255 / 0.08);
              --link: #7aa7ff;
              --shadow-multiplier: 1.5;
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
            background:
              radial-gradient(var(--texture) 1px, transparent 1px) 0 0 / 14px 14px,
              var(--bg);
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
            box-shadow: var(--shadow);
          }

          .code {
            display: inline-block;
            margin: 0 0 12px;
            padding: 2px 8px;
            border-radius: 4px;
            background: var(--code-fill);
            color: var(--muted);
            font: 600 13px/1.5 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-variant-numeric: tabular-nums;
          }

          .code-error {
            background: var(--red-fill);
            color: var(--red);
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
            <p @class(['code', 'code-error' => $status >= 500])>{{ $status }}</p>
            <h1>{{ $title }}</h1>
            @if ($message)
                <p>{{ $message }}</p>
            @endif
            <a href="{{ \CraftCms\Cms\cp_url('dashboard') }}">{{ \CraftCms\Cms\t('Dashboard') }}</a>
        </main>
    </body>
</html>
