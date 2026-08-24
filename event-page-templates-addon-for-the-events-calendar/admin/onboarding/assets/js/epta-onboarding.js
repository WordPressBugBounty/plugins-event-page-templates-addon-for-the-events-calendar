/* =========================================================================
   Event Single Page Builder — onboarding wizard page behaviour.

   Extracted from the inline <script> blocks of wizard-single-page-builder.html.
   Loads AFTER assets/epta-wizard.js. Two things intentionally stay inline in
   the HTML (they have pre-run requirements):
     1. window.EPTA_WIZARD  — epta-wizard.js exits early unless it exists at
        load time.
     2. The localStorage bootstrap IIFE — restores body attributes + Step 1
        selection before wizard.js boots (prevents a flash of the default
        Classic template on hard reload of Step 2 or 3).
   ========================================================================= */

/* -------------------------------------------------------------------------
   CONFIG — single source of truth for every external URL, video id, and UTM
   string this file uses. Change values here, not in the code below.

   UTM scheme (owner spec — applies to every outbound eventscalendaraddons.com
   link; wordpress.org links never get UTM parameters):
     utm_source   = code of the HOST plugin (this wizard: epta_plugin), even
                    when the link points at a sibling addon page
     utm_medium   = inside
     utm_campaign = destination: demo | get_pro | docs | get_bundle
                    ("policy" is used for the non-ECA coolplugins.net
                    usage-tracking page, which the scheme does not govern)
     utm_content  = onboarding_step{N} — the wizard step hosting the link
                    (template = step1, target = step2, success = step3)
   ------------------------------------------------------------------------- */
window.EPTA_ONBOARDING_CONFIG = {
  // --- UTM ---------------------------------------------------------------
  UTM_SOURCE: 'epta_plugin',
  UTM_MEDIUM: 'inside',
  UTM_CONTENT_PREFIX: 'onboarding_step',
  // Hosts that receive UTM parameters. wordpress.org is deliberately absent.
  UTM_HOSTS: ['eventscalendaraddons.com', 'coolplugins.net'],

  // --- Videos ------------------------------------------------------------
  // Walkthrough video for Event Single Page Builder. Must stay in sync with
  // the data-youtube-id attribute on the Step 3 video element in the HTML.
  VIDEO_WALKTHROUGH_ID: '50FBrcqoB-M',
  YOUTUBE_THUMB_BASE: 'https://img.youtube.com/vi/',
  YOUTUBE_EMBED_BASE: 'https://www.youtube.com/embed/'
};

