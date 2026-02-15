<?php

namespace App\Service;

use App\Entity\SiteSetting;
use App\Repository\SiteSettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class SiteSettingService
{
    public function __construct(
        private SiteSettingRepository $siteSettingRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function get(string $name, ?string $default = null): ?string
    {
        $setting = $this->siteSettingRepository->findOneByName($name);
        return $setting ? $setting->getValue() : $default;
    }

    public function set(string $name, ?string $value): void
    {
        $setting = $this->siteSettingRepository->findOneByName($name);
        if (!$setting) {
            $setting = new SiteSetting();
            $setting->setName($name);
        }
        $setting->setValue($value);
        $this->entityManager->persist($setting);
        $this->entityManager->flush();
    }
}
