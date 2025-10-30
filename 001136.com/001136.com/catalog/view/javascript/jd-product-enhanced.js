/**
 * JD Product Enhanced - Plan B
 * Enhanced product page interactions with multi-dimensional options
 */
(function($) {
    'use strict';

    // Configuration
    const CONFIG = {
        animationDuration: 200,
        debounceDelay: 300,
        autoSaveSelection: true,
        showTooltips: true,
        enableCombinations: true,
        apiEndpoint: 'index.php?route=product/product.getOptionCombination&language='
    };

    // Global state management
    const STATE = {
        currentProduct: null,
        selectedOptions: {},
        availableCombinations: [],
        priceData: {},
        isLoading: false
    };

    /**
     * Initialize product page
     */
    function init() {
        initGallery();
        initQuantityControls();
        initEnhancedOptions();
        initDeliveryModal();
        initNotifyButton();
        initCombinationChecker();
        initTooltips();
        loadSavedSelections();
        
        console.log('JD Product Enhanced initialized');
    }

    /**
     * Gallery management
     */
    function initGallery() {
        const $stage = $('#jd-stage-content');
        const $stageImage = $('#jd-stage-image');
        const $thumbs = $('#jd-thumbs');

        $thumbs.on('click', '.jd-gallery__thumb', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const type = $btn.data('type');
            const src = $btn.data('src');
            const popup = $btn.data('popup') || '';
            const poster = $btn.data('poster') || '';

            $thumbs.find('.jd-gallery__thumb').removeClass('is-active');
            $btn.addClass('is-active');

            $stage.data('type', type);
            $stage.data('src', src);
            $stage.data('popup', popup);
            $stage.data('poster', poster);

            if (type === 'image') {
                const $link = $stage.find('.jd-gallery__stage-link');
                const $img = $stageImage;
                
                if ($link.length && $img.length) {
                    $link.attr('href', popup || src);
                    $img.attr('src', src).addClass('fade-in');
                    setTimeout(() => $img.removeClass('fade-in'), 300);
                }
            } else if (type === 'video') {
                let videoHtml = '<video class="jd-stage-video" controls preload="metadata"';
                if (poster) videoHtml += ' poster="' + poster + '"';
                videoHtml += '><source src="' + src + '" type="video/mp4"/></video>';
                $stage.html(videoHtml);
            } else if (type === 'model') {
                const modelHtml = '<model-viewer src="' + src + '" camera-controls auto-rotate ar shadow-intensity="0.2"></model-viewer>';
                $stage.html(modelHtml);
            }
        });

        if ($.fn.magnificPopup) {
            $('.jd-gallery__stage').magnificPopup({
                delegate: '.jd-gallery__stage-link',
                type: 'image',
                gallery: { enabled: true }
            });

            $('#jd-picture-grid').magnificPopup({
                delegate: '.jd-picture-item',
                type: 'image',
                gallery: { enabled: true }
            });
        }
    }

    /**
     * Quantity controls
     */
    function initQuantityControls() {
        const $quantityBox = $('.jd-quantity');
        const $input = $quantityBox.find('.jd-quantity__input');
        const minimum = parseInt($quantityBox.data('min')) || 1;

        $quantityBox.on('click', '.jd-quantity__btn', function(e) {
            e.preventDefault();
            const action = $(this).data('action');
            let currentQty = parseInt($input.val()) || minimum;

            if (action === 'increase') {
                currentQty++;
            } else if (action === 'decrease' && currentQty > minimum) {
                currentQty--;
            }

            $input.val(currentQty).trigger('change');
        });

        $input.on('blur', function() {
            let qty = parseInt($(this).val()) || minimum;
            if (qty < minimum) qty = minimum;
            $(this).val(qty);
        });

        $input.on('keypress', function(e) {
            const charCode = e.which || e.keyCode;
            if (charCode === 8 || charCode === 9 || charCode === 27 || charCode === 13) {
                return true;
            }
            if (charCode < 48 || charCode > 57) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Enhanced option selection system
     */
    function initEnhancedOptions() {
        const $form = $('#form-product');

        // Color swatch options
        $form.on('click', '.jd-option-item--swatch, .jd-option-item--color', function(e) {
            e.preventDefault();
            if ($(this).hasClass('is-disabled') || $(this).hasClass('is-out-of-stock')) {
                return;
            }
            handleOptionSelection($(this), 'color');
        });

        // Button style options
        $form.on('click', '.jd-option-item--button:not(.jd-option-item--color)', function(e) {
            e.preventDefault();
            if ($(this).hasClass('is-disabled') || $(this).hasClass('is-out-of-stock')) {
                return;
            }
            handleOptionSelection($(this), 'size');
        });

        // Image options
        $form.on('click', '.jd-option-item--image', function(e) {
            e.preventDefault();
            if ($(this).hasClass('is-disabled') || $(this).hasClass('is-out-of-stock')) {
                return;
            }
            handleOptionSelection($(this), 'image');
        });

        // Default options
        $form.on('click', '.jd-option-item:not(.jd-option-item--swatch):not(.jd-option-item--button):not(.jd-option-item--image):not(.jd-option-item--color)', function(e) {
            e.preventDefault();
            if ($(this).hasClass('is-disabled') || $(this).hasClass('is-out-of-stock')) {
                return;
            }
            handleOptionSelection($(this), 'default');
        });

        $form.on('dblclick', '.jd-option-item--image, .jd-option-item--swatch', function(e) {
            const $target = $(this);
            const popupSrc = $target.data('popup') || $target.data('stage') || $target.data('image');

            if (!popupSrc) {
                return;
            }

            e.preventDefault();

            if (typeof $.magnificPopup === 'object' && typeof $.magnificPopup.open === 'function') {
                $.magnificPopup.open({
                    items: {
                        src: popupSrc
                    },
                    type: 'image'
                });
            } else {
                window.open(popupSrc, '_blank', 'noopener');
            }
        });
    }

    /**
     * Handle option selection
     */
    function handleOptionSelection($item, optionType) {
    const $group = $item.closest('.jd-configurator__group');
    const $radio = $item.find('input[type="radio"]');
    const stageImage = $item.data('stage') || $item.data('image');
    const popupImage = $item.data('popup') || $item.data('stage') || $item.data('image');
    const optionGroup = $group.data('group');

        // Update active state
        $group.find('.jd-option-item').removeClass('is-active just-selected');
        $item.addClass('is-active just-selected');
        setTimeout(() => $item.removeClass('just-selected'), 400);

        // Check radio
        $radio.prop('checked', true);

        // Save selection
        const productOptionId = $radio.attr('name').match(/\d+/)[0];
        const optionValueId = $radio.val();
        STATE.selectedOptions[optionGroup || optionType] = {
            productOptionId: productOptionId,
            optionValueId: optionValueId
        };

        // Update main image if available
        if (stageImage) {
            updateMainImage(stageImage, popupImage);
        }

        // Update price
        const finalPrice = $item.data('final');
        const originalPrice = $item.data('original');
        const discountRaw = $item.data('discount');
        const discountLabel = $item.data('discount-label');

        if (typeof finalPrice !== 'undefined') {
            updatePrice(finalPrice, originalPrice, discountRaw, discountLabel);
        }

        // Check combination
        if (CONFIG.enableCombinations) {
            checkOptionCombination();
        }

        // Auto save
        if (CONFIG.autoSaveSelection) {
            saveSelectionToStorage();
        }

        // Trigger custom event
        $(document).trigger('optionSelected', [optionGroup, optionValueId]);
    }

    /**
     * Update main product image
     */
    function updateMainImage(imageUrl, popupUrl) {
        const $stageImage = $('#jd-stage-image');
        const $stageLink = $('.jd-gallery__stage-link');
        const targetUrl = popupUrl || imageUrl;
        
        if ($stageImage.length) {
            $stageImage.addClass('loading');
            
            const img = new Image();
            img.onload = function() {
                $stageImage.attr('src', imageUrl).removeClass('loading');
                if ($stageLink.length && targetUrl) {
                    $stageLink.attr('href', targetUrl);
                }
            };
            img.src = imageUrl;
        }
    }

    /**
     * Update price display
     */
    function updatePrice(finalRaw, originalRaw, discountRaw, discountLabel) {
        const $priceFinal = $('#jd-price-final');
        const $priceOriginal = $('#jd-price-original');
        const $priceDiscount = $('#jd-price-discount');

        const currentPrice = $priceFinal.text();
        const currencyMatch = currentPrice.match(/[^\d.,]+/);
        const currencySymbol = currencyMatch ? currencyMatch[0] : '$';

        const finalFormatted = currencySymbol + parseFloat(finalRaw).toFixed(2);
        
        $priceFinal.fadeOut(100, function() {
            $(this).text(finalFormatted).fadeIn(100);
        });

        if (originalRaw && parseFloat(originalRaw) > parseFloat(finalRaw)) {
            const originalFormatted = currencySymbol + parseFloat(originalRaw).toFixed(2);
            if ($priceOriginal.length) {
                $priceOriginal.text(originalFormatted).show();
            } else {
                $priceFinal.after('<span class="jd-price-box__original" id="jd-price-original">' + originalFormatted + '</span>');
            }
        } else {
            $priceOriginal.hide();
        }

        if (discountLabel) {
            $priceDiscount.html(discountLabel).removeClass('d-none').hide().fadeIn(200);
        } else {
            $priceDiscount.addClass('d-none');
        }

        STATE.priceData = {
            final: finalRaw,
            original: originalRaw,
            discount: discountRaw
        };
    }

    /**
     * Check option combination availability
     */
    function checkOptionCombination() {
        if (STATE.isLoading) return;

        const selectedKeys = Object.keys(STATE.selectedOptions).map(key => {
            return key + '_' + STATE.selectedOptions[key].optionValueId;
        }).sort().join('_');

        console.log('Checking combination:', selectedKeys);

        // Update option availability
        $('.jd-option-item').each(function() {
            const $item = $(this);
            const stockStatus = $item.data('stock-status');
            
            if (stockStatus === 0 || stockStatus === false) {
                $item.addClass('is-out-of-stock');
                if (!$item.find('.jd-option-item__stock-text').length) {
                    $item.find('.jd-option-item__name').after('<div class="jd-option-item__stock-text">Out of Stock</div>');
                }
            }
        });
    }

    /**
     * Initialize combination checker
     */
    function initCombinationChecker() {
        if (!CONFIG.enableCombinations) return;

        $(document).on('optionSelected', debounce(function() {
            checkOptionCombination();
        }, CONFIG.debounceDelay));
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        if (!CONFIG.showTooltips) return;

        $('.jd-option-item').each(function() {
            const $item = $(this);
            const title = $item.attr('title') || $item.data('tooltip');
            
            if (title) {
                $item.append('<div class="jd-option-item__tooltip">' + title + '</div>');
            }
        });
    }

    /**
     * Save selection to localStorage
     */
    function saveSelectionToStorage() {
        const productId = $('input[name="product_id"]').val();
        if (!productId) return;

        const storageKey = 'product_options_' + productId;
        try {
            localStorage.setItem(storageKey, JSON.stringify(STATE.selectedOptions));
        } catch (e) {
            console.warn('Could not save to localStorage:', e);
        }
    }

    /**
     * Load saved selections from localStorage
     */
    function loadSavedSelections() {
        const productId = $('input[name="product_id"]').val();
        if (!productId) return;

        const storageKey = 'product_options_' + productId;
        try {
            const saved = localStorage.getItem(storageKey);
            if (saved) {
                const options = JSON.parse(saved);
                Object.keys(options).forEach(group => {
                    const opt = options[group];
                    const $radio = $('input[type="radio"][value="' + opt.optionValueId + '"]');
                    if ($radio.length) {
                        $radio.closest('.jd-option-item').trigger('click');
                    }
                });
            }
        } catch (e) {
            console.warn('Could not load from localStorage:', e);
        }
    }

    /**
     * Delivery address modal
     */
    function initDeliveryModal() {
        const $trigger = $('#jd-delivery-trigger');
        const $modal = $('#jd-address-modal');
        const $confirmBtn = $('#jd-address-modal-confirm');

        $trigger.on('click', function(e) {
            e.preventDefault();
            const addressData = $(this).data('address');

            if (addressData && !addressData.is_logged) {
                if (addressData.login_url) {
                    window.location.href = addressData.login_url;
                }
            } else if (addressData && addressData.is_logged && !addressData.has_address) {
                if (addressData.manage_url) {
                    $confirmBtn.attr('href', addressData.manage_url);
                    $modal.modal('show');
                }
            } else if (addressData && addressData.is_logged && addressData.has_address) {
                if (addressData.manage_url) {
                    window.location.href = addressData.manage_url;
                }
            }
        });
    }

    /**
     * Notify button for out of stock
     */
    function initNotifyButton() {
        const $notifyBtn = $('#jd-notify-btn');
        const $cartBtn = $('#button-cart');

        function checkStockStatus() {
            const stockText = $('.jd-summary__meta').text().toLowerCase();
            
            if (stockText.indexOf('out of stock') !== -1 || 
                stockText.indexOf('unavailable') !== -1) {
                $cartBtn.hide();
                $notifyBtn.show();
            } else {
                $cartBtn.show();
                $notifyBtn.hide();
            }
        }

        checkStockStatus();

        $notifyBtn.on('click', function(e) {
            e.preventDefault();
            const message = $(this).data('message');
            if (message) alert(message);
        });
    }

    /**
     * Utility: Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Public API
     */
    window.JDProductEnhanced = {
        getSelectedOptions: () => STATE.selectedOptions,
        getCurrentPrice: () => STATE.priceData,
        updatePrice: updatePrice,
        checkCombination: checkOptionCombination,
        STATE: STATE,
        CONFIG: CONFIG
    };

    // Initialize on document ready
    $(document).ready(function() {
        init();
    });

})(jQuery);

