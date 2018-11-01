# Integrating Disqus

You can easily add comments to your Craft entry pages by utilizing a third-party comment service such as [Disqus](https://disqus.com/).

To do this, you will need to have an output for displaying a single entry you want to allow comments on. In this example, we'll use a template located at `templates/_entry.twig`.

You'll want to sign up with Disqus and get your Universal Code on Disqus’ site in Admin → Settings → Install. Disqus has certain configuration variables that you can use to ensure comments end up on the right post.  Look for:

```javascript
/* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
```

and add this line:

```javascript
var disqus_identifier = 'blog-{{ entry.id }}';
```

A final entry template including comments might look like so:

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

