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

        if (date && start) {
            this.trackInitiateCheckout(date, start);
        }
    }

    trackInitiateCheckout(date, time) {
        if (typeof window.fbq !== 'function') {
            return;
        }

        window.fbq('track', 'InitiateCheckout', {
            content_name: 'Booking form opened',
            content_category: 'Retro Photo Booking',
            selected_booking_date: date,
            selected_booking_time: time,
            booking_window: this.getBookingWindow(date),
            slot_period: this.getSlotPeriod(time),
            locale: window.location.pathname.split('/')[1] === 'bg' ? 'bg' : 'en',
        });
    }

    getBookingWindow(selectedDate) {
        // Build both dates at local midnight; parsing 'YYYY-MM-DD' with the Date
        // constructor would treat it as UTC and could shift the calendar day.
        const [year, month, day] = selectedDate.split('-').map(Number);
        const target = new Date(year, month - 1, day);
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        // Math.round absorbs DST days that are not exactly 24h long
        const days = Math.round((target - today) / 86400000);

        if (days <= 0) return 'same_day';
        if (days === 1) return 'next_day';
        if (days <= 7) return '2_7_days';
        if (days <= 14) return '8_14_days';
        if (days <= 30) return '15_30_days';
        return '30_plus_days';
    }

    getSlotPeriod(time) {
        const hour = Number.parseInt(time.split(':')[0], 10);

        if (hour < 12) {
            return 'morning';
        }
        if (hour < 17) {
            return 'afternoon';
        }
        return 'evening';
    }
}
