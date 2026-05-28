<?php
/**
 * Plugin Name: Daskalo Product Card Clickable Elements
 * Description: Makes JetEngine/Elementor product card sale badges and prices clickable.
 * Version: 1.0.3
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function () {
    ?>
    <style>
        .dd-product-price-link,
        .dd-product-sale-badge-link,
        .dd-product-card-overlay-link {
            color: inherit;
            text-decoration: none;
        }

        .dd-product-price-link {
            display: inline-block;
            cursor: pointer;
        }

        .dd-product-sale-badge-link[data-dd-sale-overlay="1"] {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 10;
            display: block;
            cursor: pointer;
            background: transparent;
            font-size: 0;
            line-height: 0;
        }

        .dd-product-card-overlay-link[data-dd-card-overlay="1"] {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 5;
            display: block;
            cursor: pointer;
            background: transparent;
            font-size: 0;
            line-height: 0;
        }

        [data-dd-card-clickable="1"] .jet-woo-builder-archive-add-to-cart,
        [data-dd-card-clickable="1"] .jet-woo-builder-archive-add-to-cart a {
            position: relative;
            z-index: 20;
        }

        [data-dd-card-clickable="1"] .elementor-widget-image,
        [data-dd-card-clickable="1"] .elementor-widget-image .elementor-widget-container {
            overflow: hidden;
        }

        [data-dd-card-clickable="1"] .elementor-widget-image img {
            transition: transform 0.3s ease;
        }

        [data-dd-card-clickable="1"]:hover .elementor-widget-image img {
            transform: scale(1.05);
        }

        .dd-product-price-link:hover,
        .dd-product-price-link:focus,
        .dd-product-sale-badge-link:hover,
        .dd-product-sale-badge-link:focus,
        .dd-product-card-overlay-link:hover,
        .dd-product-card-overlay-link:focus {
            color: inherit;
            text-decoration: none;
        }

        .dd-product-price-link del,
        .dd-product-price-link ins,
        .dd-product-price-link .woocommerce-Price-amount,
        .dd-product-price-link .woocommerce-Price-currencySymbol {
            color: inherit;
        }
    </style>
    <?php
});

add_action('wp_footer', function () {
    ?>
    <script>
        (function () {
            'use strict';

            var CARD_SELECTOR = '.jet-listing-grid__item';
            var SALE_TEXTS = ['מבצע!', 'Sale!', 'Sale'];

            function isAddToCartLink(link) {
                if (!link || !link.href) {
                    return true;
                }

                return (
                    link.classList.contains('add_to_cart_button') ||
                    link.classList.contains('dd-product-card-overlay-link') ||
                    link.classList.contains('dd-product-sale-badge-link') ||
                    link.classList.contains('dd-product-price-link') ||
                    link.closest('.jet-woo-builder-archive-add-to-cart') ||
                    link.href.indexOf('add-to-cart=') !== -1
                );
            }

            function getProductLink(card) {
                var links = Array.prototype.slice.call(card.querySelectorAll('a[href]'));

                return links.find(function (link) {
                    return !isAddToCartLink(link);
                }) || null;
            }

            function getProductLabel(card) {
                var headingLinks = Array.prototype.slice.call(card.querySelectorAll('.elementor-heading-title a[href]'));

                var titleLink = headingLinks.find(function (link) {
                    var text = link.textContent.trim();

                    return text && SALE_TEXTS.indexOf(text) === -1;
                });

                if (titleLink) {
                    return titleLink.textContent.trim();
                }

                var headings = Array.prototype.slice.call(card.querySelectorAll('.elementor-heading-title, h1, h2, h3'));

                var titleHeading = headings.find(function (heading) {
                    var text = heading.textContent.trim();

                    return text && SALE_TEXTS.indexOf(text) === -1;
                });

                if (titleHeading) {
                    return titleHeading.textContent.trim();
                }

                var img = card.querySelector('img[alt]');
                var alt = img ? img.getAttribute('alt') : '';

                return alt ? alt.trim() : 'View product';
            }

            function isSaleBadgeElement(element) {
                if (!element) {
                    return false;
                }

                var text = element.textContent.trim();

                return SALE_TEXTS.indexOf(text) !== -1;
            }

            function wrapElementContentWithLink(element, url, className, ariaLabel) {
                if (!element || !url) {
                    return;
                }

                if (element.querySelector('a.' + className)) {
                    return;
                }

                var link = document.createElement('a');

                link.className = className;
                link.href = url;
                link.setAttribute('aria-label', ariaLabel || 'View product');

                while (element.firstChild) {
                    link.appendChild(element.firstChild);
                }

                element.appendChild(link);
            }

            function unwrapSaleBadgeTextLinks(element, className) {
                var existingLinks = Array.prototype.slice.call(
                    element.querySelectorAll('a.' + className)
                );

                existingLinks.forEach(function (existingLink) {
                    if (existingLink.getAttribute('data-dd-sale-overlay') === '1') {
                        return;
                    }

                    var parent = existingLink.parentNode;

                    if (!parent) {
                        return;
                    }

                    while (existingLink.firstChild) {
                        parent.insertBefore(existingLink.firstChild, existingLink);
                    }

                    parent.removeChild(existingLink);
                });
            }

            function addOverlayLinkToElement(element, url, className, ariaLabel) {
                if (!element || !url) {
                    return;
                }

                unwrapSaleBadgeTextLinks(element, className);

                if (element.querySelector('a.' + className + '[data-dd-sale-overlay="1"]')) {
                    return;
                }

                if (window.getComputedStyle(element).position === 'static') {
                    element.style.position = 'relative';
                }

                var link = document.createElement('a');

                link.className = className;
                link.href = url;
                link.setAttribute('aria-label', ariaLabel || 'View product');
                link.setAttribute('data-dd-sale-overlay', '1');

                element.appendChild(link);
            }

            function getClickableCardArea(card) {
                return card.querySelector('.elementor-element[data-element_type="container"]') || card;
            }

            function hasDirectCardOverlay(element) {
                var children = Array.prototype.slice.call(element.children);

                return children.some(function (child) {
                    return (
                        child.classList &&
                        child.classList.contains('dd-product-card-overlay-link') &&
                        child.getAttribute('data-dd-card-overlay') === '1'
                    );
                });
            }

            function addCardOverlayLink(cardArea, url, ariaLabel) {
                if (!cardArea || !url) {
                    return;
                }

                cardArea.setAttribute('data-dd-card-clickable', '1');

                if (hasDirectCardOverlay(cardArea)) {
                    return;
                }

                if (window.getComputedStyle(cardArea).position === 'static') {
                    cardArea.style.position = 'relative';
                }

                var link = document.createElement('a');

                link.className = 'dd-product-card-overlay-link';
                link.href = url;
                link.setAttribute('aria-label', ariaLabel || 'View product');
                link.setAttribute('data-dd-card-overlay', '1');

                cardArea.appendChild(link);
            }

            function wrapSaleBadges(card, productUrl, productLabel) {
                var possibleBadges = Array.prototype.slice.call(
                    card.querySelectorAll('.elementor-heading-title, .onsale')
                );

                possibleBadges.forEach(function (badge) {
                    if (!isSaleBadgeElement(badge)) {
                        return;
                    }

                    var badgeWrapper = badge.closest('.elementor-element.elementor-widget-heading') ||
                        badge.closest('.elementor-widget-heading') ||
                        badge;

                    addOverlayLinkToElement(
                        badgeWrapper,
                        productUrl,
                        'dd-product-sale-badge-link',
                        productLabel
                    );
                });
            }

            function wrapPrices(card, productUrl, productLabel) {
                var priceElements = Array.prototype.slice.call(
                    card.querySelectorAll('.jet-woo-product-price')
                );

                priceElements.forEach(function (priceElement) {
                    if (!priceElement.textContent.trim()) {
                        return;
                    }

                    wrapElementContentWithLink(
                        priceElement,
                        productUrl,
                        'dd-product-price-link',
                        productLabel
                    );
                });
            }

            function initClickableProductCardElements() {
                var cards = document.querySelectorAll(CARD_SELECTOR);

                cards.forEach(function (card) {
                    var productLink = getProductLink(card);

                    if (!productLink) {
                        return;
                    }

                    var productUrl = productLink.href;
                    var productLabel = getProductLabel(card);
                    var cardArea = getClickableCardArea(card);

                    addCardOverlayLink(cardArea, productUrl, productLabel);
                    wrapSaleBadges(card, productUrl, productLabel);
                    wrapPrices(card, productUrl, productLabel);
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                initClickableProductCardElements();
            });

            window.addEventListener('load', function () {
                initClickableProductCardElements();
            });

            document.addEventListener('jet-engine/listing-grid/after-load-more', function () {
                initClickableProductCardElements();
            });

            document.addEventListener('jet-engine/listing-grid/after-lazy-load', function () {
                initClickableProductCardElements();
            });

            if (window.jQuery) {
                window.jQuery(document).ajaxComplete(function () {
                    initClickableProductCardElements();
                });
            }
        })();
    </script>
    <?php
}, 100);