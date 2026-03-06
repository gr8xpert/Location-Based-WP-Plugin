/**
 * Lion Trust Locations - AJAX Search
 */

(function($) {
    'use strict';

    var LTSearch = {
        /**
         * Configuration
         */
        config: {
            debounceDelay: 300,
            perPage: 12
        },

        /**
         * Cache DOM elements
         */
        cache: {},

        /**
         * Current state
         */
        state: {
            currentPage: 1,
            totalPages: 1,
            isLoading: false,
            searchTerm: '',
            region: ''
        },

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.cacheElements();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.cache.$searchContainer = $('.lt-search-container');
            this.cache.$searchInput = $('.lt-search-input');
            this.cache.$regionSelect = $('.lt-search-region');
            this.cache.$resultsGrid = $('.lt-search-results');
            this.cache.$pagination = $('.lt-search-pagination');
            this.cache.$loadMore = $('.lt-load-more');
            this.cache.$loading = $('.lt-search-loading');
            this.cache.$noResults = $('.lt-search-no-results');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Search input with debounce
            $(document).on('input', '.lt-search-input', this.debounce(function() {
                self.state.searchTerm = $(this).val();
                self.state.currentPage = 1;
                self.search();
            }, this.config.debounceDelay));

            // Region filter change
            $(document).on('change', '.lt-search-region', function() {
                self.state.region = $(this).val();
                self.state.currentPage = 1;
                self.search();
            });

            // Form submit
            $(document).on('submit', '.lt-search-form', function(e) {
                e.preventDefault();
                self.search();
            });

            // Pagination click
            $(document).on('click', '.lt-search-pagination a', function(e) {
                e.preventDefault();
                var page = $(this).data('page');
                if (page) {
                    self.state.currentPage = parseInt(page, 10);
                    self.search();
                    self.scrollToResults();
                }
            });

            // Load more button
            $(document).on('click', '.lt-load-more', function(e) {
                e.preventDefault();
                if (!self.state.isLoading && self.state.currentPage < self.state.totalPages) {
                    self.state.currentPage++;
                    self.search(true);
                }
            });
        },

        /**
         * Perform search
         */
        search: function(append) {
            var self = this;

            if (this.state.isLoading) {
                return;
            }

            this.state.isLoading = true;
            this.showLoading(!append);

            var params = {
                page: this.state.currentPage,
                per_page: this.config.perPage,
                parents_only: true
            };

            if (this.state.searchTerm) {
                params.s = this.state.searchTerm;
            }

            if (this.state.region) {
                params.region = this.state.region;
            }

            $.ajax({
                url: ltLocations.apiUrl + 'locations/search',
                method: 'GET',
                data: params,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', ltLocations.nonce);
                },
                success: function(response) {
                    self.state.totalPages = response.total_pages;
                    self.renderResults(response.locations, append);
                    self.renderPagination(response);
                    self.hideLoading();
                },
                error: function() {
                    self.showError();
                    self.hideLoading();
                },
                complete: function() {
                    self.state.isLoading = false;
                }
            });
        },

        /**
         * Render search results
         */
        renderResults: function(locations, append) {
            var self = this;
            var $container = $('.lt-search-results');

            if (!$container.length) {
                return;
            }

            if (!locations.length && !append) {
                $container.html(this.getNoResultsHTML());
                return;
            }

            var html = '';

            locations.forEach(function(location) {
                html += self.renderCard(location);
            });

            if (append) {
                $container.append(html);
            } else {
                $container.html(html);
            }

            // Update load more button visibility
            if (this.state.currentPage >= this.state.totalPages) {
                $('.lt-load-more').hide();
            } else {
                $('.lt-load-more').show();
            }
        },

        /**
         * Render a single location card
         */
        renderCard: function(location) {
            var imageHTML = '';

            if (location.image && location.image.thumb) {
                imageHTML = '<div class="lt-card-image">' +
                    '<a href="' + this.escapeHTML(location.url) + '">' +
                    '<img src="' + this.escapeHTML(location.image.thumb) + '" alt="' + this.escapeHTML(location.title) + '">' +
                    '</a></div>';
            } else if (ltLocations.placeholderImage) {
                imageHTML = '<div class="lt-card-image">' +
                    '<a href="' + this.escapeHTML(location.url) + '">' +
                    '<img src="' + this.escapeHTML(ltLocations.placeholderImage) + '" alt="' + this.escapeHTML(location.title) + '">' +
                    '</a></div>';
            }

            var excerpt = location.short_description || location.excerpt || '';
            var buttonsHTML = '';

            if (location.children && location.children.length) {
                buttonsHTML = '<div class="lt-card-buttons">';
                location.children.forEach(function(child) {
                    var typeName = child.property_type || child.title.replace(location.title, '').trim();
                    buttonsHTML += '<a href="' + this.escapeHTML(child.url) + '" class="lt-btn">' +
                        this.escapeHTML(typeName.charAt(0).toUpperCase() + typeName.slice(1)) + '</a>';
                }.bind(this));
                buttonsHTML += '</div>';
            }

            return '<article class="lt-location-card">' +
                imageHTML +
                '<div class="lt-card-content">' +
                '<h3 class="lt-card-title">' +
                '<a href="' + this.escapeHTML(location.url) + '">' + this.escapeHTML(location.title) + '</a>' +
                '</h3>' +
                (excerpt ? '<p class="lt-card-excerpt">' + this.escapeHTML(excerpt) + '</p>' : '') +
                '<a href="' + this.escapeHTML(location.url) + '" class="lt-read-more">' + ltLocations.i18n.readMore + '</a>' +
                '</div>' +
                buttonsHTML +
                '</article>';
        },

        /**
         * Render pagination
         */
        renderPagination: function(response) {
            var $container = $('.lt-search-pagination');

            if (!$container.length || response.total_pages <= 1) {
                $container.empty();
                return;
            }

            var html = '';
            var current = this.state.currentPage;
            var total = response.total_pages;

            // Previous
            if (current > 1) {
                html += '<a href="#" data-page="' + (current - 1) + '">&laquo;</a>';
            }

            // Page numbers
            var start = Math.max(1, current - 2);
            var end = Math.min(total, current + 2);

            if (start > 1) {
                html += '<a href="#" data-page="1">1</a>';
                if (start > 2) {
                    html += '<span class="dots">...</span>';
                }
            }

            for (var i = start; i <= end; i++) {
                if (i === current) {
                    html += '<span class="current">' + i + '</span>';
                } else {
                    html += '<a href="#" data-page="' + i + '">' + i + '</a>';
                }
            }

            if (end < total) {
                if (end < total - 1) {
                    html += '<span class="dots">...</span>';
                }
                html += '<a href="#" data-page="' + total + '">' + total + '</a>';
            }

            // Next
            if (current < total) {
                html += '<a href="#" data-page="' + (current + 1) + '">&raquo;</a>';
            }

            $container.html(html);
        },

        /**
         * Show loading state
         */
        showLoading: function(clearResults) {
            if (clearResults) {
                var skeletons = '';
                for (var i = 0; i < 6; i++) {
                    skeletons += '<div class="lt-skeleton lt-skeleton-card"></div>';
                }
                $('.lt-search-results').html(skeletons);
            }
            $('.lt-search-loading').addClass('active');
        },

        /**
         * Hide loading state
         */
        hideLoading: function() {
            $('.lt-search-loading').removeClass('active');
        },

        /**
         * Show error message
         */
        showError: function() {
            $('.lt-search-results').html(
                '<div class="lt-error"><p>' + ltLocations.i18n.error + '</p></div>'
            );
        },

        /**
         * Get no results HTML
         */
        getNoResultsHTML: function() {
            return '<div class="lt-no-results"><p>' + ltLocations.i18n.noResults + '</p></div>';
        },

        /**
         * Scroll to results
         */
        scrollToResults: function() {
            var $container = $('.lt-search-container');
            if ($container.length) {
                $('html, body').animate({
                    scrollTop: $container.offset().top - 100
                }, 300);
            }
        },

        /**
         * Debounce helper
         */
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this;
                var args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        },

        /**
         * Escape HTML helper
         */
        escapeHTML: function(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        LTSearch.init();
    });

    // Expose to global scope for external access
    window.LTSearch = LTSearch;

})(jQuery);