// Shared helpers — same __ECT / __ECTBE pattern used by the other wizards,
// prefix-swapped for this plugin.
window.__EPTA = {
  TEMPLATE_LABEL: {
    classic:   'Template 1',
    hero:      'Template 2',
    split:     'Template 3',
    elementor: 'Elementor Template',
    divi:      'Divi Template'
  },

  // ---------------- UTM appender ---------------------------------------
  // Walks every outgoing link to eventscalendaraddons.com / coolplugins.net
  // and appends utm_source / utm_medium / utm_campaign / utm_content per the
  // scheme documented in EPTA_ONBOARDING_CONFIG above. Explicit override via
  // data-utm="{campaign}" (legacy per-template values like "demo_classic"
  // normalize to the canonical "demo" campaign).
  applyUtm: function () {
    var CFG = window.EPTA_ONBOARDING_CONFIG;

    function isTarget(url) {
      return CFG.UTM_HOSTS.some(function (h) { return url.indexOf(h) !== -1; });
    }

    // utm_content = onboarding_step{N}, derived from the wizard step that
    // hosts the link (template → step1, target → step2, success → step3).
    function detectContent(el) {
      var stepEl = el && el.closest ? el.closest('.epta-wizard-step') : null;
      var stepId = stepEl ? stepEl.getAttribute('data-step') : null;
      var steps = (window.EPTA_WIZARD && window.EPTA_WIZARD.steps) || [];
      var idx = -1;
      for (var i = 0; i < steps.length; i++) {
        if (steps[i].id === stepId) { idx = i; break; }
      }
      return CFG.UTM_CONTENT_PREFIX + (idx === -1 ? 1 : idx + 1);
    }

    // Campaigns are constrained to the owner scheme: demo | get_pro | docs |
    // get_bundle (+ "policy" for the non-ECA coolplugins.net page).
    function normalizeCampaign(raw) {
      return raw && raw.indexOf('demo') === 0 ? 'demo' : raw;
    }
    function detectCampaign(url, el) {
      if (el && el.dataset.utm) return normalizeCampaign(el.dataset.utm);
      // Live demo pages: /demos/{slug}/, /event/{slug}/, /divi/{slug}/
      if (/eventscalendaraddons\.com\/(demos|event|divi)\//.test(url)) return 'demo';
      if (/coolplugins\.net\/terms\/usage-tracking/.test(url)) return 'policy';
      if (/eventscalendaraddons\.com\/docs\//.test(url)) return 'docs';
      if (/eventscalendaraddons\.com\/pricing/.test(url)) return 'get_bundle';
      // Any /plugin/... landing page counts as a Pro CTA.
      if (/eventscalendaraddons\.com\/(get-?pro|plugin\/)/.test(url)) return 'get_pro';
      return null;
    }
    function append(url, campaign, content) {
      if (!url || !campaign || url.indexOf('utm_source=') !== -1) return url;
      var params = { utm_source: CFG.UTM_SOURCE, utm_medium: CFG.UTM_MEDIUM,
                     utm_campaign: campaign, utm_content: content };
      var qs = Object.keys(params).map(function (k) {
        return k + '=' + encodeURIComponent(params[k]);
      }).join('&');
      var sep = url.indexOf('?') === -1 ? '?' : '&';
      var hashIdx = url.indexOf('#');
      if (hashIdx === -1) return url + sep + qs;
      return url.slice(0, hashIdx) + sep + qs + url.slice(hashIdx);
    }

    document.querySelectorAll('a[href]').forEach(function (a) {
      var href = a.getAttribute('href');
      if (!href || !isTarget(href)) return;
      var campaign = detectCampaign(href, a);
      if (campaign) a.setAttribute('href', append(href, campaign, detectContent(a)));
    });
  }
};

if (document.readyState !== 'loading') window.__EPTA.applyUtm();
else document.addEventListener('DOMContentLoaded', window.__EPTA.applyUtm);

/* -------------------------------------------------------------------------
   Step 1 behaviour: template selection, Pro CTA swap, color live-paint.
   ------------------------------------------------------------------------- */
(function () {
  var stepEl = document.querySelector('.epta-wizard-step[data-step="template"]');
  if (!stepEl) return;
  var picker = stepEl.querySelector('[data-tpl-picker]');
  if (!picker) return;

  // --- Nav-mode swap ------------------------------------------------------
  // Free templates → Continue button; SPB Pro (Hero/Split/Elementor) →
  // orange Upgrade/Activate Pro; Divi → orange Get Divi Modules Pro.
  function setNavMode(tier) {
    var navRight = stepEl.querySelector('[data-nav-right]');
    if (!navRight) return;
    var mode = tier === 'pro-same' ? 'upgrade-spb'
             : tier === 'pro-divi' ? 'upgrade-divi'
             : 'default';
    navRight.setAttribute('data-nav-mode', mode);
    syncSpbProCta(tier === 'pro-same');
    syncDiviCta(tier === 'pro-divi');
  }

  function syncSpbProCta(isProSame) {
    var cfg = window.EPTA_ONBOARDING || {};
    var upgrade = document.querySelector('[data-spb-upgrade]');
    var activate = document.querySelector('[data-spb-activate-pro]');
    var ownedNote = document.querySelector('[data-spb-owned-note]');
    var label = document.querySelector('[data-spb-activate-label]');
    var proReady = cfg.proStatus === 'inactive' || cfg.proStatus === 'active';

    if (!isProSame) {
      if (upgrade) upgrade.hidden = false;
      if (activate) activate.hidden = true;
      if (ownedNote) ownedNote.hidden = true;
      return;
    }

    if (proReady) {
      if (upgrade) upgrade.hidden = true;
      if (activate) {
        activate.hidden = false;
        if (label && cfg.i18n && cfg.i18n.activatePro) {
          label.textContent = cfg.i18n.activatePro;
        }
      }
      if (ownedNote) ownedNote.hidden = false;
    } else {
      if (upgrade) upgrade.hidden = false;
      if (activate) activate.hidden = true;
      if (ownedNote) ownedNote.hidden = true;
    }
  }

  function syncDiviCta(isDivi) {
    var cfg = window.EPTA_ONBOARDING || {};
    var upgrade = document.querySelector('[data-divi-upgrade]');
    var activate = document.querySelector('[data-divi-activate]');
    var ownedNote = document.querySelector('[data-divi-owned-note]');
    var label = document.querySelector('[data-divi-activate-label]');
    var proReady = cfg.diviProStatus === 'inactive' || cfg.diviProStatus === 'active';
    var freeReady = cfg.diviFreeStatus === 'inactive' || cfg.diviFreeStatus === 'active';
    var ready = proReady || freeReady;

    if (!isDivi) {
      if (upgrade) upgrade.hidden = false;
      if (activate) activate.hidden = true;
      if (ownedNote) ownedNote.hidden = true;
      return;
    }

    if (ready) {
      if (upgrade) upgrade.hidden = true;
      if (activate) {
        activate.hidden = false;
        if (label && cfg.i18n) {
          label.textContent = proReady
            ? (cfg.i18n.activateDiviPro || cfg.i18n.activatePro || 'Activate Pro & Continue')
            : (cfg.i18n.activateDiviFree || 'Activate & Continue');
        }
      }
      if (ownedNote) ownedNote.hidden = false;
    } else {
      if (upgrade) upgrade.hidden = false;
      if (activate) activate.hidden = true;
      if (ownedNote) ownedNote.hidden = true;
    }
  }

  function readSelectedColors() {
    var colors = {};
    ['primary', 'alternate', 'secondary'].forEach(function (key) {
      var swatch = picker.querySelector('[data-color-input="' + key + '"]');
      if (swatch && swatch.value) colors[key] = swatch.value;
    });
    return colors;
  }

  function postActivateHandoff(btn, opts) {
    var cfg = window.EPTA_ONBOARDING || {};
    if (!cfg.ajaxUrl || !opts.action) return;

    var prevHtml = btn.innerHTML;
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    btn.innerHTML = '<span class="dashicons dashicons-update" aria-hidden="true"></span> <span>' +
      (opts.busyLabel || 'Activating…') + '</span>';

    var fd = new FormData();
    fd.append('action', opts.action);
    fd.append('nonce', cfg.nonce || '');
    if (opts.template) fd.append('template', opts.template);
    if (opts.colors) fd.append('colors', JSON.stringify(opts.colors));

    fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success || !res.data || !res.data.redirectUrl) {
          throw new Error((res && res.data && res.data.message) || opts.errorLabel || 'Activation failed.');
        }
        window.location.href = res.data.redirectUrl;
      })
      .catch(function (err) {
        window.console && window.console.error && window.console.error(err);
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        btn.innerHTML = prevHtml;
        window.alert((err && err.message) || opts.errorLabel || 'Activation failed.');
      });
  }

  function activateProAndHandoff(btn) {
    var cfg = window.EPTA_ONBOARDING || {};
    var selected = stepEl.querySelector('.epta-template-card.is-selected');
    var template = (selected && selected.dataset.value) || document.body.getAttribute('data-selected-template') || '';
    if (['hero', 'split', 'elementor'].indexOf(template) === -1) return;

    postActivateHandoff(btn, {
      action: cfg.activateProAction,
      template: template,
      colors: readSelectedColors(),
      busyLabel: (cfg.i18n && cfg.i18n.activatingPro) || 'Activating Pro…',
      errorLabel: (cfg.i18n && cfg.i18n.activateProError) || 'Activation failed.'
    });
  }

  function activateDiviAndHandoff(btn) {
    var cfg = window.EPTA_ONBOARDING || {};
    postActivateHandoff(btn, {
      action: cfg.activateDiviAction,
      busyLabel: (cfg.i18n && cfg.i18n.activatingDivi) || 'Activating Divi Modules…',
      errorLabel: (cfg.i18n && cfg.i18n.activateDiviError) || 'Activation failed.'
    });
  }

  // --- Reflect selection into body attr + trigger side effects -----------
  var TPL_LABELS = { classic: 'Template 1', hero: 'Template 2', split: 'Template 3', elementor: 'Elementor Template', divi: 'Divi Template' };

  function applySelection(card) {
    if (!card) return;
    stepEl.querySelectorAll('.epta-template-card').forEach(function (c) { c.classList.remove('is-selected'); });
    card.classList.add('is-selected');
    var value = card.dataset.value;
    var tier  = card.dataset.tier;
    document.body.setAttribute('data-selected-template', value);
    setNavMode(tier);

    // Sync the SPB promo description with the selected template's label
    // so the CTA reads e.g. "Unlock the Split template plus every other..."
    var nameEl = document.querySelector('[data-promo-tpl-name]');
    if (nameEl && TPL_LABELS[value]) nameEl.textContent = TPL_LABELS[value];

    // Persist selection to state (best-effort; wizard.js also writes on
    // step change, but persisting here means colors + template survive
    // an F5 that happens before Next is clicked).
    try {
      var slug = window.EPTA_WIZARD.slug;
      var key  = 'epta:wizard:' + slug + ':state';
      var s    = JSON.parse(localStorage.getItem(key) || '{}');
      s.selections = s.selections || {};
      s.selections.template = value;
      localStorage.setItem(key, JSON.stringify(s));
    } catch (_) {}
  }

  // Click delegation on the picker — but let demo-icon anchors follow
  // their own href.
  picker.addEventListener('click', function (e) {
    if (e.target.closest('.epta-template-card__demo')) return;
    var card = e.target.closest('.epta-template-card');
    if (!card) return;
    e.preventDefault();
    applySelection(card);
  });

  // Keyboard support — cards are <div role="button">, so we handle
  // Enter and Space ourselves. Demo-icon clicks are still handled by
  // the native anchor's own keyboard behaviour.
  picker.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    if (e.target.closest('.epta-template-card__demo')) return;
    var card = e.target.closest('.epta-template-card');
    if (!card) return;
    e.preventDefault();
    applySelection(card);
  });

  // --- Color live-paint --------------------------------------------------
  function paint(key, value) {
    if (!value) return;
    var cssVar = key === 'primary'   ? '--epta-tpl-primary'
               : key === 'alternate' ? '--epta-tpl-alt'
               : '--epta-tpl-secondary';
    picker.style.setProperty(cssVar, value);
    var swatch = picker.querySelector('[data-color-input="' + key + '"]');
    var hex    = picker.querySelector('[data-color-hex="' + key + '"]');
    if (swatch && swatch.value.toLowerCase() !== value.toLowerCase()) swatch.value = value;
    if (hex    && hex.value.toLowerCase()    !== value.toLowerCase())    hex.value    = value;

    try {
      var slug = window.EPTA_WIZARD.slug;
      var storageKey = 'epta:wizard:' + slug + ':state';
      var s = JSON.parse(localStorage.getItem(storageKey) || '{}');
      s.selections = s.selections || {};
      s.selections.colors = s.selections.colors || {};
      s.selections.colors[key] = value;
      localStorage.setItem(storageKey, JSON.stringify(s));
    } catch (_) {}
  }
  picker.querySelectorAll('[data-color-input]').forEach(function (input) {
    input.addEventListener('input', function () { paint(this.dataset.colorInput, this.value); });
  });
  picker.querySelectorAll('[data-color-hex]').forEach(function (input) {
    input.addEventListener('change', function () {
      var v = (this.value || '').trim();
      if (!/^#([0-9a-fA-F]{6})$/.test(v)) {
        // Revert if not a valid 6-digit hex
        var key = this.dataset.colorHex;
        var swatch = picker.querySelector('[data-color-input="' + key + '"]');
        if (swatch) this.value = swatch.value;
        return;
      }
      paint(this.dataset.colorHex, v);
    });
  });

  // Set initial nav mode from the currently-selected card.
  var initial = stepEl.querySelector('.epta-template-card.is-selected');
  if (initial) setNavMode(initial.dataset.tier);

  var activateProBtn = document.querySelector('[data-spb-activate-pro]');
  if (activateProBtn) {
    activateProBtn.addEventListener('click', function (e) {
      e.preventDefault();
      activateProAndHandoff(activateProBtn);
    });
  }

  var activateDiviBtn = document.querySelector('[data-divi-activate]');
  if (activateDiviBtn) {
    activateDiviBtn.addEventListener('click', function (e) {
      e.preventDefault();
      activateDiviAndHandoff(activateDiviBtn);
    });
  }

  // "Continue with free template" — appears when a Pro template is selected.
  // Reverts the pick to Template 1 (the only free template) and advances.
  // Implemented as two synchronous clicks so wizard.js stays the single
  // source of truth: clicking the classic card updates wizard.js's own
  // state (template = classic) and flips the nav back to default; clicking
  // the now-enabled Next then advances the step normally.
  var continueFreeBtn = stepEl.querySelector('[data-continue-free]');
  if (continueFreeBtn) {
    continueFreeBtn.addEventListener('click', function (e) {
      e.preventDefault();
      var classicCard = picker.querySelector('.epta-template-card[data-value="classic"]');
      if (classicCard) classicCard.click();
      var nextBtn = stepEl.querySelector('[data-wizard-next][data-nav-variant="default"]');
      if (nextBtn) nextBtn.click();
    });
  }
})();

