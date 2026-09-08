/**
 * Public-site tracking beacon (steps 34-35). Loaded as its own tiny,
 * dependency-free bundle via a plain `<script defer>` tag (see
 * template/default/templates/base.html.twig) - deliberately not part of the
 * `default` React entry, so tracking can never add to the main bundle's
 * parse/load cost and never runs synchronously on page load, per the
 * roadmap's explicit constraint. All collection is AJAX-only: fetch() for
 * the initial page view, sendBeacon() for everything that fires on
 * unload/hide, where reliability matters more than reading the response.
 *
 * Because the public site is a client-side-routed SPA, a "page view" is
 * re-armed on every history.pushState/replaceState/popstate, not just on
 * the initial full page load.
 */
(function () {
    'use strict';

    var API_BASE = '/api/stats';

    var currentPageViewId = null;
    var pageStartTime = null;
    var maxScrollPercent = 0;
    var lastTrackedUrl = null;

    /** @return {string} the current path+query, used both to send and to de-duplicate navigations */
    function currentUrl() {
        return window.location.pathname + window.location.search;
    }

    /** @return {number} how far down the page the visitor has scrolled, 0-100 */
    function computeScrollPercent() {
        var doc = document.documentElement;
        var scrollTop = window.scrollY || doc.scrollTop || 0;
        var scrollHeight = Math.max(doc.scrollHeight - window.innerHeight, 1);

        return Math.max(0, Math.min(100, Math.round((scrollTop / scrollHeight) * 100)));
    }

    function onScroll() {
        var percent = computeScrollPercent();
        if (percent > maxScrollPercent) {
            maxScrollPercent = percent;
        }
    }

    /**
     * Sends a JSON payload without waiting for/reading a response.
     * Prefers navigator.sendBeacon (survives page unload); falls back to a
     * keepalive fetch for browsers/contexts where it isn't available.
     *
     * @param {string} url
     * @param {object} payload
     */
    function sendJson(url, payload) {
        var body = JSON.stringify(payload);

        if (navigator.sendBeacon) {
            navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: body,
            keepalive: true,
        }).catch(function () {});
    }

    /** Sends the accumulated time-on-page/scroll for the page view currently being tracked, if any. */
    function finalizeCurrentPageView() {
        if (!currentPageViewId || !pageStartTime) {
            return;
        }

        sendJson(API_BASE + '/pageview/' + currentPageViewId + '/heartbeat', {
            timeOnPageSeconds: (Date.now() - pageStartTime) / 1000,
            maxScrollPercent: maxScrollPercent,
        });
    }

    /** Finalizes the previous page view (if any) and starts tracking a new one. */
    function startNewPageView() {
        finalizeCurrentPageView();

        currentPageViewId = null;
        pageStartTime = Date.now();
        maxScrollPercent = 0;

        fetch(API_BASE + '/pageview', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                url: currentUrl(),
                referrer: document.referrer || null,
                screenWidth: window.screen ? window.screen.width : null,
                screenHeight: window.screen ? window.screen.height : null,
            }),
        })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (data) {
                if (data && data.id) {
                    currentPageViewId = data.id;
                }
            })
            .catch(function () {});
    }

    /** Re-arms tracking on an SPA navigation, skipping no-op calls (e.g. a replaceState to the same URL). */
    function handleNavigation() {
        var url = currentUrl();
        if (url === lastTrackedUrl) {
            return;
        }
        lastTrackedUrl = url;
        startNewPageView();
    }

    /**
     * Walks up from `element` to find the nearest tracked interactive
     * ancestor (a link, button, or explicit button-like control).
     *
     * @param {Element|null} element
     * @return {Element|null}
     */
    function findTrackedAncestor(element) {
        while (element && element !== document.body) {
            var tag = element.tagName;
            if (tag === 'A' || tag === 'BUTTON' || element.getAttribute('role') === 'button' || element.getAttribute('type') === 'submit') {
                return element;
            }
            element = element.parentElement;
        }

        return null;
    }

    function onClick(event) {
        var target = findTrackedAncestor(event.target);
        if (!target || !currentPageViewId) {
            return;
        }

        var isLink = target.tagName === 'A';
        sendJson(API_BASE + '/click', {
            pageViewId: currentPageViewId,
            elementType: isLink ? 'link' : 'button',
            label: (target.textContent || '').trim().slice(0, 255),
            targetUrl: isLink ? target.getAttribute('href') : null,
        });
    }

    /** Wraps a History API method so SPA navigations dispatch a plain DOM event tracker.js can listen for. */
    function patchHistoryMethod(methodName) {
        var original = window.history[methodName];
        window.history[methodName] = function () {
            var result = original.apply(this, arguments);
            window.dispatchEvent(new Event('statscms:navigation'));
            return result;
        };
    }

    patchHistoryMethod('pushState');
    patchHistoryMethod('replaceState');
    window.addEventListener('popstate', function () {
        window.dispatchEvent(new Event('statscms:navigation'));
    });
    window.addEventListener('statscms:navigation', handleNavigation);

    window.addEventListener('scroll', onScroll, { passive: true });
    document.addEventListener('click', onClick, true);
    window.addEventListener('pagehide', finalizeCurrentPageView);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            finalizeCurrentPageView();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', handleNavigation);
    } else {
        handleNavigation();
    }
})();
