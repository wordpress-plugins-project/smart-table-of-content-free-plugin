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
                
                const targetId = this.getAttribute('href').slice(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    // Set scrolling flag to prevent highlight interference
                    isScrolling = true;
                    
                    // Clear any existing timeout
                    if (scrollTimeout) {
                        clearTimeout(scrollTimeout);
                    }
                    
                    const offset = parseInt(settings.scrollOffset, 10) || 80;
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - offset;
                    
                    // Calculate max scroll position
                    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                    const finalPosition = Math.min(targetPosition, maxScroll);
                    
                    window.scrollTo({
                        top: finalPosition,
                        behavior: 'smooth'
                    });

                    // Update URL hash without jumping
                    history.pushState(null, null, '#' + targetId);

                    // Focus the target for accessibility
                    targetElement.setAttribute('tabindex', '-1');
                    targetElement.focus({ preventScroll: true });
                    
                    // Manually set active state for clicked link
                    const allLinks = toc.querySelectorAll('.smart-toc-list a');
                    allLinks.forEach(function(l) {
                        l.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Reset scrolling flag after animation completes
                    scrollTimeout = setTimeout(function() {
                        isScrolling = false;
                    }, 1000);
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
            // Skip if we're in the middle of a programmatic scroll
            if (isScrolling) {
                ticking = false;
                return;
            }
            
            const scrollPosition = window.pageYOffset;
            const offset = parseInt(settings.scrollOffset, 10) || 80;
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            
            let activeHeading = null;
            
            // Check if we're at the bottom of the page
            const isAtBottom = (scrollPosition + windowHeight) >= (documentHeight - 50);
            
            if (isAtBottom && headings.length > 0) {
                // If at bottom, highlight the last heading
                activeHeading = headings[headings.length - 1];
            } else {
                // Find the current active heading
                for (let i = headings.length - 1; i >= 0; i--) {
                    const heading = headings[i];
                    const headingTop = heading.element.getBoundingClientRect().top + window.pageYOffset;
                    
                    if (scrollPosition >= headingTop - offset - 10) {
                        activeHeading = heading;
                        break;
                    }
                }
            }

            // Update active states
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
