import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['slotId', 'date', 'startTime', 'endTime', 'timeRangeLabel'];

    update(event) {
        const button = event.relatedTarget;
        if (!button) {
            console.warn('Booking modal opened without relatedTarget');
            return;
        }

        const slotId = button.getAttribute('data-slot-id');
        const date = button.getAttribute('data-date');
        const start = button.getAttribute('data-start');
        const end = button.getAttribute('data-end');

        if (this.hasSlotIdTarget) this.slotIdTarget.value = slotId;
        if (this.hasDateTarget) this.dateTarget.value = date;
        if (this.hasStartTimeTarget) this.startTimeTarget.value = start;
        if (this.hasEndTimeTarget) this.endTimeTarget.value = end;

        if (this.hasTimeRangeLabelTarget) {
            this.timeRangeLabelTarget.textContent = (start && end) ? (start + ' - ' + end) : '';
        }

        console.log('Booking modal updated:', {slotId, date, start, end});
    }
}
