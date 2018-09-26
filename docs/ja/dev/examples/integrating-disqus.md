# Disqus の統合

[Disqus](https://disqus.com/) のようなサードパーティのコメントサービスを利用して、Craft のエントリページに簡単にコメント機能を追加することができます。

これを行うためには、コメントを許可する単一のエントリを表示するための出力設定が必要です。この例では、`templates/_entry.twig` にあるテンプレートを使用します。

あなたは Disqus にサインアップし、Disqus のサイトの「Admin → Settings → Install」からユニバーサルコードを入手できるでしょう。Disqus には意図したポストへのコメントを保証するために使うことができる、規定のコンフィグ変数があります。次のコメントを探してください。

```javascript
/* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
```

次の行を追加します。

```javascript
var disqus_identifier = 'blog-{{ entry.id }}';
```

コメント機能を含む最終的なエントリのテンプレートは次のようになります。

```twig
{% block blog %}
    <h1 class="title">{{ entry.title }}</h1>

    {{ entry.body|raw }}

    <div id="disqus_thread"></div>
    <script type="text/javascript">
        /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
        var disqus_shortname = 'disqus_forum_shortname'; // required: replace example with your forum shortname
        var disqus_identifier = '{{ entry.section }}-{{ entry.id }}';

        /* * * DON'T EDIT BELOW THIS LINE * * */
        (function() {
            var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
            dsq.src = 'https://' + disqus_shortname + '.disqus.com/embed.js';

            (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
        })();
    </script>

    <noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
    <a href="https://disqus.com/" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>
{% endblock %}
```

