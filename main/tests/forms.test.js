import { liveFilters, filterUrl } from '../resources/js/filters.js';
import test from 'node:test';
import assert from 'node:assert/strict';
import { assignmentForm, evidenceForm, auditScopeForm, findingAssignmentForm } from '../resources/js/forms.js';

const assignment = (role) => assignmentForm({ role, assignStaff: true, facilityId: '10', jobId: '20', regionId: '30' });

test('global roles clear all location and job fields', () => {
    for (const role of ['admin', 'compliance_officer']) {
        const form = assignment(role);
        form.clearUnusedAssignments();
        assert.deepEqual([form.facilityId, form.jobId, form.regionId], ['', '', '']);
        assert.equal(form.needsFacility, false);
        assert.equal(form.needsJob, false);
        assert.equal(form.needsRegion, false);
    }
});

test('staff assignment needs facility and job together and clears region', () => {
    const form = assignment('staff');
    form.clearUnusedAssignments();
    assert.deepEqual([form.facilityId, form.jobId, form.regionId], ['10', '20', '']);
    assert.equal(form.needsFacility, true);
    assert.equal(form.needsJob, true);
    form.assignStaff = false;
    form.clearUnusedAssignments();
    assert.deepEqual([form.facilityId, form.jobId], ['', '']);
    assert.equal(form.needsFacility, false);
    assert.equal(form.needsJob, false);
});

test('manager and regional roles keep only their applicable scope', () => {
    const manager = assignment('facility_manager');
    manager.clearUnusedAssignments();
    assert.deepEqual([manager.facilityId, manager.jobId, manager.regionId], ['10', '', '']);
    for (const role of ['regional_director', 'auditor']) {
        const form = assignment(role);
        form.clearUnusedAssignments();
        assert.deepEqual([form.facilityId, form.jobId, form.regionId], ['', '', '30']);
        assert.equal(form.needsRegion, true);
    }
});

const evidence = () => evidenceForm({
    qualificationId: '1', enrollmentId: '11',
    qualifications: [{ id: 1, requires_training: true, requires_expiry: true }, { id: 2, requires_training: false, requires_expiry: false }, { id: 3, requires_training: true, requires_expiry: false }],
    enrollments: [{ id: 11, qualification_id: 1 }, { id: 22, qualification_id: 3 }],
});

test('evidence fields follow qualification requirements', () => {
    const form = evidence();
    assert.equal(form.requiresTraining, true);
    assert.equal(form.requiresExpiry, true);
    assert.deepEqual(form.matchingEnrollments.map(item => item.id), [11]);
    form.qualificationId = '2';
    form.changeQualification();
    assert.equal(form.requiresTraining, false);
    assert.equal(form.requiresExpiry, false);
    assert.equal(form.enrollmentId, '');
});

test('changing qualifications removes a mismatched enrollment', () => {
    const form = evidence();
    form.qualificationId = '3';
    form.changeQualification();
    assert.equal(form.enrollmentId, '');
    assert.deepEqual(form.matchingEnrollments.map(item => item.id), [22]);
    form.enrollmentId = '22';
    form.changeQualification();
    assert.equal(form.enrollmentId, '22');
});

test('unknown or empty qualifications have no required dependent fields', () => {
    const form = evidence();
    for (const id of ['', '999']) {
        form.qualificationId = id;
        form.changeQualification();
        assert.equal(form.requiresTraining, false);
        assert.equal(form.requiresExpiry, false);
        assert.equal(form.enrollmentId, '');
        assert.deepEqual(form.matchingEnrollments, []);
    }
});

test('audit region selection exposes only facilities in that region', () => {
    const form = auditScopeForm([{ id: 1, region_id: 10 }, { id: 2, region_id: 20 }], '10');
    assert.deepEqual(form.availableFacilities.map(facility => facility.id), [1]);
    form.regionId = '20';
    assert.deepEqual(form.availableFacilities.map(facility => facility.id), [2]);
});

test('finding assignment exposes only managers of the selected facility', () => {
    const form = findingAssignmentForm([{ id: 1, facility_id: 10 }, { id: 2, facility_id: 20 }], '10');
    assert.deepEqual(form.availableManagers.map(manager => manager.id), [1]);
    form.facilityId = '';
    assert.deepEqual(form.availableManagers, []);
});


const flush = () => new Promise(resolve => setImmediate(resolve));

