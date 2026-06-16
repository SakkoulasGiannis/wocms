{{--
    Portal footer — minimal, distinct from the main site footer.
    Clone + customize for other layouts.
--}}
<footer class="mt-16 border-t border-outline bg-surface">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-sm text-variant-1 sm:flex-row sm:px-6 lg:px-8">
        <span>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'Portal') }} — Portal</span>
        <a href="/" class="font-medium text-brand hover:text-brand-hover">Back to main site →</a>
    </div>
</footer>
