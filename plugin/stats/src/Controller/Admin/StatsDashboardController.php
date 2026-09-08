<?php

namespace Plugin\Stats\Controller\Admin;

use Plugin\Stats\Repository\ClickEventRepository;
use Plugin\Stats\Repository\PageViewRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin dashboard data (step 36): a single aggregated summary rather than
 * one endpoint per metric, since the dashboard renders them all together
 * and they all share the same optional date range. Already gated by the
 * existing ^/api/admin ROLE_SUPER_ADMIN access_control rule.
 */
class StatsDashboardController
{
    public function __construct(
        private readonly PageViewRepository $pageViewRepository,
        private readonly ClickEventRepository $clickEventRepository,
    ) {
    }

    /**
     * @return JsonResponse aggregated metrics over an optional ?from=&to= (ISO 8601) range
     */
    #[Route('/api/admin/stats/summary', name: 'admin_stats_summary', methods: ['GET'])]
    public function summary(Request $request): JsonResponse
    {
        $from = $this->parseDate($request->query->get('from'));
        $to = $this->parseDate($request->query->get('to'));

        return new JsonResponse([
            'totalPageViews' => $this->pageViewRepository->countTotal($from, $to),
            'avgTimeOnPageSeconds' => $this->pageViewRepository->averageTimeOnPage($from, $to),
            'avgScrollPercent' => $this->pageViewRepository->averageScrollPercent($from, $to),
            'topPages' => $this->pageViewRepository->topPages(10, $from, $to),
            'browsers' => $this->pageViewRepository->breakdown('browserName', $from, $to),
            'operatingSystems' => $this->pageViewRepository->breakdown('osName', $from, $to),
            'languages' => $this->pageViewRepository->breakdown('language', $from, $to),
            'countries' => $this->pageViewRepository->breakdown('country', $from, $to),
            'deviceTypes' => $this->pageViewRepository->breakdown('deviceType', $from, $to),
            'botVsHuman' => $this->pageViewRepository->botHumanCounts($from, $to),
            'topBots' => $this->pageViewRepository->topBots(10, $from, $to),
            'topClicks' => $this->clickEventRepository->topClicks(10, $from, $to),
        ]);
    }

    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
