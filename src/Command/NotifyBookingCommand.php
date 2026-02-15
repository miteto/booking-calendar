<?php

namespace App\Command;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Service\SiteSettingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:notify-bookings',
    description: 'Notify clients about their upcoming bookings',
)]
class NotifyBookingCommand extends Command
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private MailerInterface $mailer,
        private SiteSettingService $siteSettings,
        private TranslatorInterface $translator,
        private string $notificationHours,
        private string $noreplyEmail
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hours = array_filter(array_map('trim', explode(',', $this->notificationHours)));

        $now = new \DateTime();
        $notificationsSentCount = 0;
        $emails = [];

        foreach ($hours as $hour) {
            $targetTime = (clone $now)->modify("+{$hour} hours");
            $targetDate = $targetTime->format('Y-m-d');
            $targetTimeStr = $targetTime->format('H:i:00');

            $bookings = $this->bookingRepository->createQueryBuilder('b')
                ->where('b.date = :date')
                ->andWhere('b.startTime = :time')
                ->setParameter('date', $targetDate)
                ->setParameter('time', $targetTimeStr)
                ->getQuery()
                ->getResult();

            /** @var Booking $booking */
            foreach ($bookings as $booking) {
                try {
                    $this->sendNotification($booking, (int)$hour);
                    $notificationsSentCount++;
                    $emails[] = $booking->getUserEmail();
                } catch (\Exception $e) {
                    $io->error(sprintf('Failed to notify booking #%d: %s', $booking->getId(), $e->getMessage()));
                }
            }
        }

        if ($notificationsSentCount > 0) {
            $io->success(sprintf('Sent %d notifications - %s', $notificationsSentCount, implode(', ', $emails)));
        } else {
            $io->info('No notifications needed to be sent at ' . $now->format('Y-m-d H:i:s'));
        }

        return Command::SUCCESS;
    }

    private function sendNotification(Booking $booking, int $hourWindow): void
    {
        $emailAddress = $booking->getUserEmail();
        if (!$emailAddress) {
            return;
        }

        $locale = $booking->getLocale() ?? 'en';
        $lang = str_starts_with($locale, 'bg') ? 'bg' : 'en';

        $template = $this->siteSettings->get('reminder_email_template_' . $lang);
        if (!$template) {
            // Fallback to other language if this one is empty
            $otherLang = ($lang === 'en' ? 'bg' : 'en');
            $template = $this->siteSettings->get('reminder_email_template_' . $otherLang);
            if ($template) { $lang = $otherLang; }
        }

        $subject = $this->translator->trans('email.reminder.subject', [], null, $lang);

        $email = (new Email())
            ->from(new Address($this->noreplyEmail, $this->translator->trans('site.name', [], null, $lang)))
            ->to($emailAddress)
            ->subject($subject);

        $placeholders = [
            '%name%' => $booking->getUserName(),
            '%email%' => $booking->getUserEmail(),
            '%phone%' => $booking->getUserPhone(),
            '%date%' => $booking->getDate()->format('Y-m-d'),
            '%time%' => $booking->getStartTime()->format('H:i'),
            '%hours%' => $hourWindow,
        ];

        if ($template) {
            $bodyHtml = strtr($template, $placeholders);
            $email->html($bodyHtml)->text(strip_tags($bodyHtml));
        } else {
            $bodyText = $this->translator->trans('email.reminder.body', $placeholders, null, $lang);
            $email->text($bodyText);
        }

        $this->mailer->send($email);
    }
}