function filterHarness(fetchPage = async () => ({ ok: true, text: async () => 'fresh' })) {
    const fields = [
        { name: 'search', value: '', type: 'search', tagName: 'INPUT' },
        { name: 'status', value: '', type: 'select-one', tagName: 'SELECT' },
        { name: 'type', value: 'readiness', type: 'select-one', tagName: 'SELECT' },
    ];
    const form = { action: 'http://checkpoint.test/reports', elements: fields,
        querySelector: () => fields[2] };
    const results = { nodes: ['previous results'], replaceChildren(...nodes) { this.nodes = nodes; }, focus() { this.focused = true; }, scrollIntoView() { this.scrolled = true; } };
    const download = { href: 'http://checkpoint.test/reports/download?type=readiness' };
    const location = { href: form.action, origin: 'http://checkpoint.test', assign(url) { this.assigned = url; } };
    const history = { updates: [], replaceState(_state, _title, url) { location.href = url; }, pushState(_state, _title, url) { this.updates.push(url); location.href = url; } };
    const editFields = [];
    const root = { querySelectorAll: () => editFields, querySelector(selector) {
        return ({ '[data-filter-form]': form, '[data-filter-results]': results, '[data-filter-download]': download,
            '[name="type"]': fields[2] })[selector];
    } };
    const model = liveFilters({ fetch: fetchPage, entries: () => fields.map(field => [field.name, field.value]),
        location, history, parse: html => ({ querySelector(selector) {
            if (selector === '[data-filter-download]') return { href: 'http://checkpoint.test/reports/download?type=findings' };
            if (selector === '[data-filter-results]') return { childNodes: [html], querySelector: () => ({ textContent: '3 matching rows' }) };
        } }),
    });
    model.$root = root;
    model.init();
    return { model, fields, results, download, location, history, editFields };
}

test('filter URLs trim text, encode special characters and remove obsolete pagination', () => {
    const url = filterUrl('/employees?page=9&obsolete=yes', [['search', '  cold & chain  '], ['status', ''], ['page', '9'], ['facility_id', '0']], 'http://checkpoint.test');
    assert.equal(url.href, 'http://checkpoint.test/employees?search=cold+%26+chain&facility_id=0');
});

test('typing waits for a pause and coalesces keystrokes into one request', async t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const calls = [];
    const { model, fields, results } = filterHarness(async url => { calls.push(url); return { ok: true, text: async () => 'matching rows' }; });
    fields[0].value = 'C';
    model.typing({ target: fields[0] });
    t.mock.timers.tick(300);
    fields[0].value = 'Cold';
    model.typing({ target: fields[0] });
    t.mock.timers.tick(399);
    assert.equal(calls.length, 0);
    assert.equal(model.busy, true);
    t.mock.timers.tick(1);
    await flush();
    assert.equal(calls.length, 1);
    assert.equal(new URL(calls[0]).searchParams.get('search'), 'Cold');
    assert.deepEqual(results.nodes, ['matching rows']);
    assert.equal(fields[0].value, 'Cold');
    assert.equal(model.message, '3 matching rows');
    assert.equal(model.busy, false);
});

test('a select applies immediately and cancels a queued text request', async t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const calls = [];
    const { model, fields } = filterHarness(async url => { calls.push(url); return { ok: true, text: async () => 'updated' }; });
    fields[0].value = 'Handling';
    model.typing({ target: fields[0] });
    fields[1].value = 'approved';
    model.changed({ target: fields[1] });
    assert.equal(calls.length, 1);
    t.mock.timers.tick(1000);
    await flush();
    assert.equal(calls.length, 1);
    assert.equal(new URL(calls[0]).searchParams.get('status'), 'approved');
    assert.equal(new URL(calls[0]).searchParams.get('search'), 'Handling');
});

test('older responses cannot replace newer results or overwrite the URL', async () => {
    const pending = [];
    const { model, fields, results, location } = filterHarness((url, options) => new Promise(resolve => pending.push({ url, options, resolve })));
    fields[0].value = 'old';
    const oldRequest = model.apply();
    fields[0].value = 'new';
    const newRequest = model.apply();
    assert.equal(pending[0].options.signal.aborted, true);
    pending[1].resolve({ ok: true, text: async () => 'new rows' });
    await newRequest;
    pending[0].resolve({ ok: true, text: async () => 'old rows' });
    await oldRequest;
    assert.deepEqual(results.nodes, ['new rows']);
    assert.equal(new URL(location.href).searchParams.get('search'), 'new');
});

test('typing invalidates an in-flight response before the next debounce finishes', async t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    let complete;
    const { model, fields, results } = filterHarness(() => new Promise(resolve => { complete = resolve; }));
    const request = model.apply();
    fields[0].value = 'next';
    model.typing({ target: fields[0] });
    complete({ ok: true, text: async () => 'stale rows' });
    await request;
    assert.deepEqual(results.nodes, ['previous results']);
    assert.equal(model.busy, true);
    model.destroy();
});

test('failed requests keep previous results and export URL with a retryable error', async () => {
    const { model, results, download, history } = filterHarness(async () => ({ ok: false }));
    await model.apply();
    assert.deepEqual(results.nodes, ['previous results']);
    assert.match(model.error, /previous results are still shown/);
    assert.equal(download.href, 'http://checkpoint.test/reports/download?type=readiness');
    assert.deepEqual(history.updates, []);
    assert.equal(model.busy, false);
});

test('session redirects navigate to login without replacing results with a login form', async () => {
    const { model, results, location } = filterHarness(async () => ({ redirected: true, url: 'http://checkpoint.test/login' }));
    await model.apply();
    assert.equal(location.assigned, 'http://checkpoint.test/login');
    assert.deepEqual(results.nodes, ['previous results']);
});

