export function filterUrl(action, entries, origin) {
    const url = new URL(action, origin);
    url.search = '';
    for (const [name, value] of entries) {
        if (name !== 'page' && typeof value === 'string' && value.trim() !== '') {
            url.searchParams.append(name, value.trim());
        }
    }
    return url;
}

export function liveFilters(options = {}) {
    const fetchPage = options.fetch ?? globalThis.fetch.bind(globalThis);
    const parse = options.parse ?? (html => new DOMParser().parseFromString(html, 'text/html'));
    const entries = options.entries ?? (form => new FormData(form));
    const location = options.location ?? globalThis.location;
    const history = options.history ?? globalThis.history;
    let timer;
    let controller;
    let revision = 0;

    return {
        ready: false, busy: false, composing: false, error: '', message: '', reportType: 'readiness', downloadUrl: '', resultsUrl: '',
        init() {
            this.resultsUrl = location.href;
            this.ready = Boolean(this.$root.querySelector('[data-filter-form]') && this.$root.querySelector('[data-filter-results]'));
            this.reportType = this.$root.querySelector('[name="type"]')?.value ?? 'readiness';
            this.downloadUrl = this.$root.querySelector('[data-filter-download]')?.href ?? '';
        },
        cancel() {
            clearTimeout(timer);
            controller?.abort();
            revision++;
        },
        typing(event) {
            if (!['search', 'text'].includes(event.target.type)) return;
            this.cancel();
            this.busy = true;
            this.error = '';
            if (!this.composing && !event.isComposing) {
                timer = setTimeout(() => this.apply(), options.delay ?? 400);
            }
        },
        changed(event) {
            if (event.target.tagName !== 'SELECT') return;
            if (event.target.name === 'type') this.reportType = event.target.value;
            this.apply();
        },
        startComposition() {
            this.composing = true;
            this.cancel();
            this.busy = true;
        },
        endComposition(event) {
            this.composing = false;
            this.typing(event);
        },
        keepPendingEdits() {
            const fields = this.$root.querySelectorAll('[data-filter-edit-form] input:not([type="hidden"]), [data-filter-edit-form] textarea');
            if (![...fields].some(field => field.value.trim() !== '')) return false;
            this.cancel();
            this.busy = false;
            this.restoreFields(new URL(this.resultsUrl));
            if (location.href !== this.resultsUrl) history.replaceState(null, '', this.resultsUrl);
            this.error = 'Finish recording completion or clear the entered details before changing results. Your entries are still here.';
            return true;
        },
        async apply(target = null, push = true) {
            this.cancel();
            if (this.keepPendingEdits()) return;
            const requestRevision = revision;
            const form = this.$root.querySelector('[data-filter-form]');
            const url = target ? new URL(target, location.href) : filterUrl(form.action, entries(form), location.href);
            if (url.origin !== location.origin || url.pathname !== new URL(form.action).pathname) return;
            this.busy = true;
            this.error = '';
            controller = new AbortController();
            try {
                const response = await fetchPage(url.href, {
                    headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
                });
                if (requestRevision !== revision) return;
                if (response.redirected) {
                    location.assign(response.url);
                    return;
                }
                if (!response.ok) throw new Error('The results request failed.');
                const html = await response.text();
                if (requestRevision !== revision) return;
                const page = parse(html);
                const next = page.querySelector('[data-filter-results]');
                if (!next) throw new Error('The results were unavailable.');
                if (this.keepPendingEdits()) return;
                this.message = next.querySelector('[data-filter-summary]')?.textContent.trim() ?? 'Results updated.';
                this.$root.querySelector('[data-filter-results]').replaceChildren(...next.childNodes);
                const download = this.$root.querySelector('[data-filter-download]');
                const nextDownload = page.querySelector('[data-filter-download]');
                if (download && nextDownload) {
                    this.downloadUrl = nextDownload.href;
                    download.href = nextDownload.href;
                }
                if (push && url.href !== location.href) history.pushState(null, '', url.href);
                this.resultsUrl = url.href;
            } catch (error) {
                if (requestRevision === revision && error.name !== 'AbortError') {
                    this.error = 'Results could not be updated. Your previous results are still shown.';
                }
            } finally {
                if (requestRevision === revision) this.busy = false;
            }
        },
        restoreFields(url) {
            const form = this.$root.querySelector('[data-filter-form]');
            for (const field of form.elements) {
                if (field.name && ['INPUT', 'SELECT'].includes(field.tagName)) {
                    field.value = url.searchParams.get(field.name) ?? (field.name === 'type' ? 'readiness' : '');
                }
            }
            this.reportType = form.querySelector('[name="type"]')?.value ?? 'readiness';
        },
        reset() {
            const url = new URL(this.$root.querySelector('[data-filter-form]').action);
            this.restoreFields(url);
            this.apply(url.href);
        },
        restore() {
            this.restoreFields(new URL(location.href));
            this.apply(location.href, false);
        },
        async follow(event) {
            if (event.defaultPrevented) return;
            const link = event.target.closest('a');
            if (!link || !link.closest('[data-filter-results]') || event.button !== 0
                || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            const url = new URL(link.href);
            const action = new URL(this.$root.querySelector('[data-filter-form]').action);
            if (url.origin !== action.origin || url.pathname !== action.pathname) return;
            event.preventDefault();
            if (!this.busy && !this.error) {
                await this.apply(url.href);
                if (!this.busy && !this.error) {
                    const results = this.$root.querySelector('[data-filter-results]');
                    results.focus({ preventScroll: true });
                    results.scrollIntoView({ block: 'start' });
                }
            }
        },
        destroy() {
            this.cancel();
        },
    };
}
