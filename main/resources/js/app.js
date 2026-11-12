import Alpine from 'alpinejs';
import { liveFilters } from './filters';
import { assignmentForm, evidenceForm, auditScopeForm, findingAssignmentForm } from './forms';

Alpine.data('liveFilters', liveFilters);
Alpine.data('assignmentForm', assignmentForm);
Alpine.data('evidenceForm', evidenceForm);
Alpine.data('auditScopeForm', auditScopeForm);
Alpine.data('findingAssignmentForm', findingAssignmentForm);
window.Alpine = Alpine;
Alpine.start();