/* -------------------------------------------------------------------------
   Step 2 behaviour: target card selection, matching multiselect reveal,
   and Select2-style multiselect init (10 items preloaded per group, real
   WP integration will lazy-load the rest via AJAX search).
   ------------------------------------------------------------------------- */
(function () {
  var stepEl = document.querySelector('.epta-wizard-step[data-step="target"]');
  if (!stepEl) return;

  // --- Target-card single-select ---------------------------------------
  // Custom logic because the cards are <div> (not <a>), and we don't want
  // wizard.js's default is-selected toggle for these — they should behave
  // like radio buttons.
  function selectTargetCard(card) {
    stepEl.querySelectorAll('.epta-target-card').forEach(function (c) { c.classList.remove('is-selected'); });
    card.classList.add('is-selected');
    refreshTargetPicker();
    try {
      if (window.EPTAWizardAPI && typeof window.EPTAWizardAPI.getState === 'function') {
        var state = window.EPTAWizardAPI.getState();
        state.selections = state.selections || {};
        state.selections.target = card.dataset.value || 'all';
        window.EPTAWizardAPI.saveState();
      }
    } catch (_) {}
  }

  function refreshTargetPicker() {
    var selected = stepEl.querySelector('.epta-target-card.is-selected');
    var value = selected && selected.dataset.value;
    stepEl.querySelectorAll('[data-target-picker]').forEach(function (el) {
      el.hidden = (el.dataset.targetPicker !== value);
    });
  }

  stepEl.addEventListener('click', function (e) {
    var card = e.target.closest('.epta-target-card');
    if (!card) return;
    e.preventDefault();
    selectTargetCard(card);
  });
  stepEl.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var card = e.target.closest('.epta-target-card');
    if (!card) return;
    e.preventDefault();
    selectTargetCard(card);
  });

  // --- Select2-style multiselect init ----------------------------------
  // Ported from the Shortcodes & Blocks wizard for consistency. Chips
  // render inside the control; clicking an option toggles it; the hidden
  // input carries a CSV of selected values so wizard.js persistence works
  // with no extra plumbing. Chip removes fall back to empty (no options).
  function initMultiselect(root) {
    var control     = root.querySelector('[data-multiselect-control]');
    var dropdown    = root.querySelector('[data-multiselect-dropdown]');
    var chips       = root.querySelector('[data-multiselect-chips]');
    var placeholder = root.querySelector('[data-multiselect-placeholder]');
    var input       = root.querySelector('input[type="hidden"]');
    var options     = root.querySelectorAll('[data-multiselect-option]');
    var search      = root.querySelector('[data-multiselect-search]');
    var empty       = root.querySelector('[data-multiselect-empty]');

    function getValues() { return (input.value || '').split(',').filter(Boolean); }
    function setValues(vals) {
      input.value = vals.join(',');
      try {
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      } catch (_) {}
      render();
    }

    function render() {
      var vals = getValues();
      chips.innerHTML = '';
      vals.forEach(function (v) {
        var opt = root.querySelector('[data-multiselect-option][data-value="' + v.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]');
        var label = v;
        if (opt) {
          var labelEl = opt.querySelector('.epta-multiselect__label');
          if (labelEl) label = labelEl.textContent;
        }
        var chip = document.createElement('span');
        chip.className = 'epta-multiselect__chip';
        var text = document.createElement('span');
        text.textContent = label;
        chip.appendChild(text);
        var rm = document.createElement('span');
        rm.className = 'epta-multiselect__chip-remove';
        rm.setAttribute('role', 'button');
        rm.setAttribute('tabindex', '0');
        rm.setAttribute('data-multiselect-remove', v);
        rm.setAttribute('aria-label', 'Remove ' + label);
        rm.innerHTML = '<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>';
        chip.appendChild(rm);
        chips.appendChild(chip);
      });
      if (placeholder) placeholder.hidden = vals.length > 0;
      root.querySelectorAll('[data-multiselect-option]').forEach(function (opt) {
        opt.classList.toggle('is-selected', vals.indexOf(opt.dataset.value) >= 0);
      });
    }

    function toggleValue(value) {
      var vals = getValues();
      var idx = vals.indexOf(value);
      if (idx >= 0) vals.splice(idx, 1);
      else vals.push(value);
      setValues(vals);
    }

    function removeValue(value) {
      setValues(getValues().filter(function (v) { return v !== value; }));
    }

    function openDropdown()  {
      dropdown.hidden = false; root.classList.add('is-open'); control.setAttribute('aria-expanded', 'true');
      // Focus the search input as soon as the dropdown opens so a user
      // who clicks the field can start typing immediately (Select2-style).
      if (search) setTimeout(function () { search.focus(); }, 0);
    }
    function closeDropdown() {
      dropdown.hidden = true;  root.classList.remove('is-open'); control.setAttribute('aria-expanded', 'false');
      if (search) { search.value = ''; filterOptions(''); }
    }

    // Client-side filter over the pre-rendered options. On WP integration,
    // debounce and swap this for a wp_ajax call that returns the next
    // page of matching results — see the "WP integration" comment above
    // this block. `data-multiselect-search-input` custom event is fired
    // so PHP-side integrations can hook it without patching this file.
    function filterOptions(query) {
      var q = (query || '').trim().toLowerCase();
      var visibleCount = 0;
      root.querySelectorAll('[data-multiselect-option]').forEach(function (opt) {
        var labelEl = opt.querySelector('.epta-multiselect__label');
        var label = labelEl ? labelEl.textContent.toLowerCase() : '';
        var match = !q || label.indexOf(q) !== -1;
        opt.hidden = !match;
        if (match) visibleCount++;
      });
      if (empty) empty.classList.toggle('is-visible', visibleCount === 0 && q.length > 0);
    }
    if (search) {
      search.addEventListener('input', function () {
        filterOptions(this.value);
        // Give WP integrations a hook to fetch remote results on typing.
        root.dispatchEvent(new CustomEvent('epta:multiselect-search', {
          detail: { query: this.value, name: root.dataset.multiselectName || null },
          bubbles: true
        }));
      });
      // Prevent Enter in the search field from submitting a form or
      // closing the dropdown accidentally.
      search.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') e.preventDefault();
        if (e.key === 'Escape') { e.stopPropagation(); closeDropdown(); }
      });
    }

    control.addEventListener('click', function (e) {
      if (e.target.closest('[data-multiselect-remove]')) return;
      e.preventDefault();
      dropdown.hidden ? openDropdown() : closeDropdown();
    });
    control.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        dropdown.hidden ? openDropdown() : closeDropdown();
      } else if (e.key === 'Escape') closeDropdown();
    });
    dropdown.addEventListener('click', function (e) {
      var opt = e.target.closest('[data-multiselect-option]');
      if (!opt) return;
      e.preventDefault();
      toggleValue(opt.dataset.value);
    });
    chips.addEventListener('click', function (e) {
      var rm = e.target.closest('[data-multiselect-remove]');
      if (!rm) return;
      e.preventDefault();
      e.stopPropagation();
      removeValue(rm.dataset.multiselectRemove);
    });
    chips.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      var rm = e.target.closest('[data-multiselect-remove]');
      if (!rm) return;
      e.preventDefault();
      e.stopPropagation();
      removeValue(rm.dataset.multiselectRemove);
    });
    document.addEventListener('click', function (e) {
      if (!root.contains(e.target)) closeDropdown();
    });

    render();
  }

  stepEl.querySelectorAll('[data-multiselect]').forEach(initMultiselect);

  // Fire once when the step opens so persisted selections land in the
  // right visibility state.
  document.addEventListener('epta:wizard-step', function (e) {
    if (e.detail.stepId === 'target') requestAnimationFrame(refreshTargetPicker);
  });
})();

