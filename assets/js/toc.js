/**
 * Smart TOC - Frontend JavaScript
 *
 * @package Anik_Smart_TOC
 */

(function () {
  "use strict";

  const settings = Object.assign(
    {
      smoothScroll: true,
      highlightActive: true,
      scrollOffset: 80,
      copyLink: false,
      readingProgress: true,
      dynamicContent: true,
      lazyLoad: true,
      mobileModal: false,
      floatingDesktop: true,
      floatingTocPosition: "right",
      floatingTocStyle: "icon_text",
      floatingTocTheme: "dark",
      floatingTocPanelWidth: 320,
      floatingTocAutoClose: true,
      floatingTocShowProgress: true,
      floatingTocDefaultExpanded: true,
      stickyPosition: "inline",
      stickyWidth: 280,
      stickyOffsetTop: 20,
      collapsibleSections: true,
      sectionsCollapsed: false,
      backToTop: false,
      backToTopIcon: "arrow",
      backToTopStyle: "circle",
      backToTopBgColor: "",
      backToTopIconColor: "#ffffff",
      backToTopShowDesktop: true,
      backToTopShowTablet: true,
      backToTopShowMobile: true,
      autoDarkMode: false,
      copyLabel: "Copy",
      copySuccessLabel: "Copied",
      copyErrorLabel: "Error",
      mobileOpenLabel: "Contents",
      mobileCloseLabel: "Close",
      desktopOpenLabel: "Table of Contents",
    },
    window.aniksmtaSettings || {},
  );

  let isScrolling = false;
  let scrollTimeout = null;
  let highlightBound = false;
  let dynamicObserverBound = false;
  let readingProgressBound = false;
  let lazyObserver = null;

  function init() {
    if (settings.dynamicContent && !dynamicObserverBound) {
      initDynamicContentObserver();
      dynamicObserverBound = true;
    }

    if (settings.lazyLoad) {
      observeUninitializedTocs();
    } else {
      initAvailableTocs();
    }
  }

  function initAvailableTocs() {
    const tocContainers = document.querySelectorAll(".smart-toc");
    let initializedCount = 0;

    tocContainers.forEach(function (toc) {
      if (initTocContainer(toc)) {
        initializedCount += 1;
      }
    });

    if (initializedCount > 0) {
      maybeBindHighlight();
      ensureReadingProgress();
    }
  }

  function initTocContainer(toc) {
    if (!toc || toc.dataset.aniksmtaInit === "1") {
      return false;
    }

    toc.dataset.aniksmtaInit = "1";
    initToggle(toc);
    initSmoothScroll(toc);
    initMobileModal(toc);
    initFloatingDesktop(toc);
    initStickyToc(toc);
    initCollapsibleSections(toc);
    initCopyLinks(toc);
    initBackToTop();

    return true;
  }

  function observeUninitializedTocs() {
    const pendingTocs = Array.prototype.slice.call(
      document.querySelectorAll('.smart-toc:not([data-aniksmta-init="1"])'),
    );

    if (!pendingTocs.length) {
      return;
    }

    if (typeof IntersectionObserver === "undefined") {
      pendingTocs.forEach(function (toc) {
        initTocContainer(toc);
      });
      ensureReadingProgress();
      return;
    }

    if (!lazyObserver) {
      lazyObserver = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) {
              return;
            }

            initTocContainer(entry.target);
            maybeBindHighlight();
            ensureReadingProgress();
            lazyObserver.unobserve(entry.target);
          });
        },
        {
          root: null,
          rootMargin: "200px 0px",
          threshold: 0,
        },
      );
    }

    pendingTocs.forEach(function (toc) {
      lazyObserver.observe(toc);
    });
  }

  function initToggle(toc) {
    const toggleBtn = toc.querySelector(".smart-toc-toggle");
    if (!toggleBtn) {
      return;
    }

    toggleBtn.addEventListener("click", function () {
      toc.classList.toggle("collapsed");
      toggleBtn.setAttribute(
        "aria-expanded",
        String(!toc.classList.contains("collapsed")),
      );
    });
  }

  function initSmoothScroll(toc) {
    if (!settings.smoothScroll) {
      return;
    }

    const links = toc.querySelectorAll(".smart-toc-list a");
    links.forEach(function (link) {
      link.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();

        const targetId = this.getAttribute("href").substring(1);
        const targetElement = document.getElementById(targetId);
        if (!targetElement) {
          return;
        }

        isScrolling = true;
        if (scrollTimeout) {
          clearTimeout(scrollTimeout);
        }

        const offset = parseInt(settings.scrollOffset, 10) || 80;
        const allLinks = toc.querySelectorAll(".smart-toc-list a");
        allLinks.forEach(function (currentLink) {
          currentLink.classList.remove("active");
        });
        this.classList.add("active");

        const targetRect = targetElement.getBoundingClientRect();
        const scrollTop =
          window.pageYOffset || document.documentElement.scrollTop;
        const targetY = targetRect.top + scrollTop - offset;

        window.scrollTo({
          top: Math.max(0, targetY),
          behavior: "smooth",
        });

        if (history.pushState) {
          history.pushState(null, "", "#" + targetId);
        }

        scrollTimeout = setTimeout(function () {
          isScrolling = false;
        }, 1000);
      });
    });
  }

  function initCopyLinks(toc) {
    if (!settings.copyLink) {
      return;
    }

    const links = toc.querySelectorAll(".smart-toc-list a");
    links.forEach(function (link) {
      const href = link.getAttribute("href") || "";
      const anchorId = href.indexOf("#") !== -1 ? href.split("#")[1] : "";
      if (!anchorId) {
        return;
      }

      let itemInner = link.parentElement;
      if (!itemInner || !itemInner.classList.contains("toc-item-inner")) {
        const item = link.closest(".toc-item");
        if (item) {
          let directInner = null;
          Array.prototype.forEach.call(item.children, function (child) {
            if (!directInner && child.classList.contains("toc-item-inner")) {
              directInner = child;
            }
          });

          if (!directInner) {
            directInner = document.createElement("div");
            directInner.className = "toc-item-inner";
            item.insertBefore(directInner, link);
          }

          if (link.parentElement !== directInner) {
            directInner.appendChild(link);
          }
          itemInner = directInner;
        }
      }

      let copyBtn = null;
      const sibling = link.nextElementSibling;
      if (sibling && sibling.classList.contains("smart-toc-copy-link")) {
        copyBtn = sibling;
      } else if (itemInner) {
        const existingBtn = itemInner.querySelector(".smart-toc-copy-link");
        if (existingBtn) {
          copyBtn = existingBtn;
        }
      } else {
        copyBtn = document.createElement("button");
        copyBtn.type = "button";
        copyBtn.className = "smart-toc-copy-link";
      }

      if (!copyBtn) {
        copyBtn = document.createElement("button");
        copyBtn.type = "button";
        copyBtn.className = "smart-toc-copy-link";
      }

      if (itemInner) {
        if (copyBtn.parentElement !== itemInner) {
          itemInner.appendChild(copyBtn);
        }
        if (link.nextElementSibling !== copyBtn) {
          itemInner.insertBefore(copyBtn, link.nextSibling);
        }
      } else if (copyBtn.parentElement !== link.parentNode) {
        link.parentNode.insertBefore(copyBtn, link.nextSibling);
      }

      copyBtn.setAttribute("data-anchor-id", anchorId);
      copyBtn.setAttribute("aria-label", settings.copyLabel || "Copy");
      copyBtn.innerHTML =
        '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
        '<path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
        '<path d="M10.172 13.828a4 4 0 005.656 0l4-4a4 4 0 10-5.656-5.656l-1.1 1.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
        "</svg>" +
        '<span class="smart-toc-copy-tooltip">' +
        (settings.copyLabel || "Copy") +
        "</span>";

      if (copyBtn.dataset.copyBound === "1") {
        return;
      }
      copyBtn.dataset.copyBound = "1";

      copyBtn.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();

        const currentButton = this;
        const copyAnchorId = currentButton.getAttribute("data-anchor-id") || "";
        if (!copyAnchorId) {
          return;
        }

        const cleanUrl =
          window.location.origin +
          window.location.pathname +
          window.location.search +
          "#" +
          copyAnchorId;

        copyText(cleanUrl)
          .then(function () {
            setCopyButtonState(currentButton, true);
          })
          .catch(function () {
            setCopyButtonState(currentButton, false);
          });
      });
    });
  }

  function initMobileModal(toc) {
    if (
      !settings.mobileModal ||
      !toc.classList.contains("smart-toc-mobile-modal")
    ) {
      return;
    }

    if (toc.dataset.mobileModalInit === "1") {
      return;
    }

    toc.dataset.mobileModalInit = "1";

    if (!toc.id) {
      toc.id = "aniksmta-toc-" + Math.random().toString(36).slice(2, 10);
    }

    const openLabel = settings.mobileOpenLabel || "Contents";
    const closeLabel = settings.mobileCloseLabel || "Close";

    const trigger = document.createElement("button");
    trigger.type = "button";
    trigger.className = "smart-toc-mobile-toggle-btn";
    trigger.setAttribute("aria-controls", toc.id);
    trigger.setAttribute("aria-expanded", "false");
    trigger.textContent = openLabel;

    const overlay = document.createElement("div");
    overlay.className = "smart-toc-mobile-overlay";
    overlay.setAttribute("aria-hidden", "true");

    if (toc.parentNode) {
      toc.parentNode.insertBefore(trigger, toc.nextSibling);
    }
    document.body.appendChild(overlay);

    const header = toc.querySelector(".smart-toc-header");
    let closeButton = toc.querySelector(".smart-toc-mobile-close");
    if (!closeButton && header) {
      closeButton = document.createElement("button");
      closeButton.type = "button";
      closeButton.className = "smart-toc-mobile-close";
      closeButton.setAttribute("aria-label", closeLabel);
      closeButton.textContent = closeLabel;
      header.appendChild(closeButton);
    }

    function closeMobileModal() {
      toc.classList.remove("smart-toc-mobile-open");
      overlay.classList.remove("is-visible");
      overlay.setAttribute("aria-hidden", "true");
      trigger.setAttribute("aria-expanded", "false");
      document.body.classList.remove("smart-toc-mobile-open");
    }

    function openMobileModal() {
      toc.classList.add("smart-toc-mobile-open");
      overlay.classList.add("is-visible");
      overlay.setAttribute("aria-hidden", "false");
      trigger.setAttribute("aria-expanded", "true");
      document.body.classList.add("smart-toc-mobile-open");
    }

    trigger.addEventListener("click", function () {
      if (toc.classList.contains("smart-toc-mobile-open")) {
        closeMobileModal();
      } else {
        openMobileModal();
      }
    });

    overlay.addEventListener("click", closeMobileModal);

    if (closeButton) {
      closeButton.addEventListener("click", closeMobileModal);
    }

    toc.addEventListener(
      "click",
      function (event) {
        const linkTarget =
          event.target && event.target.closest
            ? event.target.closest(".smart-toc-list a")
            : null;

        if (linkTarget && toc.contains(linkTarget)) {
          closeMobileModal();
        }
      },
      true,
    );

    document.addEventListener("keydown", function (event) {
      if (
        event.key === "Escape" &&
        toc.classList.contains("smart-toc-mobile-open")
      ) {
        closeMobileModal();
      }
    });

    window.addEventListener(
      "resize",
      function () {
        if (window.innerWidth > 768) {
          closeMobileModal();
        }
      },
      { passive: true },
    );
  }

  function initStickyToc(toc) {
    if (!toc.classList.contains("smart-toc-sticky")) {
      return;
    }

    const stickyPosition =
      toc.dataset.stickyPosition || settings.stickyPosition || "inline";

    if (stickyPosition === "inline") {
      return;
    }

    const stickyWidth =
      parseInt(toc.dataset.stickyWidth || settings.stickyWidth, 10) || 280;
    const stickyOffset =
      parseInt(toc.dataset.stickyOffset || settings.stickyOffsetTop, 10) || 20;
    const DESKTOP_BREAKPOINT = 1024;

    const placeholder = document.createElement("div");
    placeholder.className = "smart-toc-placeholder";
    placeholder.style.display = "none";
    toc.parentNode.insertBefore(placeholder, toc);

    let originalTop = 0;
    let originalWidth = 0;
    let isSticky = false;
    let ticking = false;
    let resizeTimeout;

    function recalc() {
      const rect = toc.getBoundingClientRect();
      originalTop = rect.top + window.pageYOffset;
      originalWidth = rect.width;
    }

    function applySticky() {
      placeholder.style.display = "block";
      placeholder.style.height = toc.offsetHeight + "px";
      placeholder.style.width = originalWidth + "px";
      placeholder.style.marginBottom = window.getComputedStyle(toc).marginBottom;

      toc.style.top = stickyOffset + "px";
      toc.style.width = stickyWidth + "px";
      toc.style.left = stickyPosition === "left" ? "20px" : "auto";
      toc.style.right = stickyPosition === "right" ? "20px" : "auto";
      toc.classList.add("is-sticky");
      isSticky = true;
    }

    function clearSticky() {
      placeholder.style.display = "none";
      toc.classList.remove("is-sticky");
      toc.style.top = "";
      toc.style.width = "";
      toc.style.left = "";
      toc.style.right = "";
      isSticky = false;
    }

    function updateStickyState() {
      if (window.innerWidth < DESKTOP_BREAKPOINT) {
        if (isSticky) {
          clearSticky();
        }
        ticking = false;
        return;
      }

      const scrollPos =
        window.pageYOffset || document.documentElement.scrollTop || 0;
      const shouldStick = scrollPos > originalTop - stickyOffset;

      if (shouldStick && !isSticky) {
        applySticky();
      } else if (!shouldStick && isSticky) {
        clearSticky();
      }

      ticking = false;
    }

    function onScroll() {
      if (!ticking) {
        window.requestAnimationFrame(updateStickyState);
        ticking = true;
      }
    }

    function onResize() {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(function () {
        if (isSticky) {
          clearSticky();
        }
        recalc();
        updateStickyState();
      }, 200);
    }

    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onResize, { passive: true });
    window.addEventListener("orientationchange", onResize, { passive: true });

    setTimeout(function () {
      recalc();
      updateStickyState();
    }, 120);
  }

  function initFloatingDesktop(toc) {
    if (!settings.floatingDesktop) {
      return;
    }

    if (document.querySelector(".smart-toc-floating")) {
      return;
    }

    const DESKTOP_BREAKPOINT = 1024;
    function isDesktop() {
      return window.innerWidth >= DESKTOP_BREAKPOINT;
    }

    const floatingPosition =
      settings.floatingTocPosition === "left" ? "left" : "right";
    const floatingStyle = ["icon_only", "icon_text", "icon_counter"].includes(
      settings.floatingTocStyle,
    )
      ? settings.floatingTocStyle
      : "icon_text";
    const floatingTheme =
      settings.floatingTocTheme === "dark" ? "dark" : "default";
    const floatingPanelWidth = Math.min(
      450,
      Math.max(280, parseInt(settings.floatingTocPanelWidth, 10) || 320),
    );
    const floatingAutoClose = settings.floatingTocAutoClose !== false;
    const floatingShowProgress = settings.floatingTocShowProgress !== false;
    const floatingDefaultExpanded =
      settings.floatingTocDefaultExpanded !== false;

    const tocLinks = toc.querySelectorAll(".smart-toc-list a");
    const totalItems = tocLinks.length;

    const floatingContainer = document.createElement("div");
    floatingContainer.className =
      "smart-toc-floating floating-" +
      floatingPosition +
      " style-" +
      floatingStyle +
      " floating-theme-" +
      floatingTheme;
    floatingContainer.id =
      "aniksmta-floating-toc-" + Math.random().toString(36).slice(2, 10);

    if (toc.classList.contains("toc-theme-dark") && floatingTheme === "default") {
      floatingContainer.classList.remove("floating-theme-default");
      floatingContainer.classList.add("floating-theme-dark");
    }

    const titleEl = toc.querySelector(".smart-toc-title");
    const tocTitle = titleEl
      ? titleEl.textContent.trim()
      : settings.desktopOpenLabel || "Table of Contents";

    const buttonHTML =
      '<div class="smart-toc-floating-btn-wrapper">' +
      '<button type="button" class="smart-toc-floating-dismiss" aria-label="Hide Table of Contents">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
      '<path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
      "</svg>" +
      "</button>" +
      '<button type="button" class="smart-toc-floating-btn" aria-controls="' +
      floatingContainer.id +
      '" aria-expanded="false">' +
      '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
      '<path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
      "</svg>" +
      '<span class="smart-toc-floating-btn-text">' +
      (settings.desktopOpenLabel || "Table of Contents") +
      "</span>" +
      '<span class="smart-toc-floating-counter">' +
      totalItems +
      "</span>" +
      "</button>" +
      "</div>";

    const panelHTML =
      '<div class="smart-toc-floating-panel" style="width: ' +
      floatingPanelWidth +
      'px;">' +
      '<div class="smart-toc-floating-panel-header">' +
      '<h4 class="smart-toc-floating-panel-title"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;"><path d="M4 6h16M4 12h16M4 18h12"></path></svg>' +
      tocTitle +
      "</h4>" +
      '<span class="smart-toc-floating-panel-close" role="button" tabindex="0" aria-label="' +
      (settings.mobileCloseLabel || "Close") +
      '">&times;</span>' +
      "</div>" +
      (floatingShowProgress
        ? '<div class="smart-toc-floating-progress"><div class="smart-toc-floating-progress-fill"></div></div>'
        : "") +
      '<div class="smart-toc-floating-panel-body"></div></div>';

    floatingContainer.innerHTML = buttonHTML + panelHTML;
    document.body.appendChild(floatingContainer);

    const floatingBtn = floatingContainer.querySelector(
      ".smart-toc-floating-btn",
    );
    const dismissBtn = floatingContainer.querySelector(
      ".smart-toc-floating-dismiss",
    );
    const panelBody = floatingContainer.querySelector(
      ".smart-toc-floating-panel-body",
    );
    const closeBtn = floatingContainer.querySelector(
      ".smart-toc-floating-panel-close",
    );
    const progressFill = floatingContainer.querySelector(
      ".smart-toc-floating-progress-fill",
    );

    dismissBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      floatingContainer.classList.add("dismissed");
      setTimeout(function () {
        floatingContainer.remove();
      }, 300);
    });

    const tocList = toc.querySelector(".smart-toc-list");
    if (tocList) {
      const clonedList = tocList.cloneNode(true);
      clonedList.classList.remove("smart-toc-numbered");

      const existingCopyButtons = clonedList.querySelectorAll(
        ".smart-toc-copy-link",
      );
      existingCopyButtons.forEach(function (btn) {
        btn.remove();
      });

      panelBody.appendChild(clonedList);
    }

    const panelLinks = panelBody.querySelectorAll("a");

    if (settings.collapsibleSections) {
      initCollapsibleSections(panelBody);
    }

    if (settings.copyLink) {
      initCopyLinks(panelBody);
    }

    let isVisible = false;
    let isPanelOpen = false;
    let hasShownOnce = false;
    let ticking = false;
    let tocOriginalTop = 0;

    function updateTocPosition() {
      const rect = toc.getBoundingClientRect();
      tocOriginalTop = rect.top + window.pageYOffset + rect.height;
    }

    function updatePanelActiveLink(scrollPos) {
      const offset = parseInt(settings.scrollOffset, 10) || 80;
      let activeLink = null;

      panelLinks.forEach(function (link) {
        const href = link.getAttribute("href") || "";
        const targetId = href.indexOf("#") !== -1 ? href.split("#")[1] : "";

        if (targetId) {
          const target = document.getElementById(targetId);
          if (target) {
            const headingTop =
              target.getBoundingClientRect().top + window.pageYOffset;
            if (scrollPos >= headingTop - offset - 50) {
              activeLink = link;
            }
          }
        }

        link.classList.remove("active");
      });

      if (activeLink) {
        activeLink.classList.add("active");

        const linkRect = activeLink.getBoundingClientRect();
        const bodyRect = panelBody.getBoundingClientRect();

        if (linkRect.top < bodyRect.top || linkRect.bottom > bodyRect.bottom) {
          activeLink.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      }
    }

    function updateVisibility() {
      if (!isDesktop()) {
        if (isVisible) {
          floatingContainer.classList.remove("visible");
          isVisible = false;
          if (isPanelOpen) {
            closePanel();
          }
        }
        ticking = false;
        return;
      }

      const scrollPos =
        window.pageYOffset || document.documentElement.scrollTop;
      const shouldShow = scrollPos > tocOriginalTop;

      if (shouldShow !== isVisible) {
        isVisible = shouldShow;
        if (shouldShow) {
          floatingContainer.classList.add("visible");
          if (!hasShownOnce && floatingDefaultExpanded) {
            hasShownOnce = true;
            openPanel();
          }
        } else {
          floatingContainer.classList.remove("visible");
          if (isPanelOpen) {
            closePanel();
          }
        }
      }

      if (isPanelOpen) {
        if (floatingShowProgress && progressFill) {
          const docHeight = document.documentElement.scrollHeight - window.innerHeight;
          const progress = docHeight > 0 ? (scrollPos / docHeight) * 100 : 0;
          progressFill.style.width =
            Math.min(100, Math.max(0, progress)) + "%";
        }
        updatePanelActiveLink(scrollPos);
      }

      ticking = false;
    }

    function openPanel() {
      floatingContainer.classList.add("panel-open");
      floatingBtn.setAttribute("aria-expanded", "true");
      isPanelOpen = true;

      const scrollPos =
        window.pageYOffset || document.documentElement.scrollTop;
      updatePanelActiveLink(scrollPos);
      if (floatingShowProgress && progressFill) {
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = docHeight > 0 ? (scrollPos / docHeight) * 100 : 0;
        progressFill.style.width = Math.min(100, Math.max(0, progress)) + "%";
      }
    }

    function closePanel() {
      floatingContainer.classList.remove("panel-open");
      floatingBtn.setAttribute("aria-expanded", "false");
      isPanelOpen = false;
    }

    function togglePanel(e) {
      if (e) e.stopPropagation();
      if (isPanelOpen) {
        closePanel();
      } else {
        openPanel();
      }
    }

    floatingBtn.addEventListener("click", function (e) {
      e.preventDefault();
      togglePanel(e);
    });

    closeBtn.addEventListener("click", function (e) {
      e.preventDefault();
      closePanel();
    });

    panelLinks.forEach(function (link) {
      link.addEventListener("click", function (e) {
        const href = link.getAttribute("href") || "";
        const targetId = href.indexOf("#") !== -1 ? href.split("#")[1] : "";

        if (targetId) {
          const target = document.getElementById(targetId);
          if (target) {
            e.preventDefault();

            const offset = parseInt(settings.scrollOffset, 10) || 80;
            const targetPosition =
              target.getBoundingClientRect().top + window.pageYOffset - offset;

            window.scrollTo({
              top: targetPosition,
              behavior: "smooth",
            });

            if (history.pushState) {
              history.pushState(null, null, "#" + targetId);
            }

            if (floatingAutoClose) {
              setTimeout(closePanel, 300);
            }
          }
        }
      });
    });

    document.addEventListener("click", function (e) {
      if (isPanelOpen && !floatingContainer.contains(e.target)) {
        closePanel();
      }
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && isPanelOpen) {
        closePanel();
        floatingBtn.focus();
      }
    });

    function onScroll() {
      if (!ticking) {
        window.requestAnimationFrame(updateVisibility);
        ticking = true;
      }
    }

    let resizeTimeout;
    function onResize() {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(function () {
        updateTocPosition();
        updateVisibility();
      }, 200);
    }

    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onResize, { passive: true });

    setTimeout(function () {
      updateTocPosition();
      updateVisibility();
    }, 150);

    if (floatingTheme === "default") {
      initAutoDarkMode(floatingContainer);
    }
  }

  function initCollapsibleSections(toc) {
    if (!settings.collapsibleSections) {
      return;
    }

    const items = toc.querySelectorAll(".smart-toc-list .toc-item");
    if (!items.length) return;

    function getLevel(item) {
      const match = item.className.match(/toc-level-(\d)/);
      return match ? parseInt(match[1], 10) : 2;
    }

    function updateVisibility() {
      let activeCollapsedLevel = 999;

      for (let i = 0; i < items.length; i++) {
        const item = items[i];
        const level = getLevel(item);

        if (level <= activeCollapsedLevel) {
          activeCollapsedLevel = 999;
          item.style.display = "";

          if (item.classList.contains("section-collapsed")) {
            activeCollapsedLevel = level;
          }
        } else {
          item.style.display = "none";
        }
      }
    }

    toc.addEventListener("aniksmta-update-visibility", updateVisibility);

    let hasCollapsible = false;

    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      const currentLevel = getLevel(item);
      let hasChildren = false;

      if (i < items.length - 1) {
        const nextLevel = getLevel(items[i + 1]);
        if (nextLevel > currentLevel) {
          hasChildren = true;
        }
      }

      if (hasChildren) {
        hasCollapsible = true;
        item.classList.add("has-children");

        const toggleBtn = document.createElement("button");
        toggleBtn.type = "button";
        toggleBtn.className = "smart-toc-section-toggle";
        toggleBtn.setAttribute(
          "aria-expanded",
          settings.sectionsCollapsed ? "false" : "true",
        );
        toggleBtn.setAttribute("aria-label", "Toggle section");
        toggleBtn.innerHTML =
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';

        let inner = item.querySelector(".toc-item-inner");
        if (!inner) {
          const a = item.querySelector("a");
          if (a) {
            inner = document.createElement("div");
            inner.className = "toc-item-inner";
            item.insertBefore(inner, a);
            inner.appendChild(a);
          }
        }

        if (inner) {
          inner.insertBefore(toggleBtn, inner.firstChild);
        }

        if (settings.sectionsCollapsed) {
          item.classList.add("section-collapsed");
        }

        toggleBtn.addEventListener("click", function (e) {
          e.preventDefault();
          e.stopPropagation();

          const isExpanded = toggleBtn.getAttribute("aria-expanded") === "true";
          toggleBtn.setAttribute("aria-expanded", String(!isExpanded));

          if (isExpanded) {
            item.classList.add("section-collapsed");
          } else {
            item.classList.remove("section-collapsed");
          }

          updateVisibility();
        });
      }
    }

    if (hasCollapsible) {
      updateVisibility();
    }
  }

  function initBackToTop() {
    if (!settings.backToTop) {
      return;
    }

    if (document.querySelector(".smart-toc-back-to-top")) {
      return;
    }

    const iconSvgs = {
      arrow:
        '<svg class="btt-icon" viewBox="0 0 20 20" fill="none"><path d="M10 4l6 6-1.4 1.4L10 6.8l-4.6 4.6L4 10l6-6z" fill="currentColor"/></svg>',
      chevron:
        '<svg class="btt-icon" viewBox="0 0 20 20" fill="none"><path d="M5 12l5-5 5 5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
      double:
        '<svg class="btt-icon" viewBox="0 0 20 20" fill="none"><path d="M5 14l5-4 5 4M5 10l5-4 5 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
      rocket:
        '<svg class="btt-icon" viewBox="0 0 20 20" fill="none"><path d="M10 2c-2 2-4 5-4 8 0 1.5.5 3 1.5 4l.5.5V17l2-2 2 2v-2.5l.5-.5c1-1 1.5-2.5 1.5-4 0-3-2-6-4-8z" fill="currentColor"/><circle cx="10" cy="9" r="1.5" fill="currentColor" opacity="0.3"/></svg>',
    };

    const iconStyle = ["arrow", "chevron", "double", "rocket"].includes(
      settings.backToTopIcon,
    )
      ? settings.backToTopIcon
      : "arrow";
    const shapeStyle = ["circle", "rounded", "pill"].includes(
      settings.backToTopStyle,
    )
      ? settings.backToTopStyle
      : "circle";

    const showDesktop = settings.backToTopShowDesktop !== false;
    const showTablet = settings.backToTopShowTablet !== false;
    const showMobile = settings.backToTopShowMobile !== false;

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "smart-toc-back-to-top smart-toc-btt-" + shapeStyle;
    btn.setAttribute("aria-label", "Back to top");
    btn.innerHTML =
      '<svg class="progress-ring" viewBox="0 0 56 56">' +
      '<circle class="progress-ring-bg" cx="28" cy="28" r="26"></circle>' +
      '<circle class="progress-ring-fill" cx="28" cy="28" r="26"></circle>' +
      "</svg>" +
      (iconSvgs[iconStyle] || iconSvgs.arrow);

    if (settings.backToTopBgColor) {
      btn.style.setProperty("--btt-bg-color", settings.backToTopBgColor);
    }
    if (settings.backToTopIconColor) {
      btn.style.setProperty("--btt-icon-color", settings.backToTopIconColor);
    }

    document.body.appendChild(btn);

    const progressRing = btn.querySelector(".progress-ring-fill");
    const circumference = 2 * Math.PI * 26;
    let isVisible = false;
    let ticking = false;

    function shouldShowOnDevice() {
      const width = window.innerWidth;
      if (width > 1024) {
        return showDesktop;
      }
      if (width >= 768) {
        return showTablet;
      }
      return showMobile;
    }

    function updateBackToTop() {
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const progress = docHeight > 0 ? scrollTop / docHeight : 0;
      const shouldShow = scrollTop > 300 && shouldShowOnDevice();

      if (progressRing) {
        const offset = circumference - progress * circumference;
        progressRing.style.strokeDashoffset = offset;
      }

      if (shouldShow !== isVisible) {
        isVisible = shouldShow;
        btn.classList.toggle("is-visible", shouldShow);
        btn.classList.toggle("visible", shouldShow);
      }

      ticking = false;
    }

    function onScrollOrResize() {
      if (!ticking) {
        window.requestAnimationFrame(updateBackToTop);
        ticking = true;
      }
    }

    btn.addEventListener("click", function (e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: "smooth" });
    });

    window.addEventListener("scroll", onScrollOrResize, { passive: true });
    window.addEventListener("resize", onScrollOrResize, { passive: true });
    updateBackToTop();
  }

  function initAutoDarkMode(toc) {
    if (!settings.autoDarkMode || !window.matchMedia) {
      return;
    }

    const darkModeQuery = window.matchMedia("(prefers-color-scheme: dark)");

    function applyThemeToToc(isDark) {
      if (toc.classList.contains("smart-toc-floating")) {
        if (isDark) {
          toc.classList.remove("floating-theme-default");
          toc.classList.add("floating-theme-dark");
        } else {
          toc.classList.remove("floating-theme-dark");
          toc.classList.add("floating-theme-default");
        }
        return;
      }

      if (isDark) {
        if (!toc.dataset.origTheme) {
          const match = toc.className.match(/toc-theme-[a-z]+/);
          toc.dataset.origTheme = match ? match[0] : "toc-theme-default";
        }
        toc.classList.remove(
          "toc-theme-default",
          "toc-theme-light",
          "toc-theme-minimal",
        );
        toc.classList.add("toc-theme-dark");
      } else {
        if (toc.dataset.origTheme) {
          toc.classList.remove("toc-theme-dark");
          toc.classList.add(toc.dataset.origTheme);
        }

      }
    }

    if (darkModeQuery.addEventListener) {
      darkModeQuery.addEventListener("change", function (e) {
        applyThemeToToc(e.matches);
      });
    } else if (darkModeQuery.addListener) {
      darkModeQuery.addListener(function (e) {
        applyThemeToToc(e.matches);
      });
    }

    applyThemeToToc(darkModeQuery.matches);
  }

  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text);
    }

    return new Promise(function (resolve, reject) {
      try {
        const textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.setAttribute("readonly", "");
        textarea.style.position = "fixed";
        textarea.style.top = "-9999px";
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        const success = document.execCommand("copy");
        document.body.removeChild(textarea);
        if (success) {
          resolve();
        } else {
          reject(new Error("copy command failed"));
        }
      } catch (error) {
        reject(error);
      }
    });
  }

  function setCopyButtonState(button, copied) {
    const okLabel = settings.copySuccessLabel || "Copied";
    const errLabel = settings.copyErrorLabel || "Error";
    const fallbackLabel = settings.copyLabel || "Copy";
    const feedbackLabel = copied ? okLabel : errLabel;
    button.classList.toggle("copied", copied);
    button.classList.toggle("is-feedback", !copied);
    button.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
      (copied
        ? '<path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
        : '<path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.172 13.828a4 4 0 005.656 0l4-4a4 4 0 10-5.656-5.656l-1.1 1.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>') +
      "</svg>" +
      '<span class="smart-toc-copy-tooltip">' +
      feedbackLabel +
      "</span>";

    window.setTimeout(function () {
      button.classList.remove("copied");
      button.classList.remove("is-feedback");
      button.innerHTML =
        '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
        '<path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
        '<path d="M10.172 13.828a4 4 0 005.656 0l4-4a4 4 0 10-5.656-5.656l-1.1 1.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
        "</svg>" +
        '<span class="smart-toc-copy-tooltip">' +
        fallbackLabel +
        "</span>";
    }, 1800);
  }

  function initActiveHighlight() {
    let ticking = false;

    function updateActiveHeading() {
      if (isScrolling) {
        ticking = false;
        return;
      }

      const tocLinks = document.querySelectorAll(".smart-toc-list a");
      if (!tocLinks.length) {
        ticking = false;
        return;
      }

      const offset = parseInt(settings.scrollOffset, 10) || 80;
      let activeLink = null;

      tocLinks.forEach(function (link) {
        const targetId = link.getAttribute("href").slice(1);
        const heading = document.getElementById(targetId);
        if (heading && heading.getBoundingClientRect().top <= offset + 10) {
          activeLink = link;
        }
      });

      tocLinks.forEach(function (link) {
        link.classList.remove("active");
      });

      if (activeLink) {
        activeLink.classList.add("active");

        if (settings.collapsibleSections) {
          const activeItem = activeLink.closest(".toc-item");
          if (activeItem && activeItem.style.display === "none") {
            let currentItem = activeItem.previousElementSibling;
            let currentTargetLevel =
              activeItem.className.match(/toc-level-(\d)/);
            currentTargetLevel = currentTargetLevel
              ? parseInt(currentTargetLevel[1], 10)
              : 6;
            let changed = false;

            while (currentItem) {
              const levelMatch = currentItem.className.match(/toc-level-(\d)/);
              if (levelMatch) {
                const level = parseInt(levelMatch[1], 10);
                if (level < currentTargetLevel) {
                  if (currentItem.classList.contains("section-collapsed")) {
                    currentItem.classList.remove("section-collapsed");
                    const toggle = currentItem.querySelector(
                      ".smart-toc-section-toggle",
                    );
                    if (toggle) toggle.setAttribute("aria-expanded", "true");
                    changed = true;
                  }
                  currentTargetLevel = level;
                }
              }
              currentItem = currentItem.previousElementSibling;
            }

            if (changed) {
              const targetToc =
                activeLink.closest(".smart-toc") ||
                activeLink.closest(".smart-toc-floating-panel-body");
              if (targetToc) {
                targetToc.dispatchEvent(
                  new CustomEvent("aniksmta-update-visibility"),
                );
              }
            }
          }
        }
      }

      ticking = false;
    }

    function onScroll() {
      if (!ticking) {
        window.requestAnimationFrame(updateActiveHeading);
        ticking = true;
      }
    }

    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll, { passive: true });
    window.setTimeout(updateActiveHeading, 120);
  }

  function maybeBindHighlight() {
    if (!settings.highlightActive || highlightBound) {
      return;
    }

    if (!document.querySelector(".smart-toc-list a")) {
      return;
    }

    initActiveHighlight();
    highlightBound = true;
  }

  function ensureReadingProgress() {
    if (!settings.readingProgress || readingProgressBound) {
      return;
    }

    if (!document.querySelector(".smart-toc")) {
      return;
    }

    let progressBar = document.querySelector(".smart-toc-reading-progress");
    if (!progressBar) {
      progressBar = document.createElement("div");
      progressBar.className = "smart-toc-reading-progress";
      progressBar.setAttribute("aria-hidden", "true");
      document.body.appendChild(progressBar);
    }

    function updateProgress() {
      const doc = document.documentElement;
      const scrollTop = window.pageYOffset || doc.scrollTop || 0;
      const maxScrollable = Math.max(0, doc.scrollHeight - window.innerHeight);
      const percentage =
        maxScrollable > 0
          ? Math.min(100, (scrollTop / maxScrollable) * 100)
          : 0;
      progressBar.style.width = percentage + "%";
    }

    window.addEventListener("scroll", updateProgress, { passive: true });
    window.addEventListener("resize", updateProgress, { passive: true });
    updateProgress();
    readingProgressBound = true;
  }

  function initDynamicContentObserver() {
    if (typeof MutationObserver === "undefined") {
      return;
    }

    const observer = new MutationObserver(function (mutations) {
      let hasNewToc = false;

      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (!node || node.nodeType !== 1) {
            return;
          }

          if (
            (node.matches && node.matches(".smart-toc")) ||
            (node.querySelector && node.querySelector(".smart-toc"))
          ) {
            hasNewToc = true;
          }
        });
      });

      if (!hasNewToc) {
        return;
      }

      if (settings.lazyLoad) {
        observeUninitializedTocs();
      } else {
        initAvailableTocs();
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
