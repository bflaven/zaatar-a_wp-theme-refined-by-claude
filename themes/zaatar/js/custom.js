'use strict';

/*!
 * Custom v1.3
 * Contains handlers for the different site functions
 *
 * Copyright (c) 2013-2019 Zaatar
 * License: GNU General Public License v2 or later
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * v1.1 (theme 1.3): responsive videos handled in CSS (aspect-ratio),
 * fitvids.js removed; enquire.js replaced by native window.matchMedia.
 * v1.2 (theme 1.4): mobile menu is a server-rendered native <dialog>
 * (vanilla JS, no clone hack, focus managed by the dialog element);
 * overlay-effect div removed, Escape handled natively.
 * v1.3 (theme 1.4): jQuery dropped. Superfish + hoverIntent removed:
 * the desktop dropdown menu is pure CSS (:hover / :focus-within, arrows
 * via .menu-item-has-children). Responsive tables and scroll-to-top
 * ported to vanilla JS.
 * v1.4 (theme 1.4): sticky header — IntersectionObserver sentinel
 * toggles .is-pinned on #masthead for the scrolled shadow.
 * v1.5 (theme 1.4): dark-mode toggle (data-theme + localStorage, scheme
 * bootstrap is inline in wp_head); --header-height published via
 * ResizeObserver for anchor scroll-margin; scroll-to-top honors
 * prefers-reduced-motion.
 */

