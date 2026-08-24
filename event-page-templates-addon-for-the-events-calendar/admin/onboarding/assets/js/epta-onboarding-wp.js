/* =========================================================================
   WordPress bridge for the EPTA onboarding wizard.
   Loads AFTER epta-onboarding.js. Applies wizard selections via AJAX to the
   existing epta template post meta, wires multiselect search, and marks
   onboarding complete on Finish / Exit.
   ========================================================================= */
(function () {
  'use strict';

  var cfg = window.EPTA_ONBOARDING;
  if (!cfg || !cfg.ajaxUrl) return;

  function post(action, data) {
    var body = new window.FormData();
    body.append('action', action);
    body.append('nonce', cfg.nonce);
    Object.keys(data || {}).forEach(function (k) {
      body.append(k, data[k]);
    });
    return window.fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: body
    }).then(function (res) {
      return res.text().then(function (text) {
        try {
          return JSON.parse(text);
        } catch (e) {
          throw new Error((cfg.i18n && cfg.i18n.applyError) || 'Could not apply template settings. Please try again.');
        }
      });
    });
  }

  function readWizardState() {
    if (window.EPTAWizardAPI && typeof window.EPTAWizardAPI.getState === 'function') {
      return window.EPTAWizardAPI.getState();
    }
    try {
      var raw = localStorage.getItem('epta:wizard:single-page-builder:state');
      return raw ? JSON.parse(raw) : {};
    } catch (_) {
      return {};
    }
  }

  function getSelectedTarget() {
    var card = document.querySelector('.epta-wizard-step[data-step="target"] .epta-target-card.is-selected');
    if (card && card.dataset.value) return card.dataset.value;
    var state = readWizardState() || {};
    var target = (state.selections && state.selections.target) || 'all';
    return target === 'tags' ? 'all' : target;
  }

  function getMultiselectCsv(name) {
    var input = document.querySelector('input[data-wizard-input="' + name + '"]');
    return input ? (input.value || '').trim() : '';
  }

  function isTelemetryAccepted() {
    var telemetry = cfg.telemetry || {};
    if (telemetry.show === false) return true;

    var stepId = window.EPTAWizardAPI && typeof window.EPTAWizardAPI.getCurrentStepId === 'function'
      ? window.EPTAWizardAPI.getCurrentStepId()
      : null;

    // Only read the checkbox on the target step — it exists in the DOM (checked
    // by default) on earlier steps too and must not count as consent there.
    if (stepId !== 'target') {
      return false;
    }

    var box = document.querySelector('[data-wizard-telemetry]');
    if (box) return !!box.checked;

    var state = readWizardState() || {};
    return !!state.telemetryAccepted;
  }

  function collectPayload() {
    var state = readWizardState() || {};
    var selections = Object.assign({}, state.selections || {});

    // DOM is source of truth for target + pickers.
    selections.target = getSelectedTarget();

    var dateSel = document.querySelector('[data-selection="date-format"]');
    if (dateSel) selections['date-format'] = dateSel.value;

    selections['target-category'] = getMultiselectCsv('target-category');
    selections['target-event'] = getMultiselectCsv('target-event');
    selections.colors = selections.colors || {};
    ['primary', 'alternate', 'secondary'].forEach(function (key) {
      var swatch = document.querySelector('[data-color-input="' + key + '"]');
      if (swatch && swatch.value) selections.colors[key] = swatch.value;
    });

    return {
      selections: selections,
      telemetryAccepted: isTelemetryAccepted()
    };
  }

  function applyTelemetryFromPhp() {
    var telemetry = cfg.telemetry || {};
    var wrap = document.querySelector('.epta-telemetry');
    var box = document.querySelector('[data-wizard-telemetry]');
    var accepted = !!telemetry.checked;

    if (!telemetry.show) {
      if (wrap) wrap.hidden = true;
      accepted = true;
    } else {
      if (wrap) wrap.hidden = false;
      if (box) box.checked = accepted;
    }

    if (window.EPTAWizardAPI && typeof window.EPTAWizardAPI.getState === 'function') {
      var state = window.EPTAWizardAPI.getState();
      state.telemetryAccepted = accepted;
      if (typeof window.EPTAWizardAPI.saveState === 'function') {
        window.EPTAWizardAPI.saveState();
      }
    } else if (box) {
      box.checked = accepted;
    }
  }

  function validateTargetSelection(payload) {
    var target = payload.selections.target || 'all';
    if (target === 'categories' && !payload.selections['target-category']) {
      return (cfg.i18n && cfg.i18n.pickCategories) || 'Please select at least one category.';
    }
    if (target === 'events' && !payload.selections['target-event']) {
      return (cfg.i18n && cfg.i18n.pickEvents) || 'Please select at least one event.';
    }
    return '';
  }

  var applying = false;

  function setBusy(el, busy, label) {
    if (!el) return;
    if (busy) {
      if (!el._eptaHtml) el._eptaHtml = el.innerHTML;
      el.classList.add('is-busy');
      el.setAttribute('aria-busy', 'true');
      el.setAttribute('aria-disabled', 'true');
      el.setAttribute('data-epta-busy', '1');
      el.innerHTML = '<span class="dashicons dashicons-update" aria-hidden="true"></span> <span>' +
        (label || '') + '</span>';
    } else {
      el.classList.remove('is-busy');
      el.removeAttribute('aria-busy');
      el.removeAttribute('aria-disabled');
      el.removeAttribute('data-epta-busy');
      if (el._eptaHtml) {
        el.innerHTML = el._eptaHtml;
        el._eptaHtml = null;
      }
    }
  }

  function setFailed(el, message) {
    if (!el) return;
    el.classList.remove('is-busy');
    el.removeAttribute('aria-busy');
    el.removeAttribute('aria-disabled');
    el.removeAttribute('data-epta-busy');
    el._eptaHtml = null;
    el.innerHTML = '<span class="dashicons dashicons-warning" aria-hidden="true"></span> <span>' +
      (message || 'Failed — retry') + '</span>';
  }

  document.addEventListener('click', function (e) {
    var nextBtn = e.target.closest('[data-wizard-next]');
    if (!nextBtn || nextBtn.hasAttribute('disabled') || applying || nextBtn.getAttribute('data-epta-busy') === '1') return;

    var stepId = window.EPTAWizardAPI
      ? window.EPTAWizardAPI.getCurrentStepId()
      : null;
    if (stepId !== 'target') return;

    e.preventDefault();
    e.stopImmediatePropagation();

    if (window.EPTAWizardAPI && !window.EPTAWizardAPI.isValid('target')) return;

    var payload = collectPayload();
    var validationError = validateTargetSelection(payload);
    if (validationError) {
      window.alert(validationError);
      return;
    }

    applying = true;
    setBusy(nextBtn, true, (cfg.i18n && cfg.i18n.applying) || 'Applying…');

    post(cfg.applyAction, { payload: JSON.stringify(payload) })
      .then(function (json) {
        if (!json || !json.success) {
          var msg = (json && json.data && json.data.message) || 'apply failed';
          throw new Error(msg);
        }
        var data = json.data || {};
        if (data.settingsUrl) {
          document.querySelectorAll('a.epta-btn-secondary').forEach(function (a) {
            if (a.textContent && a.textContent.indexOf('Edit Template') !== -1) {
              a.setAttribute('href', data.settingsUrl);
            }
          });
        }
        if (data.previewUrl) {
          var preview = document.querySelector('[data-preview-event]');
          if (preview) preview.setAttribute('href', data.previewUrl);
        }
        if (data.telemetry) {
          cfg.telemetry = data.telemetry;
          applyTelemetryFromPhp();
        }
        setBusy(nextBtn, false);
        if (window.EPTAWizardAPI) window.EPTAWizardAPI.next();
      })
      .catch(function (err) {
        setFailed(nextBtn, (err && err.message) || (cfg.i18n && cfg.i18n.applyError) || 'Failed — retry');
      })
      .then(function () {
        applying = false;
      });
  }, true);

  document.addEventListener('click', function (e) {
    var finish = e.target.closest('[data-wizard-finish]');
    if (!finish || finish.getAttribute('data-epta-busy') === '1') return;
    document.body.classList.add('epta-finishing');
    setBusy(finish, true, (cfg.i18n && cfg.i18n.finishing) || 'Finishing…');
    post(cfg.completeAction, {}).catch(function () {
      document.body.classList.remove('epta-finishing');
      setBusy(finish, false);
    });
  });

  document.addEventListener('epta:wizard-step', function (e) {
    if (e.detail && e.detail.stepId === 'target') {
      applyTelemetryFromPhp();
    }
  });

  document.addEventListener('epta:multiselect-search', function (e) {
    var detail = e.detail || {};
    var query = (detail.query || '').trim();
    var name = detail.name || '';
    if (query.length < 2 || !name) return;

    var root = e.target.closest('[data-multiselect]');
    if (!root) return;

    var type = name === 'target-category' ? 'categories'
             : name === 'target-event'    ? 'events'
             : name;

    var hidden = root.querySelector('input[type="hidden"]');
    var selectedVals = hidden ? (hidden.value || '').split(',').filter(Boolean) : [];

    post(cfg.searchAction, { type: type, query: query }).then(function (json) {
      if (!json || !json.success || !json.data || !json.data.results) return;
      var dropdown = root.querySelector('[data-multiselect-dropdown]');
      if (!dropdown) return;

      var empty = root.querySelector('[data-multiselect-empty]');
      var searchWrap = root.querySelector('.epta-multiselect__search');
      var searchInput = searchWrap
        ? searchWrap.querySelector('[data-multiselect-search]')
        : null;
      // Moving/rebuilding dropdown nodes blurs the search field — restore after.
      var restoreFocus = searchInput && document.activeElement === searchInput;
      var caretStart = restoreFocus ? searchInput.selectionStart : null;
      var caretEnd = restoreFocus ? searchInput.selectionEnd : null;
      var keep = {};

      root.querySelectorAll('[data-multiselect-option]').forEach(function (opt) {
        if (selectedVals.indexOf(opt.dataset.value) >= 0) {
          keep[opt.dataset.value] = {
            value: opt.dataset.value,
            label: (opt.querySelector('.epta-multiselect__label') || {}).textContent || opt.dataset.value
          };
        }
        opt.remove();
      });

      var merged = [];
      Object.keys(keep).forEach(function (k) { merged.push(keep[k]); });
      json.data.results.forEach(function (item) {
        if (!keep[item.value]) merged.push(item);
      });

      merged.forEach(function (item) {
        var a = document.createElement('a');
        a.href = '#';
        a.setAttribute('role', 'option');
        a.className = 'epta-multiselect__option';
        a.dataset.value = item.value;
        a.setAttribute('data-multiselect-option', '');
        if (selectedVals.indexOf(item.value) >= 0) a.classList.add('is-selected');
        a.innerHTML =
          '<span class="epta-multiselect__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>' +
          '<span class="epta-multiselect__label"></span>';
        a.querySelector('.epta-multiselect__label').textContent = item.label;
        dropdown.appendChild(a);
      });

      if (empty) empty.classList.toggle('is-visible', merged.length === 0);
      if (searchWrap && searchWrap.parentNode === dropdown && dropdown.firstChild !== searchWrap) {
        dropdown.insertBefore(searchWrap, dropdown.firstChild);
      }
      if (empty && empty.parentNode === dropdown) {
        dropdown.insertBefore(empty, searchWrap ? searchWrap.nextSibling : dropdown.firstChild);
      }

      if (restoreFocus && searchInput) {
        searchInput.focus();
        if (caretStart != null && caretEnd != null) {
          try {
            searchInput.setSelectionRange(caretStart, caretEnd);
          } catch (_) {}
        }
      }
    });
  });

  document.addEventListener('DOMContentLoaded', function () {
    applyTelemetryFromPhp();

    if (typeof cfg.hasEvents === 'boolean' && !cfg.hasEvents) {
      document.body.setAttribute('data-preview-state', 'no-events');
    }
    if (cfg.previewUrl) {
      var preview = document.querySelector('[data-preview-event]');
      if (preview && cfg.previewUrl) preview.setAttribute('href', cfg.previewUrl);
    }

    var dateSel = document.querySelector('[data-selection="date-format"]');
    if (dateSel) {
      dateSel.addEventListener('change', function () {
        if (!window.EPTAWizardAPI) return;
        var state = window.EPTAWizardAPI.getState();
        state.selections = state.selections || {};
        state.selections['date-format'] = dateSel.value;
        window.EPTAWizardAPI.saveState();
      });
    }
  });
}());