/* -------------------------------------------------------------------------
   Step 3 behaviour: dynamic title/lede + "no events" state + finish cleanup.
   ------------------------------------------------------------------------- */
(function () {
  var stepEl = document.querySelector('.epta-wizard-step[data-step="success"]');
  if (!stepEl) return;

  function readState() {
    var out = {};
    try {
      var raw = localStorage.getItem('epta:wizard:' + window.EPTA_WIZARD.slug + ':state');
      if (raw) {
        var s = JSON.parse(raw);
        if (s && s.selections) out = s.selections;
      }
    } catch (_) {}
    return out;
  }

  function scopeLabel(target) {
    if (!target || target === 'all') return 'all events';
    if (target === 'categories') return 'the selected categories';
    if (target === 'events')     return 'the selected events';
    return 'your events';
  }

  function refresh() {
    var state = readState();
    var tplLabel = window.__EPTA.TEMPLATE_LABEL[state.template] || 'Classic Sidebar';
    var scope    = scopeLabel(state.target);

    var t = stepEl.querySelector('[data-success-title]');
    var l = stepEl.querySelector('[data-success-lede]');
    if (t) t.textContent = 'Congrats! ' + tplLabel + ' is applied.';
    if (l) l.textContent = 'Your single event pages now use ' + tplLabel + ' on ' + scope + '. Watch the walkthrough or jump straight to a live preview.';

    // "No events" fallback — hide Preview Event, show the create card.
    var previewState = document.body.getAttribute('data-preview-state') || '';
    var noEvents = previewState === 'no-events';
    var previewBtn = stepEl.querySelector('[data-preview-event]');
    var noCard     = stepEl.querySelector('[data-no-events]');
    if (previewBtn) previewBtn.hidden = noEvents;
    if (noCard)     noCard.hidden     = !noEvents;
  }

  document.addEventListener('epta:wizard-step', function (e) {
    if (e.detail.stepId === 'success') requestAnimationFrame(refresh);
  });
  document.addEventListener('epta:preview-state', function () {
    if (stepEl.classList.contains('is-active')) refresh();
  });

  // ----- YouTube video preview (thumbnail + click-to-play) --------------
  // Only one video on this wizard so we hardcode the target element.
  var videoEl = stepEl.querySelector('[data-editor-video]');
  if (videoEl) {
    var CFG = window.EPTA_ONBOARDING_CONFIG;
    var ytId = videoEl.getAttribute('data-youtube-id');
    var frame = videoEl.querySelector('[data-video-frame]');

    // Preload the HD thumbnail; if it comes back tiny (YouTube returns a
    // 120x90 grey placeholder when maxres doesn't exist for that video),
    // fall back to hqdefault.
    if (ytId) {
      var maxres = CFG.YOUTUBE_THUMB_BASE + ytId + '/maxresdefault.jpg';
      var hq     = CFG.YOUTUBE_THUMB_BASE + ytId + '/hqdefault.jpg';
      var probe = new Image();
      probe.onload = function () {
        videoEl.style.backgroundImage = 'url("' + (this.naturalWidth < 200 ? hq : maxres) + '")';
      };
      probe.onerror = function () { videoEl.style.backgroundImage = 'url("' + hq + '")'; };
      probe.src = maxres;
      videoEl.style.backgroundImage = 'url("' + hq + '")';
    }

    // Whole thumbnail is click-to-play (matches the Widgets wizard UX).
    videoEl.addEventListener('click', function (e) {
      if (videoEl.classList.contains('is-playing')) return;
      e.preventDefault();
      if (!ytId || !frame) return;
      frame.innerHTML = '<iframe src="' + CFG.YOUTUBE_EMBED_BASE + ytId
        + '?autoplay=1&rel=0&modestbranding=1" title="Walkthrough video" '
        + 'frameborder="0" allow="accelerometer; autoplay; clipboard-write; '
        + 'encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
      videoEl.classList.add('is-playing');
    });
  }

  // Finish / Exit / Cancel all clear the persisted state and mark complete.
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-wizard-finish]')) return;
    var slug = window.EPTA_WIZARD.slug;
    try {
      localStorage.removeItem('epta:wizard:' + slug + ':state');
      localStorage.setItem('epta:wizard:' + slug + ':completed', '1');
    } catch (_) {}
  });
})();
