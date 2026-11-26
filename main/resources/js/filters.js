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
    const browser = options.browser ?? globalThis.window;
    let timer;
    let controller;
    let revision = 0;
    let submitting = false;
    let beforeUnload;
    let restored;
    let submittingForm;
    let leaving;
    let reloading;
    let previousFocus;

    return {
        ready: false, busy: false, composing: false, error: '', message: '', reportType: 'readiness', downloadUrl: '', resultsUrl: '', leaveUrl: '', leaveIntent: '',
        init() {
            this.resultsUrl = location.href;
            this.ready = Boolean(this.$root.querySelector('[data-filter-form]') && this.$root.querySelector('[data-filter-results]'));
            this.reportType = this.$root.querySelector('[name="type"]')?.value ?? 'readiness';
            this.downloadUrl = this.$root.querySelector('[data-filter-download]')?.href ?? '';
            if (browser && options.protectCompletion) {
                beforeUnload = event => {
                    if (!submitting && this.hasPendingEdits()) {
                        event.preventDefault();
                        event.returnValue = '';
                    }
                };
                restored = () => { submitting = false; };
                leaving = event => {
                    const link = event.target.closest('a');
                    if (!link || event.defaultPrevented || link.target === '_blank' || link.hasAttribute('download')
                        || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
                    const url = new URL(link.href, location.href);
                    const action = new URL(this.$root.querySelector('[data-filter-form]').action);
                    if (url.origin === action.origin && url.pathname === action.pathname) return;
                    if (this.hasPendingEdits()) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        this.requestLeaving(url.href);
                    }
                };
                reloading = event => {
                    if ((event.key === 'F5' || ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'r')) && this.hasPendingEdits()) {
                        event.preventDefault();
                        this.requestLeaving('', 'reload');
                    }
                };
                submittingForm = event => {
                    if (event.target.matches('[data-filter-edit-form]') && !event.defaultPrevented) submitting = true;
                };
                browser.addEventListener('beforeunload', beforeUnload);
                browser.addEventListener('pageshow', restored);
                browser.document?.addEventListener('click', leaving, true);
                browser.document?.addEventListener('keydown', reloading, true);
                this.$root.addEventListener('submit', submittingForm);
            }
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
        hasPendingEdits() {
            const fields = this.$root.querySelectorAll('[data-filter-edit-form] input:not([type="hidden"]), [data-filter-edit-form] textarea');
            return [...fields].some(field => field.value.trim() !== '');
        },
        requestLeaving(url, intent = 'navigate') {
            previousFocus = browser.document?.activeElement;
            this.leaveUrl = url;
            this.leaveIntent = intent;
            this.$root.querySelector('[data-unsaved-dialog]').showModal();
        },
        cancelLeaving() {
            this.$root.querySelector('[data-unsaved-dialog]').close();
            this.leaveUrl = '';
            this.leaveIntent = '';
            previousFocus?.focus();
        },
        confirmLeaving() {
            submitting = true;
            this.$root.querySelector('[data-unsaved-dialog]').close();
            if (this.leaveIntent === 'reload') location.reload();
            else location.assign(this.leaveUrl);
        },
        keepPendingEdits() {
            if (!this.hasPendingEdits()) return false;
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
            if (beforeUnload) {
                browser.removeEventListener('beforeunload', beforeUnload);
                browser.removeEventListener('pageshow', restored);
                browser.document?.removeEventListener('click', leaving, true);
                browser.document?.removeEventListener('keydown', reloading, true);
                this.$root.removeEventListener('submit', submittingForm);
            }
        },
    };
}
