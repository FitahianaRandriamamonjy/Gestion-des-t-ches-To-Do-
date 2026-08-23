<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\NotificationService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private const AVATAR_COLORS = [
        '#7C6FEF', '#4EA8DE', '#2FBE7F', '#F5A623', '#EF5350', '#9B59B6', '#1ABC9C', '#E67E22',
    ];

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly Security $security,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('initials', [$this, 'initials']),
            new TwigFilter('avatar_color', [$this, 'avatarColor']),
            new TwigFilter('time_ago', [$this, 'timeAgo']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_notifications', [$this, 'getNotifications']),
            new TwigFunction('app_notifications_count', [$this, 'getNotificationsCount']),
            new TwigFunction('app_productivity', [$this, 'getCurrentUserProductivity']),
        ];
    }

    public function initials(?string $fullName): string
    {
        if (!$fullName) {
            return '?';
        }

        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $parts = array_filter($parts);

        if (count($parts) === 0) {
            return '?';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        $first = mb_substr(reset($parts), 0, 1);
        $last = mb_substr(end($parts), 0, 1);

        return mb_strtoupper($first.$last);
    }

    public function avatarColor(?string $seed): string
    {
        if (!$seed) {
            return self::AVATAR_COLORS[0];
        }

        $index = crc32($seed) % count(self::AVATAR_COLORS);

        return self::AVATAR_COLORS[abs($index)];
    }

    public function timeAgo(\DateTimeInterface $date): string
    {
        $diff = (new \DateTimeImmutable())->diff($date);

        if ($diff->y > 0) {
            return sprintf('il y a %d an%s', $diff->y, $diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            return sprintf('il y a %d mois', $diff->m);
        }
        if ($diff->d > 0) {
            return sprintf('il y a %d j', $diff->d);
        }
        if ($diff->h > 0) {
            return sprintf('il y a %dh', $diff->h);
        }
        if ($diff->i > 0) {
            return sprintf('il y a %d min', $diff->i);
        }

        return 'à l\'instant';
    }

    public function getNotifications(): array
    {
        $user = $this->security->getUser();

        return $this->notificationService->getNotifications($user instanceof User ? $user : null);
    }

    public function getNotificationsCount(): int
    {
        return count($this->getNotifications());
    }

    public function getCurrentUserProductivity(): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return 0;
        }

        return $this->notificationService->computeProductivity($user);
    }
}
