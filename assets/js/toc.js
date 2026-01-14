/**
 * Smart TOC - Frontend JavaScript
 *
 * @package Smart_TOC
 */

(function() {
    'use strict';

    // Get settings from WordPress
    const settings = window.smartTocSettings || {
        smoothScroll: true,
        highlightActive: true,
        scrollOffset: 80
    };

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
                
                const targetId = this.getAttribute('href').slice(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    const offset = parseInt(settings.scrollOffset, 10) || 80;
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - offset;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });

                    // Update URL hash without jumping
                    history.pushState(null, null, '#' + targetId);

                    // Focus the target for accessibility
                    targetElement.setAttribute('tabindex', '-1');
                    targetElement.focus({ preventScroll: true });
                }
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
            const scrollPosition = window.pageYOffset;
            const offset = parseInt(settings.scrollOffset, 10) || 80;
            
            let activeHeading = null;
            
            // Find the current active heading
            for (let i = headings.length - 1; i >= 0; i--) {
                const heading = headings[i];
                const headingTop = heading.element.getBoundingClientRect().top + window.pageYOffset;
                
                if (scrollPosition >= headingTop - offset - 10) {
                    activeHeading = heading;
                    break;
                }
            }

            // Update active states
            tocLinks.forEach(function(link) {
                link.classList.remove('active');
            });

            if (activeHeading) {
                activeHeading.link.classList.add('active');
                
                // Scroll active item into view in TOC if needed
                scrollActiveIntoView(activeHeading.link);
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
        
        // Initial check
        updateActiveHeading();
    }

    /**
     * Scroll active TOC item into view
     */
    function scrollActiveIntoView(activeLink) {
        const tocBody = activeLink.closest('.smart-toc-body');
        
        if (!tocBody) {
            return;
        }

        const linkRect = activeLink.getBoundingClientRect();
        const bodyRect = tocBody.getBoundingClientRect();

        if (linkRect.top < bodyRect.top || linkRect.bottom > bodyRect.bottom) {
            activeLink.scrollIntoView({
                block: 'nearest',
                behavior: 'smooth'
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
