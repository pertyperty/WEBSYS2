import Alpine from 'alpinejs';
import { assignmentForm, evidenceForm } from './forms';

Alpine.data('assignmentForm', assignmentForm);
Alpine.data('evidenceForm', evidenceForm);
window.Alpine = Alpine;
Alpine.start();
