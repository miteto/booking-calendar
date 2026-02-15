<?php

namespace App\Controller;

use App\Form\BookingAdminType;
use App\Form\GenerateSlotsType;
use App\Form\SiteSettingsType;
use App\Form\SlotConfigsType;
use App\Entity\Booking;
use App\Entity\Slot;
use App\Entity\SlotConfig;
use App\Repository\BookingRepository;
use App\Repository\SlotConfigRepository;
use App\Repository\SlotRepository;
use App\Service\SiteSettingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function index(BookingRepository $bookingRepository, Request $request): Response
    {
        $fromStr = $request->query->get('from');
        $toStr = $request->query->get('to');
        $search = $request->query->get('search');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;

        $from = $fromStr ? new \DateTime($fromStr) : null;
        $to = $toStr ? new \DateTime($toStr) : null;

        $paginator = $bookingRepository->findFiltered($from, $to, $search, $page, $limit);
        $totalItems = count($paginator);
        $pagesCount = ceil($totalItems / $limit);

        return $this->render('admin/index.html.twig', [
            'bookings' => $paginator,
            'currentPage' => $page,
            'pagesCount' => $pagesCount,
            'from' => $fromStr,
            'to' => $toStr,
            'search' => $search,
        ]);
    }

    #[Route('/config', name: 'app_admin_config')]
    public function config(SlotConfigRepository $slotConfigRepository, Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'global'];
        $configs = [];
        foreach ($days as $day) {
            $config = $slotConfigRepository->findOneBy(['dayOfWeek' => $day === 'global' ? null : $day]);
            if (!$config) {
                $config = new SlotConfig();
                $config->setDayOfWeek($day === 'global' ? null : $day);
            }
            $configs[$day] = $config;
        }

        $form = $this->createForm(SlotConfigsType::class, $configs, ['days' => $days]);
        // Set 'enabled' based on whether it was already in DB
        foreach ($days as $day) {
            if ($configs[$day]->getId()) {
                $form->get($day)->get('enabled')->setData(true);
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($days as $day) {
                $configForm = $form->get($day);
                $enabled = $configForm->get('enabled')->getData();
                /** @var SlotConfig $config */
                $config = $configs[$day];

                if (!$enabled) {
                    if ($config->getId()) {
                        $entityManager->remove($config);
                    }
                    continue;
                }

                $entityManager->persist($config);
            }
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('admin.configuration_updated'));
            return $this->redirectToRoute('app_admin_config');
        }

        return $this->render('admin/config.html.twig', [
            'form' => $form->createView(),
            'days' => $days,
        ]);
    }

    #[Route('/booking/delete/{id}', name: 'app_admin_booking_delete', methods: ['POST'])]
    public function deleteBooking(Booking $booking, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $entityManager->remove($booking);
        $entityManager->flush();
        $this->addFlash('success', $translator->trans('admin.booking_deleted'));
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/booking/add', name: 'app_admin_booking_add')]
    #[Route('/booking/edit/{id}', name: 'app_admin_booking_edit')]
    public function editBooking(?Booking $booking, Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        if (!$booking) {
            $booking = new Booking();
        }

        $form = $this->createForm(BookingAdminType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($booking);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('admin.booking_saved'));
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/booking_edit.html.twig', [
            'booking' => $booking,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/settings', name: 'app_admin_settings')]
    public function settings(Request $request, SiteSettingService $settings, TranslatorInterface $translator): Response
    {
        $data = [
            'reservation_details_en' => $settings->get('reservation_details_en', ''),
            'reservation_details_bg' => $settings->get('reservation_details_bg', ''),
            'email_template_en' => $settings->get('email_template_en', ''),
            'email_template_bg' => $settings->get('email_template_bg', ''),
            'reminder_email_template_en' => $settings->get('reminder_email_template_en', ''),
            'reminder_email_template_bg' => $settings->get('reminder_email_template_bg', ''),
        ];

        $form = $this->createForm(SiteSettingsType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $settings->set('reservation_details_en', $formData['reservation_details_en']);
            $settings->set('reservation_details_bg', $formData['reservation_details_bg']);
            $settings->set('email_template_en', $formData['email_template_en']);
            $settings->set('email_template_bg', $formData['email_template_bg']);
            $settings->set('reminder_email_template_en', $formData['reminder_email_template_en']);
            $settings->set('reminder_email_template_bg', $formData['reminder_email_template_bg']);

            $this->addFlash('success', $translator->trans('admin.settings.saved'));
            return $this->redirectToRoute('app_admin_settings');
        }

        return $this->render('admin/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/availability/{date}', name: 'app_admin_availability', defaults: ['date' => null])]
    public function availability(?string $date, Request $request, SlotRepository $slotRepository, BookingRepository $bookingRepository): Response
    {
        $date = $date ? new \DateTime($date) : new \DateTime('today');
        $date->setTime(0, 0, 0);

        $slots = $slotRepository->findByDate($date);

        // index bookings by start time (H:i:s) for quick lookups in template
        $bookings = $bookingRepository->findBy(['date' => $date]);
        $bookedTimes = [];
        foreach ($bookings as $b) {
            $bookedTimes[$b->getStartTime()->format('H:i:s')] = true;
        }

        return $this->render('admin/availability.html.twig', [
            'date' => $date,
            'slots' => $slots,
            'bookedTimes' => $bookedTimes,
        ]);
    }

    #[Route('/availability/block-day', name: 'app_admin_block_day', methods: ['POST'])]
    public function blockDay(Request $request, SlotRepository $slotRepository, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $dateStr = $request->request->get('date');
        if (!$dateStr) {
            $this->addFlash('error', $translator->trans('admin.date_required'));
            return $this->redirectToRoute('app_admin_availability');
        }
        $date = new \DateTime($dateStr);
        $date->setTime(0, 0, 0);

        $block = (bool)$request->request->get('block', true);

        $slots = $slotRepository->findByDate($date);
        foreach ($slots as $slot) {
            $slot->setBlocked($block);
            $em->persist($slot);
        }
        $em->flush();

        $this->addFlash('success', $block ? $translator->trans('admin.blocked_day') : $translator->trans('admin.unblocked_day'));

        return $this->redirectToRoute('app_admin_availability', ['date' => $date->format('Y-m-d')]);
    }

    #[Route('/availability/toggle/{id}', name: 'app_admin_toggle_slot', methods: ['POST'])]
    public function toggleSlot(Slot $slot, Request $request, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $block = (bool)$request->request->get('block', true);
        $slot->setBlocked($block);
        $em->persist($slot);
        $em->flush();

        $date = $request->request->get('date') ?: $slot->getDate()->format('Y-m-d');
        $this->addFlash('success', $block ? $translator->trans('admin.slot_blocked') : $translator->trans('admin.slot_unblocked'));

        return $this->redirectToRoute('app_admin_availability', ['date' => $date]);
    }

    #[Route('/generate-slots', name: 'app_admin_generate_slots')]
    public function generateSlots(Request $request, SlotRepository $slotRepository, SlotConfigRepository $slotConfigRepository, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(GenerateSlotsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $startDate = $data['start_date'];
            $startDate->setTime(0, 0, 0);
            $endDate = $data['end_date'];
            $endDate->setTime(23, 59, 59);
            $force = $data['force'];

            $created = 0;
            $deleted = 0;
            $skipped = 0;

            if ($force) {
                // Find all slots in range
                $slotsInRange = $em->createQuery('SELECT s FROM '.Slot::class.' s WHERE s.date >= :start AND s.date <= :end')
                    ->setParameter('start', $startDate->format('Y-m-d'))
                    ->setParameter('end', $endDate->format('Y-m-d'))
                    ->getResult();

                foreach ($slotsInRange as $slot) {
                    // Check if this slot has a booking
                    $booking = $em->getRepository(Booking::class)->findOneBy([
                        'date' => $slot->getDate(),
                        'startTime' => $slot->getStartTime(),
                    ]);
                    if (!$booking) {
                        $em->remove($slot);
                        $deleted++;
                    }
                }
                $em->flush();
            }

            for ($d = clone $startDate; $d <= $endDate; $d->modify('+1 day')) {
                $dayOfWeek = $d->format('l');

                if (!$force) {
                    // Check if date already has ANY slots
                    $existingSlotsCount = $slotRepository->count(['date' => $d]);
                    if ($existingSlotsCount > 0) {
                        $skipped++;
                        continue;
                    }
                }

                $config = $slotConfigRepository->findOneBy(['dayOfWeek' => $dayOfWeek]);
                if (!$config) {
                    $config = $slotConfigRepository->findOneBy(['dayOfWeek' => null]); // global
                }

                if (!$config) {
                    continue;
                }

                $slotStart = new \DateTime($d->format('Y-m-d') . ' ' . $config->getStartTime()->format('H:i:s'));
                $slotEndBoundary = new \DateTime($d->format('Y-m-d') . ' ' . $config->getEndTime()->format('H:i:s'));
                $interval = (int)$config->getSlotInterval();

                for ($current = clone $slotStart; $current < $slotEndBoundary; $current->modify('+' . $interval . ' minutes')) {
                    $end = (clone $current)->modify('+' . $interval . ' minutes');
                    if ($end > $slotEndBoundary) {
                        break;
                    }

                    // Avoid duplicates (especially if we didn't delete booked slots)
                    $existing = $slotRepository->findOneByDateAndStart($d, $current);
                    if ($existing) {
                        continue;
                    }

                    $slot = (new Slot())
                        ->setDate(clone $d)
                        ->setStartTime(clone $current)
                        ->setEndTime($end);

                    $em->persist($slot);
                    $created++;
                }
            }
            $em->flush();

            $this->addFlash('success', $translator->trans('admin.generate_slots.success', [
                '%created%' => $created,
                '%deleted%' => $deleted,
                '%skipped%' => $skipped,
            ]));

            return $this->redirectToRoute('app_admin_generate_slots');
        }

        return $this->render('admin/generate_slots.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
