<?php

namespace Plugin\Stats\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Plugin\Stats\Entity\ClickEvent;
use Plugin\Stats\Entity\PageView;
use Plugin\Stats\Repository\PageViewRepository;
use Plugin\Stats\Service\GeoIpResolver;
use Plugin\Stats\Service\UserAgentAnalyzer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, unauthenticated tracking endpoints called by the deferred
 * front-end beacon (template/default's tracker.js, plugin/stats/assets) -
 * step 34/35. Everything under /api is PUBLIC_ACCESS by default
 * (security.yaml only locks down /api/admin), so no extra access_control
 * entry is needed here, same as the newsletter public signup endpoint.
 * Every field the client doesn't inherently know better than the server
 * (browser, OS, device, bot, country) is resolved here instead of trusted
 * from the request body - only url/referrer/screen size are client-supplied.
 */
class TrackingController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PageViewRepository $pageViewRepository,
        private readonly UserAgentAnalyzer $userAgentAnalyzer,
        private readonly GeoIpResolver $geoIpResolver,
    ) {
    }

    /**
     * Creates a page view on the initial load/SPA navigation beacon.
     *
     * @return JsonResponse {id: int}
     */
    #[Route('/api/stats/pageview', name: 'stats_pageview_create', methods: ['POST'])]
    public function createPageView(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $userAgentInfo = $this->userAgentAnalyzer->analyze($request->headers->get('User-Agent', ''));
        $country = $this->geoIpResolver->resolveCountry($request->getClientIp() ?? '');

        $pageView = new PageView();
        $pageView->setUrl(mb_substr((string) ($data['url'] ?? ''), 0, 512));
        $pageView->setReferrer(isset($data['referrer']) ? mb_substr((string) $data['referrer'], 0, 512) : null);
        $pageView->setScreenWidth(isset($data['screenWidth']) ? (int) $data['screenWidth'] : null);
        $pageView->setScreenHeight(isset($data['screenHeight']) ? (int) $data['screenHeight'] : null);
        $pageView->setLanguage($request->getPreferredLanguage() ? mb_substr($request->getPreferredLanguage(), 0, 10) : null);
        $pageView->setBrowserName($userAgentInfo['browserName']);
        $pageView->setBrowserVersion($userAgentInfo['browserVersion']);
        $pageView->setOsName($userAgentInfo['osName']);
        $pageView->setOsVersion($userAgentInfo['osVersion']);
        $pageView->setDeviceType($userAgentInfo['deviceType']);
        $pageView->setIsBot($userAgentInfo['isBot']);
        $pageView->setBotName($userAgentInfo['botName']);
        $pageView->setCountry($country);

        $this->entityManager->persist($pageView);
        $this->entityManager->flush();

        return new JsonResponse(['id' => $pageView->getId()], Response::HTTP_CREATED);
    }

    /**
     * Updates a page view with the time spent and max scroll reached, sent
     * via navigator.sendBeacon when the visitor hides/leaves the page.
     * `max()`-merges rather than overwrites so a stray duplicate/earlier
     * beacon can never lower an already-recorded value.
     */
    #[Route('/api/stats/pageview/{id}/heartbeat', name: 'stats_pageview_heartbeat', methods: ['POST'])]
    public function heartbeat(int $id, Request $request): JsonResponse
    {
        $pageView = $this->pageViewRepository->find($id);
        if (null === $pageView) {
            return new JsonResponse(['error' => 'Page view not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['timeOnPageSeconds'])) {
            $pageView->setTimeOnPageSeconds(max($pageView->getTimeOnPageSeconds() ?? 0.0, (float) $data['timeOnPageSeconds']));
        }
        if (isset($data['maxScrollPercent'])) {
            $pageView->setMaxScrollPercent(max($pageView->getMaxScrollPercent() ?? 0, min(100, (int) $data['maxScrollPercent'])));
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    /** Records a click on a tracked button/link - see the tracker script's findTrackedAncestor for what counts as "tracked". */
    #[Route('/api/stats/click', name: 'stats_click_create', methods: ['POST'])]
    public function click(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $pageView = isset($data['pageViewId']) ? $this->pageViewRepository->find((int) $data['pageViewId']) : null;
        if (null === $pageView) {
            return new JsonResponse(['error' => 'Invalid pageViewId.'], Response::HTTP_BAD_REQUEST);
        }

        $click = new ClickEvent();
        $click->setPageView($pageView);
        $click->setElementType('link' === ($data['elementType'] ?? null) ? 'link' : 'button');
        $click->setLabel(isset($data['label']) ? mb_substr((string) $data['label'], 0, 255) : null);
        $click->setTargetUrl(isset($data['targetUrl']) ? mb_substr((string) $data['targetUrl'], 0, 512) : null);

        $this->entityManager->persist($click);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true], Response::HTTP_CREATED);
    }
}
