<script>
    (function() {
        try {
            var pref = localStorage.getItem('pl_theme');
            var resolved = (pref === 'light' || pref === 'dark') ?
                pref :
                (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', resolved);
        } catch (e) {}
    })();
</script>