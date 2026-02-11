/**
 * Smart TOC - Frontend JavaScript
 *
 * @package Anik_Smart_TOC
 */

(function() {
    'use strict';

    // Get settings from WordPress
    const settings = window.aniksmtaSettings || {
        smoothScroll: true,
        highlightActive: true,
        scrollOffset: 80
    };

    // Flag to prevent highlight updates during programmatic scroll
    let isScrolling = false;
    let scrollTimeout = null;

    /**
     * Initialize TOC functionality
     */
    function init() {
        const tocContainers = document.querySelectorAll('.smart-toc');
        
        if (!tocContainers.length) {
            return;
        }

        tocContainers.forEach(function(toc) {
            initToggle(toc);
            initSmoothScroll(toc);
        });

        if (settings.highlightActive) {
            initActiveHighlight();
        }
    }

    /**
     * Initialize toggle functionality
     */
    function initToggle(toc) {
        const toggleBtn = toc.querySelector('.smart-toc-toggle');
        
        if (!toggleBtn) {
            return;
        }

        toggleBtn.addEventListener('click', function() {
            toc.classList.toggle('collapsed');
            
            const isCollapsed = toc.classList.contains('collapsed');
            toggleBtn.setAttribute('aria-expanded', !isCollapsed);
        });
    }

    /**
     * Initialize smooth scroll
     */
    function initSmoothScroll(toc) {
        if (!settings.smoothScroll) {
            return;
        }

        const links = toc.querySelectorAll('.smart-toc-list a');
        
        links.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                const clickedLink = this;
                
                if (!targetElement) {
                    return;
                }
                
                // Set scrolling flag
                isScrolling = true;
                
                // Clear any existing timeout
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                
                const offset = parseInt(settings.scrollOffset, 10) || 80;
                
                // Set active state immediately
                const allLinks = toc.querySelectorAll('.smart-toc-list a');
                allLinks.forEach(function(l) {
                    l.classList.remove('active');
                });
                clickedLink.classList.add('active');
                
                // Calculate position
                const targetRect = targetElement.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const targetY = targetRect.top + scrollTop - offset;
                
                // Scroll using scrollTo
                window.scrollTo({
                    top: Math.max(0, targetY),
                    behavior: 'smooth'
                });

                // Update URL
                if (history.pushState) {
                    history.pushState(null, '', '#' + targetId);
                }
                
                // Reset flag after scroll completes
                scrollTimeout = setTimeout(function() {
                    isScrolling = false;
                }, 1000);
            });
        });
    }

    /**
     * Initialize active heading highlight
     */
    function initActiveHighlight() {
        const tocLinks = document.querySelectorAll('.smart-toc-list a');
        
        if (!tocLinks.length) {
            return;
        }

        // Get all heading targets
        const headings = [];
        tocLinks.forEach(function(link) {
            const targetId = link.getAttribute('href').slice(1);
            const heading = document.getElementById(targetId);
            if (heading) {
                headings.push({
                    element: heading,
                    link: link
                });
            }
        });

        if (!headings.length) {
            return;
        }

        // Throttled scroll handler
        let ticking = false;
        
        function updateActiveHeading() {
            // Skip during programmatic scroll
            if (isScrolling) {
                ticking = false;
                return;
            }
            
            const scrollPos = window.pageYOffset || document.documentElement.scrollTop;
            const offset = parseInt(settings.scrollOffset, 10) || 80;
            
            let activeHeading = null;
            
            // Find heading that user has scrolled past
            for (let i = 0; i < headings.length; i++) {
                const rect = headings[i].element.getBoundingClientRect();
                if (rect.top <= offset + 10) {
                    activeHeading = headings[i];
                }
            }

            // Update active class
            tocLinks.forEach(function(link) {
                link.classList.remove('active');
            });

            if (activeHeading) {
                activeHeading.link.classList.add('active');
            }

            ticking = false;
        }

        function onScroll() {
            if (!ticking) {
                requestAnimationFrame(updateActiveHeading);
                ticking = true;
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        
        // Initial check after a small delay
        setTimeout(updateActiveHeading, 100);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
