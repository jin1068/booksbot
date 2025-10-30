/**
 * JD-style Product Page Interactive Features
 * Handles color/storage selection, quantity controls, gallery, and price updates
 */
(function($) {
    'use strict';

    // Gallery management
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

            // Update active state
            $thumbs.find('.jd-gallery__thumb').removeClass('is-active');
            $btn.addClass('is-active');

            // Update stage content
            $stage.data('type', type);
            $stage.data('src', src);
            $stage.data('popup', popup);
            $stage.data('poster', poster);

            if (type === 'image') {
                const $link = $stage.find('.jd-gallery__stage-link');
                const $img = $stageImage;
                
                if ($link.length && $img.length) {
                    $link.attr('href', popup || src);
                    $img.attr('src', src);
                }
            } else if (type === 'video') {
                let videoHtml = '<video class="jd-stage-video" controls preload="metadata"';
                if (poster) {
                    videoHtml += ' poster="' + poster + '"';
                }
                videoHtml += '><source src="' + src + '" type="video/mp4"/></video>';
                $stage.html(videoHtml);
            } else if (type === 'model') {
                const modelHtml = '<model-viewer src="' + src + '" camera-controls auto-rotate ar shadow-intensity="0.2"></model-viewer>';
                $stage.html(modelHtml);
            }
        });

        // Initialize Magnific Popup for images
        if ($.fn.magnificPopup) {
            $('.jd-gallery__stage').magnificPopup({
                delegate: '.jd-gallery__stage-link',
                type: 'image',
                gallery: {
                    enabled: true
                }
            });
        }
    }

    // Quantity controls
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

            $input.val(currentQty);
        });

        // Validate input
        $input.on('blur', function() {
            let qty = parseInt($(this).val()) || minimum;
            if (qty < minimum) {
                qty = minimum;
            }
            $(this).val(qty);
        });

        // Allow only numeric input
        $input.on('keypress', function(e) {
            const charCode = e.which || e.keyCode;
            // Allow: backspace, delete, tab, escape, enter
            if (charCode === 8 || charCode === 9 || charCode === 27 || charCode === 13) {
                return true;
            }
            // Allow numbers only
            if (charCode < 48 || charCode > 57) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Option selection (color and storage)
    function initOptionSelection() {
        const $form = $('#form-product');
        const $priceFinal = $('#jd-price-final');
        const $priceOriginal = $('#jd-price-original');
        const $priceDiscount = $('#jd-price-discount');

        // Color option selection
        $form.on('click', '.jd-option-item--color', function(e) {
            const $item = $(this);
            const $group = $item.closest('.jd-configurator__group');
            const imageUrl = $item.data('image');

            // Update active state
            $group.find('.jd-option-item').removeClass('is-active');
            $item.addClass('is-active');

            // Update main image if available
            if (imageUrl) {
                const $stageImage = $('#jd-stage-image');
                const $stageLink = $('.jd-gallery__stage-link');
                
                if ($stageImage.length) {
                    $stageImage.attr('src', imageUrl);
                }
                if ($stageLink.length) {
                    $stageLink.attr('href', imageUrl);
                }
            }

            // Ensure radio is checked
            $item.find('input[type="radio"]').prop('checked', true);
        });

        // Storage option selection with price update
        $form.on('click', '.jd-option-item:not(.jd-option-item--color)', function(e) {
            const $item = $(this);
            const $group = $item.closest('.jd-configurator__group');

            // Update active state
            $group.find('.jd-option-item').removeClass('is-active');
            $item.addClass('is-active');

            // Ensure radio is checked
            $item.find('input[type="radio"]').prop('checked', true);

            // Update price if data attributes exist
            const finalPrice = $item.data('final');
            const originalPrice = $item.data('original');
            const discountRaw = $item.data('discount');
            const discountLabel = $item.data('discount-label');

            if (typeof finalPrice !== 'undefined') {
                updatePrice(finalPrice, originalPrice, discountRaw, discountLabel);
            }
        });

        function updatePrice(finalRaw, originalRaw, discountRaw, discountLabel) {
            // Get currency format from current display
            const currentPrice = $priceFinal.text();
            const currencyMatch = currentPrice.match(/[^\d.,]+/);
            const currencySymbol = currencyMatch ? currencyMatch[0] : '$';

            // Format prices
            const finalFormatted = currencySymbol + parseFloat(finalRaw).toFixed(2);
            $priceFinal.text(finalFormatted);

            // Update original price
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

            // Update discount label
            if (discountLabel) {
                $priceDiscount.html(discountLabel).removeClass('d-none');
            } else {
                $priceDiscount.addClass('d-none');
            }
        }
    }

    // Delivery address modal
    function initDeliveryModal() {
        const $trigger = $('#jd-delivery-trigger');
        const $modal = $('#jd-address-modal');
        const $confirmBtn = $('#jd-address-modal-confirm');

        $trigger.on('click', function(e) {
            e.preventDefault();
            const addressData = $(this).data('address');

            if (addressData && !addressData.is_logged) {
                // Redirect to login
                if (addressData.login_url) {
                    window.location.href = addressData.login_url;
                }
            } else if (addressData && addressData.is_logged && !addressData.has_address) {
                // Show modal to add address
                if (addressData.manage_url) {
                    $confirmBtn.attr('href', addressData.manage_url);
                    $modal.modal('show');
                }
            } else if (addressData && addressData.is_logged && addressData.has_address) {
                // Navigate to address management
                if (addressData.manage_url) {
                    window.location.href = addressData.manage_url;
                }
            }
        });
    }

    // Notify button
    function initNotifyButton() {
        const $notifyBtn = $('#jd-notify-btn');
        const $cartBtn = $('#button-cart');

        // Toggle visibility based on stock status
        function checkStockStatus() {
            const stockText = $('.jd-summary__meta').text().toLowerCase();
            
            if (stockText.indexOf('out of stock') !== -1 || stockText.indexOf('缺货') !== -1 || stockText.indexOf('無貨') !== -1) {
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
            
            if (message) {
                alert(message);
            }
        });
    }

    // Initialize all features on document ready
    $(document).ready(function() {
        initGallery();
        initQuantityControls();
        initOptionSelection();
        initDeliveryModal();
        initNotifyButton();

        // Handle picture grid clicks if exists
        $('#jd-picture-grid').on('click', '.jd-picture-item', function(e) {
            // Default link behavior is fine, but we could add custom handling here
        });
    });

})(jQuery);