(function () {
  var allium = {

    // Mobile Menu (native <dialog>, server-rendered in site-navigation.php)
    mobileMenu: {
      dialog: null,
      toggle: null,

      init: function () {
        var self = this;
        this.dialog = document.getElementById('header-menu-responsive');
        this.toggle = document.querySelector('.toggle-menu-control');

        if (!this.dialog || !this.toggle || typeof this.dialog.showModal !== 'function') {
          return;
        }

        // Open
        this.toggle.addEventListener('click', function () {
          self.open();
        });

        // Close button
        var closeButton = this.dialog.querySelector('.header-menu-responsive-close');
        if (closeButton) {
          closeButton.addEventListener('click', function () {
            self.dialog.close();
          });
        }

        // Close on backdrop click (the dialog itself is only hit outside
        // .header-menu-responsive-inside) and on any menu link click
        this.dialog.addEventListener('click', function (e) {
          if (e.target === self.dialog || e.target.closest('a')) {
            self.dialog.close();
          }
        });

        // Native close (Escape, close()): restore state; the dialog
        // element returns focus to the toggle button by itself
        this.dialog.addEventListener('close', function () {
          self.toggle.setAttribute('aria-expanded', 'false');
          document.body.classList.remove('has-responsive-menu');
        });

        // Submenu toggles: real <button> siblings after the parent link
        // (never nested inside the <a>)
        var parentLinks = this.dialog.querySelectorAll('.menu-item-has-children > a, .page_item_has_children > a');
        Array.prototype.forEach.call(parentLinks, function (link) {
          var button = document.createElement('button');
          button.type = 'button';
          button.className = 'dropdown-toggle';
          button.setAttribute('aria-expanded', 'false');
          button.setAttribute('aria-label', (window.alliumL10n && window.alliumL10n.toggleSubmenu) || 'Toggle submenu');
          link.after(button);

          button.addEventListener('click', function () {
            var submenu = button.nextElementSibling;
            var expanded = button.getAttribute('aria-expanded') === 'true';
            button.classList.toggle('toggle-on');
            if (submenu && (submenu.classList.contains('sub-menu') || submenu.classList.contains('children'))) {
              submenu.classList.toggle('toggle-on');
            }
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
          });
        });
      },

      open: function () {
        this.dialog.showModal();
        this.toggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('has-responsive-menu');
      },

      close: function () {
        if (this.dialog && this.dialog.open) {
          this.dialog.close();
        }
      }
    },

    // Media Queries (native matchMedia; replaces enquire.js)
    mqInit: function () {
      var mq = window.matchMedia('screen and (max-width: 767px)');

      function tables() {
        return document.querySelectorAll('.entry-content table, .sidebar table');
      }

      function match() {
        // Responsive Tables (skip tables already wrapped)
        Array.prototype.forEach.call(tables(), function (table) {
          if (table.parentElement && table.parentElement.classList.contains('table-responsive')) {
            return;
          }
          var wrapper = document.createElement('div');
          wrapper.className = 'table-responsive';
          table.parentNode.insertBefore(wrapper, table);
          wrapper.appendChild(table);
        });
      }

      function unmatch() {
        // Close the mobile menu when leaving the mobile breakpoint
        allium.mobileMenu.close();

        // Responsive Tables Undo
        Array.prototype.forEach.call(tables(), function (table) {
          var wrapper = table.parentElement;
          if (wrapper && wrapper.classList.contains('table-responsive')) {
            wrapper.replaceWith(table);
          }
        });
      }

      function onChange(e) {
        if (e.matches) {
          match();
        } else {
          unmatch();
        }
      }

      if (typeof mq.addEventListener === 'function') {
        mq.addEventListener('change', onChange);
      } else {
        // Safari < 14
        mq.addListener(onChange);
      }

      // Like enquire: fire match on load only when the query already matches;
      // unmatch only runs on a real transition.
      if (mq.matches) {
        match();
      }
    }

  };

  /**
   * Sticky header: toggle .is-pinned (shadow) once the header sticks.
   * A zero-height sentinel placed right before #masthead leaves the
   * viewport exactly when the header reaches its sticky position.
   */
  function stickyHeader() {
    var header = document.getElementById('masthead');
    if (!header || typeof IntersectionObserver !== 'function') {
      return;
    }

    var sentinel = document.createElement('div');
    sentinel.setAttribute('aria-hidden', 'true');
    header.before(sentinel);

    new IntersectionObserver(function (entries) {
      header.classList.toggle('is-pinned', !entries[0].isIntersecting);
    }).observe(sentinel);

    // Publish the header height so anchor targets can scroll clear of
    // the sticky header (scroll-margin-top uses --header-height).
    function syncHeight() {
      document.documentElement.style.setProperty('--header-height', header.offsetHeight + 'px');
    }
    if (typeof ResizeObserver === 'function') {
      new ResizeObserver(syncHeight).observe(header);
    } else {
      window.addEventListener('resize', syncHeight, { passive: true });
    }
    syncHeight();
  }

  /**
   * Dark mode toggle. The effective scheme was already stamped on <html>
   * by the inline head script (allium_theme_scheme_bootstrap); this only
   * wires the button and follows OS changes while no choice is stored.
   */
  function themeToggle() {
    var button = document.querySelector('.theme-toggle');
    var root = document.documentElement;
    if (!button) {
      return;
    }

    // When the header_social_icons plugin renders its icon bar, dock the
    // toggle at its far right (listeners survive the move); otherwise the
    // button keeps its fallback spot in the main navigation.
    var socialBar = document.querySelector('#iconsboxhead .links-description');
    if (socialBar) {
      socialBar.appendChild(button);
    }

    function sync() {
      button.setAttribute('aria-pressed', root.getAttribute('data-theme') === 'dark' ? 'true' : 'false');
    }
    sync();

    button.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('zaatar-theme', next); } catch (e) {}
      sync();
    });

    if (window.matchMedia) {
      var mq = window.matchMedia('(prefers-color-scheme: dark)');
      var onChange = function (e) {
        var stored = null;
        try { stored = localStorage.getItem('zaatar-theme'); } catch (err) {}
        if (stored !== 'dark' && stored !== 'light') {
          root.setAttribute('data-theme', e.matches ? 'dark' : 'light');
          sync();
        }
      };
      if (typeof mq.addEventListener === 'function') {
        mq.addEventListener('change', onChange);
      } else {
        mq.addListener(onChange); // Safari < 14
      }
    }
  }

  /**
   * Scroll to top
   */
  function scrollToTop() {
    var button = document.getElementById('scroll-to-top');
    if (!button) {
      return;
    }

    function updateVisibility() {
      button.classList.toggle('hidden', window.scrollY <= 100);
    }

    window.addEventListener('scroll', updateVisibility, { passive: true });
    updateVisibility();

    button.addEventListener('click', function (e) {
      e.preventDefault();
      var smooth = !window.matchMedia || window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
      window.scrollTo({ top: 0, behavior: smooth ? 'smooth' : 'auto' });
    });
  }

  function init() {
    // Mobile Menu
    allium.mobileMenu.init();

    // Media Queries
    allium.mqInit();

    // Sticky header
    stickyHeader();

    // Dark mode toggle
    themeToggle();

    // Scroll to top
    scrollToTop();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
