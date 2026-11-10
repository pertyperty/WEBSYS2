import test from 'node:test';
import assert from 'node:assert/strict';
import { assignmentForm, evidenceForm } from '../resources/js/forms.js';

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
