import Alpine from 'alpinejs';
import { assignmentForm, evidenceForm, auditScopeForm, findingAssignmentForm } from './forms';

Alpine.data('assignmentForm', assignmentForm);
Alpine.data('evidenceForm', evidenceForm);
Alpine.data('auditScopeForm', auditScopeForm);
Alpine.data('findingAssignmentForm', findingAssignmentForm);
window.Alpine = Alpine;
Alpine.start();
