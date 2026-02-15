<?php

namespace App\Service;

use App\Repository\BookingRepository;
use App\Repository\SlotRepository;

class BookingService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private SlotRepository $slotRepository,
        private int $minimumBookingNotice = 0
    ) {}

    public function getAvailableSlots(\DateTimeInterface $date): array
    {
        $slots = [];
        $now = new \DateTime();

        $existingBookings = $this->bookingRepository->findBy(['date' => $date]);
        $bookedTimes = [];
        foreach ($existingBookings as $booking) {
            $bookedTimes[] = $booking->getStartTime()->format('H:i');
        }

        $createdSlots = $this->slotRepository->findByDate($date);
        foreach ($createdSlots as $slot) {
            if ($slot->isBlocked()) {
                continue;
            }

            // Check if the slot is too close to now
            $slotStart = \DateTime::createFromInterface($slot->getStartTime());
            $slotFullStart = \DateTime::createFromInterface($slot->getDate());
            $slotFullStart->setTime((int)$slotStart->format('H'), (int)$slotStart->format('i'), (int)$slotStart->format('s'));

            if ($this->minimumBookingNotice > 0) {
                $minAllowedTime = (clone $now)->modify('+' . $this->minimumBookingNotice . ' minutes');
                if ($slotFullStart < $minAllowedTime) {
                    continue;
                }
            } elseif ($slotFullStart < $now) {
                continue;
            }

            $timeStr = $slot->getStartTime()->format('H:i');
            if (!in_array($timeStr, $bookedTimes)) {
                $slots[] = [
                    'id' => $slot->getId(),
                    'start' => $slotStart,
                    'end' => \DateTime::createFromInterface($slot->getEndTime()),
                ];
            }
        }

        return $slots;
    }

    /**
     * @return string[] Array of date strings (Y-m-d) that have at least one available slot
     */
    public function getAvailableDaysInRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $slots = $this->slotRepository->findInRange($startDate, $endDate);
        $bookings = $this->bookingRepository->findInRange($startDate, $endDate);
        $now = new \DateTime();

        $bookedMap = [];
        foreach ($bookings as $booking) {
            $dateStr = $booking->getDate()->format('Y-m-d');
            $timeStr = $booking->getStartTime()->format('H:i');
            $bookedMap[$dateStr][$timeStr] = true;
        }

        $availableDays = [];
        foreach ($slots as $slot) {
            if ($slot->isBlocked()) {
                continue;
            }

            // Check if the slot is too close to now
            $slotStart = \DateTime::createFromInterface($slot->getStartTime());
            $slotFullStart = \DateTime::createFromInterface($slot->getDate());
            $slotFullStart->setTime((int)$slotStart->format('H'), (int)$slotStart->format('i'), (int)$slotStart->format('s'));

            if ($this->minimumBookingNotice > 0) {
                $minAllowedTime = (clone $now)->modify('+' . $this->minimumBookingNotice . ' minutes');
                if ($slotFullStart < $minAllowedTime) {
                    continue;
                }
            } elseif ($slotFullStart < $now) {
                continue;
            }

            $dateStr = $slot->getDate()->format('Y-m-d');
            $timeStr = $slot->getStartTime()->format('H:i');

            if (!isset($bookedMap[$dateStr][$timeStr])) {
                $availableDays[$dateStr] = true;
            }
        }

        return array_keys($availableDays);
    }
}
