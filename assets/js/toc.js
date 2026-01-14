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
                e.stopPropagation();
                
                const targetId = this.getAttribute('href').slice(1);
                const targetElement = document.getElementById(targetId);
                const clickedLink = this;
                
                if (targetElement) {
                    // Set scrolling flag to prevent highlight interference
                    isScrolling = true;
                    
                    // Clear any existing timeout
                    if (scrollTimeout) {
                        clearTimeout(scrollTimeout);
                    }
                    
                    const offset = parseInt(settings.scrollOffset, 10) || 80;
                    
                    // Manually set active state for clicked link immediately
                    const allLinks = toc.querySelectorAll('.smart-toc-list a');
                    allLinks.forEach(function(l) {
                        l.classList.remove('active');
                    });
                    clickedLink.classList.add('active');
                    
                    // Calculate target position
                    const elementRect = targetElement.getBoundingClientRect();
                    const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
                    const targetPosition = currentScroll + elementRect.top - offset;
                    
                    // Check if we can actually scroll to the target
                    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                    const finalPosition = Math.min(Math.max(0, targetPosition), maxScroll);
                    
                    // Scroll to target
                    window.scrollTo({
                        top: finalPosition,
                        behavior: 'smooth'
                    });

                    // Update URL hash without jumping
                    if (history.pushState) {
                        history.pushState(null, null, '#' + targetId);
                    }

                    // Focus the target for accessibility
                    targetElement.setAttribute('tabindex', '-1');
                    targetElement.focus({ preventScroll: true });
                    
                    // Reset scrolling flag after animation completes
                    scrollTimeout = setTimeout(function() {
                        isScrolling = false;
                        // Keep the clicked link active even after scroll ends
                        allLinks.forEach(function(l) {
                            l.classList.remove('active');
                        });
                        clickedLink.classList.add('active');
                    }, 1200);
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
            
            const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            const offset = parseInt(settings.scrollOffset, 10) || 80;
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            
            let activeHeading = null;
            
            // Check if we're near the bottom of the page (within 50px)
            const isNearBottom = (scrollPosition + windowHeight) >= (documentHeight - 50);
            
            if (isNearBottom && headings.length > 0) {
                // If near bottom, find the last visible heading
                for (let i = headings.length - 1; i >= 0; i--) {
                    const heading = headings[i];
                    const headingRect = heading.element.getBoundingClientRect();
                    // If this heading is visible on screen
                    if (headingRect.top < windowHeight) {
                        activeHeading = heading;
                        break;
                    }
                }
                // Fallback to last heading if none visible
                if (!activeHeading) {
                    activeHeading = headings[headings.length - 1];
                }
            } else {
                // Normal scroll behavior - find the heading that's currently in view
                for (let i = 0; i < headings.length; i++) {
                    const heading = headings[i];
                    const headingTop = heading.element.getBoundingClientRect().top;
                    
                    // If heading is at or above the offset point
                    if (headingTop <= offset + 50) {
                        activeHeading = heading;
                    } else {
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
                
                // Scroll the TOC to show active link if needed
                const tocBody = activeHeading.link.closest('.smart-toc-body');
                if (tocBody) {
                    const linkRect = activeHeading.link.getBoundingClientRect();
                    const bodyRect = tocBody.getBoundingClientRect();
                    
                    if (linkRect.top < bodyRect.top || linkRect.bottom > bodyRect.bottom) {
                        activeHeading.link.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                }
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
