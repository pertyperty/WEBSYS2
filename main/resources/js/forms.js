export function assignmentForm(initial) {
    return {
        ...initial,
        get needsFacility() {
            return this.role === 'facility_manager' || (this.role === 'staff' && this.assignStaff);
        },
        get needsJob() {
            return this.role === 'staff' && this.assignStaff;
        },
        get needsRegion() {
            return ['regional_director', 'auditor'].includes(this.role);
        },
        clearUnusedAssignments() {
            if (!this.needsFacility) this.facilityId = '';
            if (!this.needsJob) this.jobId = '';
            if (!this.needsRegion) this.regionId = '';
        },
    };
}

export function evidenceForm(initial) {
    return {
        ...initial,
        get qualification() {
            return this.qualifications.find(item => String(item.id) === String(this.qualificationId));
        },
        get requiresTraining() {
            return this.qualification?.requires_training ?? false;
        },
        get requiresExpiry() {
            return this.qualification?.requires_expiry ?? false;
        },
        get matchingEnrollments() {
            return this.enrollments.filter(item => String(item.qualification_id) === String(this.qualificationId));
        },
        changeQualification() {
            if (!this.requiresTraining || !this.matchingEnrollments.some(item => String(item.id) === String(this.enrollmentId))) {
                this.enrollmentId = '';
            }
        },
    };
}
