/**
 * Theme Customizer enhancements for a better user experience.
 *
 * Contains handlers to make Theme Customizer preview reload changes
 * asynchronously. Vanilla JS (theme 1.4, jQuery dropped).
 */

/* global wp:true */

(function () {
  function each(selector, callback) {
    Array.prototype.forEach.call(document.querySelectorAll(selector), callback);
  }

  // Site Title and Description.
  wp.customize('blogname', function (value) {
    value.bind(function (to) {
      each('.site-title a', function (el) {
        el.textContent = to;
      });
    });
  });

  wp.customize('blogdescription', function (value) {
    value.bind(function (to) {
      each('.site-description', function (el) {
        el.textContent = to;
      });
    });
  });

  // Background Color
  wp.customize('background_color', function (value) {
    value.bind(function (to) {
      document.body.style.backgroundColor = to;
    });
  });

  // Header text color.
  wp.customize('header_textcolor', function (value) {
    value.bind(function (to) {
      if (to === 'blank') {
        each('.site-title a, .site-description', function (el) {
          el.style.clip = 'rect(1px, 1px, 1px, 1px)';
          el.style.position = 'absolute';
        });
      } else {
        each('.site-title a, .site-description', function (el) {
          el.style.clip = 'auto';
          el.style.position = 'relative';
          el.style.color = to;
        });
        each('.site-description', function (el) {
          el.style.opacity = 0.7;
        });
      }
    });
  });

  // Read More Label
  wp.customize('allium_read_more_label', function (value) {
    value.bind(function (to) {
      each('.more-link', function (el) {
        el.innerHTML = to;
      });
    });
  });

  // Copyright Control
  wp.customize('allium_copyright', function (value) {
    value.bind(function (to) {
      each('.credits-blog', function (el) {
        el.innerHTML = to;
      });
    });
  });

  // Credit Control
  wp.customize('allium_credit', function (value) {
    value.bind(function (to) {
      each('.credits-designer', function (el) {
        if (to === true) {
          el.style.clip = 'auto';
          el.style.position = 'relative';
        } else {
          el.style.clip = 'rect(1px, 1px, 1px, 1px)';
          el.style.position = 'absolute';
        }
      });
    });
  });
})();
