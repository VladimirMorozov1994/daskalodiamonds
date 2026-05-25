<?php
/**
 * Plugin Name: Daskalo Flashy Popup Control
 * Description: Delays Flashy popup display and suppresses it for paid traffic landing on product pages.
 * Version: 1.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flashy loads thunder.js in wp_head with default priority.
 * We need priority 0 so this code runs before Flashy tries to open the popup.
 */
add_action('wp_head', function () {
    if (is_admin()) {
        return;
    }

    $is_product_page = function_exists('is_product') && is_product();

    $paid_params = [
        'gclid',
        'gbraid',
        'wbraid',
        'fbclid',
        'msclkid',
        'ttclid',
        'twclid',
        'li_fat_id',
    ];

    $has_paid_param = false;

    foreach ($paid_params as $param) {
        if (!empty($_GET[$param])) {
            $has_paid_param = true;
            break;
        }
    }

    $utm_medium = isset($_GET['utm_medium'])
        ? strtolower(sanitize_text_field(wp_unslash($_GET['utm_medium'])))
        : '';

    $utm_campaign = isset($_GET['utm_campaign'])
        ? strtolower(sanitize_text_field(wp_unslash($_GET['utm_campaign'])))
        : '';

    $paid_mediums = [
        'cpc',
        'ppc',
        'paid',
        'paid-search',
        'paid_search',
        'paid-social',
        'paid_social',
        'display',
        'remarketing',
        'retargeting',
    ];

    $is_paid_traffic = $has_paid_param
        || in_array($utm_medium, $paid_mediums, true)
        || strpos($utm_medium, 'paid') !== false
        || strpos($utm_medium, 'cpc') !== false
        || strpos($utm_medium, 'ppc') !== false
        || strpos($utm_campaign, 'paid') !== false
        || strpos($utm_campaign, 'cpc') !== false
        || strpos($utm_campaign, 'ppc') !== false;

    /**
     * Requirement:
     * Users arriving from paid ads directly to product pages
     * should not see the popup during the initial session.
     */
    $suppress_for_paid_product = $is_product_page && $is_paid_traffic;
    ?>

    <style>
        html.dd-flashy-popup-blocked flashy-popup {
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
    </style>

    <script>
        (function () {
            'use strict';

            var DELAY_MS = 30000;
            var SESSION_SUPPRESS_KEY = 'dd_flashy_popup_suppressed_paid_product';

            var suppressCurrentLanding = <?php echo $suppress_for_paid_product ? 'true' : 'false'; ?>;

            var allowPopup = false;
            var pendingPopup = null;

            var nativeShowPopover = HTMLElement.prototype.showPopover;
            var nativeHidePopover = HTMLElement.prototype.hidePopover;

            function sessionGet(key) {
                try {
                    return window.sessionStorage.getItem(key);
                } catch (e) {
                    return null;
                }
            }

            function sessionSet(key, value) {
                try {
                    window.sessionStorage.setItem(key, value);
                } catch (e) {}
            }

            if (suppressCurrentLanding) {
                sessionSet(SESSION_SUPPRESS_KEY, '1');
            }

            var shouldSuppressForSession = sessionGet(SESSION_SUPPRESS_KEY) === '1';

            document.documentElement.classList.add('dd-flashy-popup-blocked');

            function isFlashyPopup(element) {
                return !!(
                    element &&
                    element.matches &&
                    element.matches('flashy-popup')
                );
            }

            function blockPopupLayer() {
                document.documentElement.classList.add('dd-flashy-popup-blocked');
            }

            function unblockPopupLayer() {
                document.documentElement.classList.remove('dd-flashy-popup-blocked');
            }

            function getFlashyPopup() {
                return pendingPopup || document.querySelector('flashy-popup');
            }

            function hidePopupElement(popup) {
                if (!popup) {
                    return;
                }

                try {
                    if (typeof nativeHidePopover === 'function') {
                        nativeHidePopover.call(popup);
                    } else if (typeof popup.hidePopover === 'function') {
                        popup.hidePopover();
                    }
                } catch (e) {}
            }

            function hideAllFlashyPopups() {
                var popups = document.querySelectorAll('flashy-popup');

                popups.forEach(function (popup) {
                    hidePopupElement(popup);
                });
            }

            function showFlashyPopup() {
                if (shouldSuppressForSession) {
                    blockPopupLayer();
                    hideAllFlashyPopups();
                    return;
                }

                unblockPopupLayer();

                var popup = getFlashyPopup();

                if (!popup) {
                    return;
                }

                try {
                    if (typeof nativeShowPopover === 'function') {
                        nativeShowPopover.call(popup);
                    } else if (typeof popup.showPopover === 'function') {
                        popup.showPopover();
                    }
                } catch (e) {}
            }

            function allowAndShowPopup() {
                if (shouldSuppressForSession || allowPopup) {
                    return;
                }

                allowPopup = true;

                /**
                 * Important:
                 * Remove blocking class even if <flashy-popup> does not exist yet.
                 * Flashy can create the popup later.
                 */
                unblockPopupLayer();

                showFlashyPopup();

                document.removeEventListener('mouseout', handleExitIntent);
            }

            function handleExitIntent(event) {
                if (event.relatedTarget === null && event.clientY <= 10) {
                    allowAndShowPopup();
                }
            }

            /**
             * Intercept Flashy native popover opening.
             * If Flashy tries to open the popup immediately, we stop it.
             */
            if (typeof nativeShowPopover === 'function') {
                HTMLElement.prototype.showPopover = function () {
                    if (isFlashyPopup(this)) {
                        pendingPopup = this;

                        if (shouldSuppressForSession) {
                            blockPopupLayer();
                            hidePopupElement(this);
                            return;
                        }

                        if (!allowPopup) {
                            blockPopupLayer();
                            hidePopupElement(this);
                            return;
                        }

                        unblockPopupLayer();
                    }

                    return nativeShowPopover.apply(this, arguments);
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                if (shouldSuppressForSession) {
                    blockPopupLayer();
                    hideAllFlashyPopups();
                    return;
                }

                blockPopupLayer();
                hideAllFlashyPopups();

                window.setTimeout(function () {
                    allowAndShowPopup();
                }, DELAY_MS);

                document.addEventListener('mouseout', handleExitIntent);
            });

            window.addEventListener('load', function () {
                if (shouldSuppressForSession) {
                    blockPopupLayer();
                    hideAllFlashyPopups();
                    return;
                }

                if (!allowPopup) {
                    blockPopupLayer();
                    hideAllFlashyPopups();
                }
            });

            /**
             * Safety check:
             * If Flashy creates the popup after our initial events,
             * keep it hidden before delay / paid suppression.
             */
            var observer = new MutationObserver(function () {
                if (shouldSuppressForSession) {
                    blockPopupLayer();
                    hideAllFlashyPopups();
                    return;
                }

                if (!allowPopup) {
                    blockPopupLayer();
                    hideAllFlashyPopups();
                }
            });

            document.addEventListener('DOMContentLoaded', function () {
                if (document.body) {
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }
            });
        })();
    </script>
    <?php
}, 0);