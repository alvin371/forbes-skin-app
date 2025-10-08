<?php

/**
 * AJAX Request Manager - Prevents pending AJAX calls from blocking navigation
 * This utility manages all AJAX requests and provides cleanup on page navigation
 */

function render_ajax_manager()
{
    return <<<'HTML'
    <script>
    /**
    * Global AJAX Request Manager
    * Tracks all active AJAX requests and provides methods to abort them
    */
    // Define global debug logging function with fallback
    window.ajaxDebugLog = function(message, ...args) {
    if (typeof console !== "undefined" && console.log) {
    console.log.apply(console, ["[AJAX Manager]", message, ...args]);
    }
    };


    window.AjaxRequestManager = (function() {
    // Store all active XMLHttpRequest objects
    let activeRequests = [];
    let isNavigating = false;

    // Original jQuery ajax method
    const originalAjax = $.ajax;

    // Override jQuery.ajax to track requests
    $.ajax = function(options) {

    if (isNavigating) {
    return $.Deferred().reject('Navigation in progress');
    }

    // Create the request using original method
    const xhr = originalAjax.call(this, options);

    // Store URL for debugging purposes
    xhr.url = options.url || "unknown URL";

    // Add to active requests array
    activeRequests.push(xhr);

    // Remove from array when request completes (success, error, or abort)
    xhr.always(function() {
    const index = activeRequests.indexOf(xhr);
    if (index > -1) {
    activeRequests.splice(index, 1);

    }
    });

    return xhr;
    };

    return {
    /**
    * Get count of active requests
    */
    getActiveCount: function() {
    return activeRequests.length;
    },

    /**
    * Abort all pending requests
    */
    abortAll: function() {
    isNavigating = true;

    // Abort all active requests with enhanced cancellation
    activeRequests.forEach(function(xhr, index) {
        if (xhr && xhr.readyState !== 4) {
            try {
                xhr.onreadystatechange = null; // Clear callbacks
                xhr.onload = null;
                xhr.onerror = null;
                xhr.ontimeout = null;
                
                // Force abort
                xhr.abort();
                
                // Force timeout to 0 to immediately fail
                if (xhr.timeout !== undefined) {
                    xhr.timeout = 0;
                }
                
            } catch (e) {
                ajaxDebugLog('⚠️ Error aborting request #' + (index + 1) + ':', e);
            }
        }
    });

    // Clear the array immediately
    activeRequests = [];
    },

    /**
    * Check if any requests are still pending
    */
    hasPendingRequests: function() {
    return activeRequests.some(xhr => xhr && xhr.readyState !== 4);
    },

    /**
    * Reset navigation state (useful for SPA navigation)
    */
    resetNavigationState: function() {
    isNavigating = false;
    }
    };
    })();

    /**
    * Handle page navigation - abort pending requests
    */
    $(window).on('beforeunload', function(e) {
        const pendingCount = AjaxRequestManager.getActiveCount();

        if (pendingCount > 0) {
            AjaxRequestManager.abortAll();
        }
    });

    /**
    * Handle link clicks - abort pending requests for immediate navigation
    */
    $(document).on('click', 'a[href]:not([href="#"]):not([href^="javascript:"]):not([target="_blank"])', function(e) {
        const href = $(this).attr('href');

        // Skip if it's a hash link or JavaScript
        if (href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return;
        }

        // Abort pending requests for faster navigation
        if (AjaxRequestManager.hasPendingRequests()) {
            AjaxRequestManager.abortAll();
        }
    });

    /**
    * Handle form submissions - abort pending requests
    */
    $(document).on('submit', 'form', function(e) {
    if (AjaxRequestManager.hasPendingRequests()) {
    console.log('Form submitted - aborting pending requests');
    AjaxRequestManager.abortAll();
    }
    });

    /**
    * Utility function for immediate redirect (used in dashboard)
    */
    window.immediateRedirect = function(url) {
        try {
            if (document.readyState === "loading") {
                window.stop(); // Stop document loading
            }
        } catch (e) {
            ajaxDebugLog('⚠️ Could not stop document loading:', e);
        }
        
        // Abort all AJAX requests with enhanced logging
        const pendingCount = AjaxRequestManager.getActiveCount();
        if (pendingCount > 0) {
            AjaxRequestManager.abortAll();
        }
        
        setTimeout(function() {
            window.location.href = url;
        }, 10); 
    };

    /**
    * Enhanced AJAX function with timeout and better error handling
    */
    window.safeAjax = function(options) {
    const defaults = {
    timeout: 10000, // 10 second timeout
    cache: false,
    error: function(xhr, status, error) {
    if (status !== 'abort') {
    console.warn('AJAX Error:', status, error);
    }
    }
    };

    return $.ajax($.extend({}, defaults, options));
    };

    /**
    * Dashboard-specific AJAX helper with intelligent caching
    */
    window.dashboardAjax = function(options) {
    const cacheKey = options.url + (options.data ? JSON.stringify(options.data) : '');
    const cacheTime = options.cacheTime || 30000; // 30 seconds default

    // Simple cache implementation
    if (!window.dashboardCache) {
    window.dashboardCache = {};
    }

    const cached = window.dashboardCache[cacheKey];
    if (cached && (Date.now() - cached.timestamp) < cacheTime) {
        // Return cached data as a resolved promise
        const deferred=$.Deferred();
        setTimeout(()=> deferred.resolve(cached.data), 0);
        return deferred.promise();
        }

        const defaults = {
        timeout: 8000,
        cache: false,
        error: function(xhr, status, error) {
        if (status !== 'abort') {
        console.warn('Dashboard AJAX Error:', status, error);
        }
        }
        };

        const xhr = $.ajax($.extend({}, defaults, options))
        .done(function(data) {
        // Cache successful responses
        window.dashboardCache[cacheKey] = {
        data: data,
        timestamp: Date.now()
        };
        });

        return xhr;
        };

        // Test the interception immediately
        setTimeout(function() {
        $.ajax({
        url: "data:text/plain,test",
        });
        }, 100);

</script>
HTML;
}
