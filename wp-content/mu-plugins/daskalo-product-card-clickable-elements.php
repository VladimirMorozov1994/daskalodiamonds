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
        .dd-product-sale-badge-link {
            color: inherit;
            text-decoration: none;
        }

        .dd-product-price-link {
            display: inline-block;
            cursor: pointer;
        }

        .dd-product-sale-badge-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .dd-product-price-link:hover,
        .dd-product-price-link:focus,
        .dd-product-sale-badge-link:hover,
        .dd-product-sale-badge-link:focus {
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

            function wrapSaleBadges(card, productUrl, productLabel) {
                var possibleBadges = Array.prototype.slice.call(
                    card.querySelectorAll('.elementor-heading-title, .onsale')
                );

                possibleBadges.forEach(function (badge) {
                    if (!isSaleBadgeElement(badge)) {
                        return;
                    }

                    wrapElementContentWithLink(
                        badge,
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