test('clear filters resets controls and report context before fetching the unfiltered list', async () => {
    const { model, fields, location } = filterHarness();
    fields[0].value = 'Handling';
    fields[1].value = 'pending';
    fields[2].value = 'findings';
    model.reportType = 'findings';
    model.reset();
    await flush();
    assert.deepEqual(fields.map(field => field.value), ['', '', 'readiness']);
    assert.equal(model.reportType, 'readiness');
    assert.equal(location.href, 'http://checkpoint.test/reports');
});

test('Back and Forward restore inputs and pagination without creating another history entry', async () => {
    const { model, fields, location, history } = filterHarness();
    location.href = 'http://checkpoint.test/reports?search=Handling&type=findings&page=2';
    model.restore();
    await flush();
    assert.equal(fields[0].value, 'Handling');
    assert.equal(fields[2].value, 'findings');
    assert.equal(model.reportType, 'findings');
    assert.deepEqual(history.updates, []);
    assert.equal(new URL(location.href).searchParams.get('page'), '2');
});

test('IME composition is not searched until the committed text is ready', async t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const calls = [];
    const { model, fields } = filterHarness(async url => { calls.push(url); return { ok: true, text: async () => 'rows' }; });
    model.startComposition();
    fields[0].value = 'text in progress';
    model.typing({ target: fields[0], isComposing: true });
    t.mock.timers.tick(1000);
    assert.equal(calls.length, 0);
    fields[0].value = 'committed text';
    model.endComposition({ target: fields[0] });
    t.mock.timers.tick(400);
    await flush();
    assert.equal(calls.length, 1);
    assert.equal(new URL(calls[0]).searchParams.get('search'), 'committed text');
});

test('only the returned results and matching CSV link are replaced on success', async () => {
    const { model, fields, results, download } = filterHarness();
    fields[2].value = 'findings';
    const sameInput = fields[0];
    await model.apply();
    assert.equal(fields[0], sameInput);
    assert.deepEqual(results.nodes, ['fresh']);
    assert.equal(download.href, 'http://checkpoint.test/reports/download?type=findings');
    assert.equal(model.downloadUrl, download.href);
});


test('pagination fetches its filtered URL and places keyboard focus on the new results', async () => {
    const { model, results, location } = filterHarness();
    const link = { href: 'http://checkpoint.test/reports?search=Handling&page=2', closest: () => results };
    const event = { button: 0, target: { closest: () => link }, preventDefault() { this.defaultPrevented = true; } };
    await model.follow(event);
    assert.equal(event.defaultPrevented, true);
    assert.equal(new URL(location.href).searchParams.get('page'), '2');
    assert.equal(results.focused, true);
    assert.equal(results.scrolled, true);
});

test('modified clicks and links to another workflow keep normal browser navigation', async () => {
    const calls = [];
    const { model, results } = filterHarness(async url => calls.push(url));
    const other = { href: 'http://checkpoint.test/employees/1', closest: () => results };
    const event = { button: 0, target: { closest: () => other }, preventDefault() { this.defaultPrevented = true; } };
    await model.follow(event);
    assert.equal(event.defaultPrevented, undefined);
    const pagination = { href: 'http://checkpoint.test/reports?page=2', closest: () => results };
    event.target.closest = () => pagination;
    event.ctrlKey = true;
    await model.follow(event);
    assert.equal(event.defaultPrevented, undefined);
    assert.equal(calls.length, 0);
});


test('filtering preserves unfinished completion details and restores the displayed filter values', async () => {
    const calls = [];
    const { model, fields, results, editFields, location } = filterHarness(async url => { calls.push(url); });
    editFields.push({ value: 'Practical assessment notes' });
    fields[0].value = 'different course';
    await model.apply();
    assert.equal(calls.length, 0);
    assert.deepEqual(results.nodes, ['previous results']);
    assert.equal(editFields[0].value, 'Practical assessment notes');
    assert.equal(fields[0].value, '');
    assert.equal(location.href, model.resultsUrl);
    assert.match(model.error, /Finish recording completion/);
    assert.equal(model.busy, false);
});

test('completion details entered during a pending fetch prevent its response from replacing the form', async () => {
    let complete;
    const { model, editFields, results } = filterHarness(() => new Promise(resolve => { complete = resolve; }));
    const request = model.apply();
    editFields.push({ value: '2026-11-13' });
    complete({ ok: true, text: async () => 'new results' });
    await request;
    assert.deepEqual(results.nodes, ['previous results']);
    assert.equal(editFields[0].value, '2026-11-13');
    assert.equal(model.busy, false);
});

test('cleared completion fields allow filtering and Back cannot silently discard entered details', async () => {
    const { model, editFields, results, location, fields } = filterHarness();
    editFields.push({ value: 'notes' });
    location.href = 'http://checkpoint.test/reports?search=older';
    model.restore();
    await flush();
    assert.equal(location.href, 'http://checkpoint.test/reports');
    assert.equal(fields[0].value, '');
    assert.deepEqual(results.nodes, ['previous results']);
    editFields[0].value = '';
    fields[0].value = 'next';
    await model.apply();
    assert.deepEqual(results.nodes, ['fresh']);
    assert.equal(model.error, '');
});
