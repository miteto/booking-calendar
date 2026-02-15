<?php

namespace App\Controller;

use App\Form\BookingType;
use App\Entity\Booking;
use App\Service\BookingService;
use App\Service\SiteSettingService;
use App\Repository\SlotRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class BookingController extends AbstractController
{
    #[Route('/{year}/{month}', name: 'app_home', requirements: ['year' => '\d{4}', 'month' => '\d{1,2}'], defaults: ['year' => null, 'month' => null])]
    public function index(Request $request, BookingService $bookingService, BookingRepository $bookingRepository, SiteSettingService $siteSettings, ?int $year = null, ?int $month = null): Response
    {
        $selectedDateStr = $request->query->get('date');
        $selectedDate = $selectedDateStr ? new \DateTime($selectedDateStr) : null;

        $defaultMonth = $selectedDate ? (int)$selectedDate->format('m') : (int)date('m');
        $defaultYear = $selectedDate ? (int)$selectedDate->format('Y') : (int)date('Y');

        $month = $month ?? (int) $request->query->get('month', $defaultMonth);
        $year = $year ?? (int) $request->query->get('year', $defaultYear);

        $currentMonth = new \DateTime("$year-$month-01");
        $currentMonth->setTime(0, 0, 0);
        $prevMonth = (clone $currentMonth)->modify('-1 month');
        $nextMonth = (clone $currentMonth)->modify('+1 month');

        $today = new \DateTime('today');
        $firstDayOfThisMonth = new \DateTime('first day of this month');
        $firstDayOfThisMonth->setTime(0, 0, 0);
        $canGoPrev = $prevMonth >= $firstDayOfThisMonth;

        $slots = [];
        $bookings = [];
        if ($selectedDate) {
            $slots = $bookingService->getAvailableSlots($selectedDate);
            $bookings = $bookingRepository->findBy(['date' => $selectedDate], ['startTime' => 'ASC']);
        }

        $daysInMonth = (int)$currentMonth->format('t');
        $firstDayOfMonth = clone $currentMonth;
        $startDayOfWeek = (int)$firstDayOfMonth->format('w'); // 0 (Sun) to 6 (Sat)
        // Adjust to Monday as first day (0=Mon, 6=Sun)
        $startDayOfWeek = ($startDayOfWeek + 6) % 7;

        $days = [];
        // Add padding for the first week
        for ($i = 0; $i < $startDayOfWeek; $i++) {
            $days[] = null;
        }

        for ($j = 1; $j <= $daysInMonth; $j++) {
            $day = new \DateTime($currentMonth->format('Y-m-') . $j);
            $day->setTime(0, 0, 0);
            $days[] = $day;
        }

        $startDate = clone $currentMonth;
        $endDate = (clone $currentMonth)->modify('last day of this month');
        $availableDays = $bookingService->getAvailableDaysInRange($startDate, $endDate);

        $locale = $request->getLocale();
        $lang = str_starts_with($locale, 'bg') ? 'bg' : 'en';
        $reservationDetails = $siteSettings->get('reservation_details_' . $lang, '');

        $form = $this->createForm(BookingType::class);

        return $this->render('booking/index.html.twig', [
            'currentMonth' => $currentMonth,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'canGoPrev' => $canGoPrev,
            'days' => $days,
            'selectedDate' => $selectedDate,
            'slots' => $slots,
            'bookings' => $bookings,
            'today' => $today,
            'availableDays' => $availableDays,
            'reservationDetails' => $reservationDetails,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/book', name: 'app_book', methods: ['POST'])]
    public function book(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, SlotRepository $slotRepository, TranslatorInterface $translator, SiteSettingService $siteSettings): Response
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', $translator->trans('flash.invalid_form'));
            return $this->redirectToRoute('app_home');
        }

        $selectedDate = $form->get('date')->getData();
        $slotId = $form->get('slot_id')->getData();
        $embed = $form->get('embed')->getData();

        // Prefer robust server-side resolution via Slot ID
        $slot = null;
        if ($slotId) {
            $slot = $slotRepository->find($slotId);
        }

        if (!$slot) {
            // Fallback: resolve via date + start time if slot_id not provided
            $date = $selectedDate ? new \DateTime($selectedDate) : null;
            $startTimeParam = $form->get('start_time')->getData();
            if (!$date || !$startTimeParam) {
                $this->addFlash('danger', $translator->trans('flash.slot_not_available'));
                return $this->redirectToRoute('app_home', [
                    'embed' => $embed,
                    'date' => $selectedDate ?: (new \DateTime('today'))->format('Y-m-d'),
                ]);
            }
            $startTime = new \DateTime($startTimeParam);
            $slot = $slotRepository->findOneByDateAndStart($date, $startTime);
        }

        if (!$slot || $slot->isBlocked()) {
            $this->addFlash('danger', $translator->trans('flash.slot_not_available'));
            return $this->redirectToRoute('app_home', [
                'embed' => $embed,
                'date' => $selectedDate ?: ($slot?->getDate()?->format('Y-m-d') ?? (new \DateTime('today'))->format('Y-m-d')),
            ]);
        }

        // Check minimum booking notice
        $minNotice = $this->getParameter('app.minimum_booking_notice');
        $now = new \DateTime();
        $slotFullStart = \DateTime::createFromInterface($slot->getDate());
        $slotStart = \DateTime::createFromInterface($slot->getStartTime());
        $slotFullStart->setTime((int)$slotStart->format('H'), (int)$slotStart->format('i'), (int)$slotStart->format('s'));

        if ($minNotice > 0) {
            $minAllowedTime = (clone $now)->modify('+' . $minNotice . ' minutes');
            if ($slotFullStart < $minAllowedTime) {
                $this->addFlash('danger', $translator->trans('flash.too_late_to_book'));
                return $this->redirectToRoute('app_home', [
                    'embed' => $embed,
                    'date' => $slot->getDate()->format('Y-m-d'),
                ]);
            }
        } elseif ($slotFullStart < $now) {
            $this->addFlash('danger', $translator->trans('flash.too_late_to_book'));
            return $this->redirectToRoute('app_home', [
                'embed' => $embed,
                'date' => $slot->getDate()->format('Y-m-d'),
            ]);
        }

        $date = \DateTime::createFromInterface($slot->getDate());
        $startTime = \DateTime::createFromInterface($slot->getStartTime());
        $endTime = \DateTime::createFromInterface($slot->getEndTime());

        // Check existing booking
        $existing = $entityManager->getRepository(Booking::class)->findOneBy([
            'date' => $date,
            'startTime' => $startTime,
        ]);
        if ($existing) {
            $this->addFlash('danger', $translator->trans('flash.slot_just_booked'));
            return $this->redirectToRoute('app_home', [
                'embed' => $embed,
                'date' => $date->format('Y-m-d'),
            ]);
        }

        $booking->setDate($date);
        $booking->setStartTime($startTime);
        $booking->setEndTime($endTime);
        $booking->setLocale($request->getLocale());

        $entityManager->persist($booking);
        $entityManager->flush();

        // Send Emails
        $name = $booking->getUserName();
        $email = $booking->getUserEmail();
        $phone = $booking->getUserPhone();

        $locale = $request->getLocale();
        $lang = str_starts_with($locale, 'bg') ? 'bg' : 'en';
        $emailTemplate = $siteSettings->get('email_template_' . $lang);
        $adminEmailAddress = $this->getParameter('app.admin_email');
        $noreplyEmailAddress = $this->getParameter('app.noreply_email');

        $userEmail = (new Email())
            ->from(new Address($noreplyEmailAddress, $translator->trans('site.name')))
            ->to($email)
            ->replyTo($adminEmailAddress)
            ->subject($translator->trans('email.user.subject'));

        if ($emailTemplate) {
            $bodyHtml = strtr($emailTemplate, [
                '%name%' => $name,
                '%email%' => $email,
                '%phone%' => $phone,
                '%date%' => $date->format('Y-m-d'),
                '%time%' => $startTime->format('H:i'),
            ]);
            $userEmail->html($bodyHtml)->text(strip_tags($bodyHtml));
        } else {
            $userEmail->text($translator->trans('email.user.body', [
                '%name%' => $name,
                '%email%' => $email,
                '%phone%' => $phone,
                '%date%' => $date->format('Y-m-d'),
                '%time%' => $startTime->format('H:i'),
            ]));
        }

        // Create admin email as a copy of user email but with admin recipient
        $adminEmail = (clone $userEmail)
            ->to($adminEmailAddress)
            ->replyTo($email);

        try {
            $mailer->send($userEmail);
            $mailer->send($adminEmail);
        } catch (\Exception $e) {
            // Log error or handle it, but the booking is saved
        }

        $this->addFlash('success', $translator->trans('flash.booking_confirmed'));

        return $this->redirectToRoute('app_home', [
            'embed' => $embed
        ]);
    }
}
