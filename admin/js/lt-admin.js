/**
 * Lion Trust Locations - Admin JavaScript
 */

(function($) {
    'use strict';

    /**
     * Update nearby locations count display
     */
    function updateNearbyCount() {
        var count = $('input[name="lt_nearby_locations[]"]:checked').length;
        $('#lt-nearby-count').text(count);
    }

    /**
     * Initialize admin functionality
     */
    function init() {
        // Update nearby count on checkbox change
        $(document).on('change', 'input[name="lt_nearby_locations[]"]', function() {
            updateNearbyCount();
        });

        // Select all/none for nearby locations
        if ($('.lt-nearby-locations-list').length) {
            var $list = $('.lt-nearby-locations-list');

            // Add select all/none buttons
            var $buttons = $('<p style="margin-bottom: 10px;">' +
                '<button type="button" class="button button-small lt-select-all">' +
                'Select All</button> ' +
                '<button type="button" class="button button-small lt-select-none">' +
                'Select None</button>' +
                '</p>');

            $list.before($buttons);

            // Select all handler
            $('.lt-select-all').on('click', function(e) {
                e.preventDefault();
                $list.find('input[type="checkbox"]').prop('checked', true);
                updateNearbyCount();
            });

            // Select none handler
            $('.lt-select-none').on('click', function(e) {
                e.preventDefault();
                $list.find('input[type="checkbox"]').prop('checked', false);
                updateNearbyCount();
            });
        }

        // Show/hide child-specific fields based on parent selection
        var $parentDropdown = $('#parent_id');

        if ($parentDropdown.length) {
            $parentDropdown.on('change', function() {
                var isChild = $(this).val() !== '' && $(this).val() !== '0';

                // Toggle visibility of parent-only metaboxes
                if (isChild) {
                    $('#lt_nearby_locations').slideUp();
                    $('#lt_coordinates').slideUp();
                } else {
                    $('#lt_nearby_locations').slideDown();
                    $('#lt_coordinates').slideDown();
                }
            }).trigger('change');
        }

        // Coordinate validation
        $('#lt_latitude, #lt_longitude').on('change', function() {
            var val = parseFloat($(this).val());
            var id = $(this).attr('id');

            if (id === 'lt_latitude' && (val < -90 || val > 90)) {
                alert('Latitude must be between -90 and 90');
                $(this).val('');
            }

            if (id === 'lt_longitude' && (val < -180 || val > 180)) {
                alert('Longitude must be between -180 and 180');
                $(this).val('');
            }
        });

        // Filter locations list by region in admin
        var $regionFilter = $('#lt_region_filter');

        if ($regionFilter.length) {
            $regionFilter.on('change', function() {
                var region = $(this).val();
                var url = new URL(window.location.href);

                if (region) {
                    url.searchParams.set('lt_region', region);
                } else {
                    url.searchParams.delete('lt_region');
                }

                window.location.href = url.toString();
            });
        }
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
