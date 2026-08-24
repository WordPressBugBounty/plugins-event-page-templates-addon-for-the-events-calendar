<?php
/**
 * Onboarding wizard markup — Event Single Page Builder.
 *
 * Expects variables from EPTA_Onboarding::render_page().
 *
 * @package EventPageTemplatesAddon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="epta-onboarding-root" class="epta-onboarding-wrap">
<script>document.body.setAttribute('data-preview-state', <?php echo wp_json_encode( $epta_preview_state ); ?>);</script>
<div class="epta-wizard-shell">

  <!-- ============================================================
       Header — brand (left) + progress steps (center) + Exit (right)
       ============================================================ -->
  <header class="epta-wizard-header">
    <div class="epta-wizard-header__brand">
      <img src="<?php echo esc_url( $epta_icons_url . 'event-single-page-icon.svg' ); ?>" alt="" class="epta-wizard-header__brand-icon">
      <span class="epta-wizard-header__brand-name">
        <strong>Event Single Page Builder</strong>
        <em>Get Started</em>
      </span>
    </div>

    <ol class="epta-wizard-header__steps" data-wizard-progress></ol>

    <a href="<?php echo esc_url( $epta_exit_url ); ?>" data-wizard-finish class="epta-wizard-header__exit">
      <span>Exit Setup</span>
      <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
    </a>
  </header>

  <main class="epta-wizard-main">
    <div class="epta-wizard-card">

      <!-- ============================================================
           STEP 1 — Template & Colors
           60/40 split. Left = 2-column grid of browser-frame template
           cards (3 free + up to 2 conditional Pro). Right = live color
           picker OR (Divi Pro only) a cross-sell promo panel.
           ============================================================ -->
      <section class="epta-wizard-step is-active" data-step="template">
        <header class="epta-wizard-card__heading">
          <h1 class="epta-wizard-card__title">Choose Your Single Event Page Template / Layout</h1>
          <p class="epta-wizard-card__desc">Pick a custom template to replace the default single event page from The Events Calendar.</p>
        </header>

        <div class="epta-template-picker" data-tpl-picker>

          <!-- LEFT: Template grid ------------------------------------
               No section heading here — the wizard `<h1>` above already
               names the task ("Pick a single page template"). Starting the
               grid immediately lets its top edge align with the Colors
               panel on the right for a cleaner two-column composition. -->
          <div class="epta-template-picker__left">
            <div class="epta-template-grid" data-required-selection="template">

              <!-- Card 1: Classic Sidebar (FREE).
                   Outer element is a <div> (not <a>) so the inner demo <a>
                   inside the browser bar is valid — nested anchors are
                   invalid HTML and the browser auto-splits them. -->
              <div role="button" tabindex="0" class="epta-template-card is-selected" data-value="classic" data-tier="free">
                <div class="epta-template-card__bar">
                  <span class="epta-template-card__dots" aria-hidden="true"><span></span><span></span><span></span></span>
                  <a class="epta-template-card__demo" href="https://eventscalendaraddons.com/event/blood-donation-camp-in-delhi/"
                     target="_blank" rel="noopener" data-utm="demo_classic" aria-label="Open Classic Sidebar template demo"
                     onclick="event.stopPropagation();">
                    <span class="dashicons dashicons-external" aria-hidden="true"></span>
                    <span>View Demo</span>
                  </a>
                </div>
                <div class="epta-template-skeleton epta-tpl--classic">
                  <div class="epta-tpl-back">&lt;&lt; All Events</div>
                  <div class="epta-tpl-body">
                    <div class="epta-tpl-left">
                      <div class="epta-tpl-image epta-tpl-image--tall">
                        <div class="epta-tpl-title-overlay">
                          <span class="epta-tpl-line epta-tpl-line--w75"></span>
                          <span class="epta-tpl-line epta-tpl-line--w40 epta-tpl-line--sm"></span>
                        </div>
                      </div>
                      <div class="epta-tpl-block">
                        <span class="epta-tpl-line"></span>
                        <span class="epta-tpl-line epta-tpl-line--w75"></span>
                      </div>
                    </div>
                    <div class="epta-tpl-right">
                      <div class="epta-tpl-countdown"><span></span><span></span><span></span><span></span></div>
                      <div class="epta-tpl-sidebar-heading">Details</div>
                      <div class="epta-tpl-block">
                        <span class="epta-tpl-line epta-tpl-line--w75"></span>
                      </div>
                      <div class="epta-tpl-sidebar-heading">Venue</div>
                      <div class="epta-tpl-block">
                        <span class="epta-tpl-line epta-tpl-line--w75"></span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="epta-template-card__footer">
                  <span class="epta-template-card__label">
                    <span class="epta-template-card__name">Template 1</span>
                    <span class="epta-template-card__badge epta-template-card__badge--free">Free</span>
                  </span>
                  <span class="epta-template-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </div>
              </div>

              <!-- Card 2: Hero (SPB Pro) -->
              <div role="button" tabindex="0" class="epta-template-card" data-value="hero" data-tier="pro-same">
                <div class="epta-template-card__bar">
                  <span class="epta-template-card__dots" aria-hidden="true"><span></span><span></span><span></span></span>
                  <a class="epta-template-card__demo" href="https://eventscalendaraddons.com/event/yoga-girl-special-classes/"
                     target="_blank" rel="noopener" data-utm="demo_hero" aria-label="Open Hero template demo"
                     onclick="event.stopPropagation();">
                    <span class="dashicons dashicons-external" aria-hidden="true"></span>
                    <span>View Demo</span>
                  </a>
                </div>
                <div class="epta-template-skeleton epta-tpl--hero">
                  <div class="epta-tpl-back">&lt;&lt; All Events</div>
                  <div class="epta-tpl-body">
                    <div class="epta-tpl-image epta-tpl-image--hero"></div>
                    <div class="epta-tpl-countdown"><span></span><span></span><span></span><span></span></div>
                    <div class="epta-tpl-tags">
                      <span class="epta-tpl-tag"></span>
                      <span class="epta-tpl-tag"></span>
                    </div>
                    <div class="epta-tpl-block">
                      <span class="epta-tpl-line"></span>
                      <span class="epta-tpl-line epta-tpl-line--w75"></span>
                    </div>
                  </div>
                </div>
                <div class="epta-template-card__footer">
                  <span class="epta-template-card__label">
                    <span class="epta-template-card__name">Template 2</span>
                    <span class="epta-template-card__badge epta-template-card__badge--pro">Pro</span>
                  </span>
                  <span class="epta-template-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </div>
              </div>

              <!-- Card 3: Split (SPB Pro) -->
              <div role="button" tabindex="0" class="epta-template-card" data-value="split" data-tier="pro-same">
                <div class="epta-template-card__bar">
                  <span class="epta-template-card__dots" aria-hidden="true"><span></span><span></span><span></span></span>
                  <a class="epta-template-card__demo" href="https://eventscalendaraddons.com/event/womens-marathon-in-london/"
                     target="_blank" rel="noopener" data-utm="demo_split" aria-label="Open Split template demo"
                     onclick="event.stopPropagation();">
                    <span class="dashicons dashicons-external" aria-hidden="true"></span>
                    <span>View Demo</span>
                  </a>
                </div>
                <div class="epta-template-skeleton epta-tpl--split">
                  <div class="epta-tpl-back">&lt;&lt; All Events</div>
                  <div class="epta-tpl-body">
                    <div class="epta-tpl-image epta-tpl-image--hero"></div>
                    <div class="epta-tpl-split-row">
                      <div class="epta-tpl-organizer">
                        <span class="epta-tpl-sidebar-heading">Organizer</span>
                        <span class="epta-tpl-line epta-tpl-line--w75"></span>
                        <span class="epta-tpl-line epta-tpl-line--w60"></span>
                      </div>
                      <div>
                        <div class="epta-tpl-info-cards">
                          <div class="epta-tpl-info-card">
                            <span class="epta-tpl-line epta-tpl-line--sm epta-tpl-line--w40"></span>
                            <span class="epta-tpl-line epta-tpl-line--w60"></span>
                          </div>
                          <div class="epta-tpl-info-card">
                            <span class="epta-tpl-line epta-tpl-line--sm epta-tpl-line--w40"></span>
                            <span class="epta-tpl-line epta-tpl-line--w60"></span>
                          </div>
                          <div class="epta-tpl-info-card">
                            <span class="epta-tpl-line epta-tpl-line--sm epta-tpl-line--w40"></span>
                            <span class="epta-tpl-line epta-tpl-line--w60"></span>
                          </div>
                        </div>
                        <div class="epta-tpl-countdown"><span></span><span></span><span></span><span></span></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="epta-template-card__footer">
                  <span class="epta-template-card__label">
                    <span class="epta-template-card__name">Template 3</span>
                    <span class="epta-template-card__badge epta-template-card__badge--pro">Pro</span>
                  </span>
                  <span class="epta-template-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </div>
              </div>

              <!-- Card 4: Elementor template (SPB Pro, conditional) -->
              <div role="button" tabindex="0" class="epta-template-card" data-value="elementor" data-tier="pro-same"
                   data-conditional="elementor">
                <div class="epta-template-card__bar">
                  <span class="epta-template-card__dots" aria-hidden="true"><span></span><span></span><span></span></span>
                  <a class="epta-template-card__demo" href="https://eventscalendaraddons.com/demos/event-single-page-builder-pro/#elementor-template"
                     target="_blank" rel="noopener" data-utm="demo_elementor" aria-label="Open Elementor template demo"
                     onclick="event.stopPropagation();">
                    <span class="dashicons dashicons-external" aria-hidden="true"></span>
                    <span>View Demo</span>
                  </a>
                </div>
                <div class="epta-template-skeleton epta-tpl--builder">
                  <div class="epta-tpl-body">
                    <div class="epta-tpl-wordmark">
                      <img src="<?php echo esc_url( $epta_images_url . 'elementor-icon.png' ); ?>" alt="">
                      <span>Design in Elementor</span>
                    </div>
                  </div>
                </div>
                <div class="epta-template-card__footer">
                  <span class="epta-template-card__label">
                    <span class="epta-template-card__name">Elementor Template</span>
                    <span class="epta-template-card__badge epta-template-card__badge--pro">Pro</span>
                  </span>
                  <span class="epta-template-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </div>
              </div>

              <!-- Card 5: Divi template (separate plugin, conditional) -->
              <div role="button" tabindex="0" class="epta-template-card" data-value="divi" data-tier="pro-divi"
                   data-conditional="divi">
                <div class="epta-template-card__bar">
                  <span class="epta-template-card__dots" aria-hidden="true"><span></span><span></span><span></span></span>
                  <a class="epta-template-card__demo" href="https://eventscalendaraddons.com/demos/the-events-calendar-modules-for-divi/"
                     target="_blank" rel="noopener" data-utm="demo_divi" aria-label="Open Divi template demo"
                     onclick="event.stopPropagation();">
                    <span class="dashicons dashicons-external" aria-hidden="true"></span>
                    <span>View Demo</span>
                  </a>
                </div>
                <div class="epta-template-skeleton epta-tpl--builder">
                  <div class="epta-tpl-body">
                    <div class="epta-tpl-wordmark">
                      <span style="font-size: 1rem; letter-spacing: 0.15em;" aria-hidden="true">DIVI</span>
                      <span>Design in Divi</span>
                    </div>
                  </div>
                </div>
                <div class="epta-template-card__footer">
                  <span class="epta-template-card__label">
                    <span class="epta-template-card__name">Divi Template</span>
                    <span class="epta-template-card__badge epta-template-card__badge--pro">Pro</span>
                  </span>
                  <span class="epta-template-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </div>
              </div>

            </div>
          </div>

          <!-- RIGHT: Colors panel (default) OR Divi cross-sell (Divi selected) -->
          <div class="epta-template-picker__right">

            <div class="epta-color-panel" data-tpl-panel="colors">
              <div>
                <h2 class="epta-color-panel__title">Template colors</h2>
                <p class="epta-color-panel__desc">Select template colors that match your website design.</p>
              </div>

              <div class="epta-color-row">
                <input type="color" class="epta-color-row__swatch" data-color-input="primary" value="#020E21" aria-label="Primary color">
                <div class="epta-color-row__meta">
                  <span class="epta-color-row__label">Primary</span>
                  <span class="epta-color-row__desc">Dark surfaces &amp; buttons.</span>
                </div>
                <input type="text" class="epta-color-row__hex" data-color-hex="primary" value="#020E21">
              </div>

              <div class="epta-color-row">
                <input type="color" class="epta-color-row__swatch" data-color-input="alternate" value="#FFFFFF" aria-label="Alternate color">
                <div class="epta-color-row__meta">
                  <span class="epta-color-row__label">Alternate</span>
                  <span class="epta-color-row__desc">Text/icons on top of primary surfaces.</span>
                </div>
                <input type="text" class="epta-color-row__hex" data-color-hex="alternate" value="#FFFFFF">
              </div>

              <div class="epta-color-row">
                <input type="color" class="epta-color-row__swatch" data-color-input="secondary" value="#EDEEF0" aria-label="Secondary color">
                <div class="epta-color-row__meta">
                  <span class="epta-color-row__label">Secondary</span>
                  <span class="epta-color-row__desc">Sidebar &amp; muted card backgrounds.</span>
                </div>
                <input type="text" class="epta-color-row__hex" data-color-hex="secondary" value="#EDEEF0">
              </div>
            </div>

            <!-- Promo panel — swaps content based on which Pro template is
                 selected. Two mutually-exclusive bodies live in here:
                   [data-promo-for="spb"]  → Hero, Split, and Elementor (all
                                             ship in Event Single Page Builder Pro)
                   [data-promo-for="divi"] → Divi (separate plugin — Events
                                             Calendar Modules for Divi Pro)
                 CSS shows only the body matching body[data-selected-template]. -->
            <div class="epta-template-promo" data-tpl-panel="promo">

              <div data-promo-for="spb">
                <span class="epta-template-promo__kicker">This template requires Pro</span>
                <div class="epta-template-promo__head">
                  <span class="epta-template-promo__icon" aria-hidden="true">
                    <img src="<?php echo esc_url( $epta_icons_url . 'event-single-page-icon.svg' ); ?>" alt="">
                  </span>
                  <h3 class="epta-template-promo__title">Event Single Page Builder Pro</h3>
                </div>
                <p class="epta-template-promo__desc">Unlock <strong data-promo-tpl-name>Template 2</strong> plus every other premium template &mdash; Template 3, the Elementor Template, and more. All from one plugin.</p>
                <!-- Buy link when Pro is not installed; Activate when Pro is on disk but inactive. -->
                <a href="https://eventscalendaraddons.com/plugin/event-single-page-builder-pro/" target="_blank" rel="noopener" class="epta-btn-accent" data-spb-upgrade>
                  <span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
                  <span>Upgrade to Pro</span>
                </a>
                <button type="button" class="epta-btn-accent" data-spb-activate-pro hidden>
                  <span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
                  <span data-spb-activate-label>Activate Pro &amp; Continue</span>
                </button>
                <p class="epta-template-promo__owned-note" data-spb-owned-note hidden>
                  <?php echo esc_html__( 'Pro is already installed on this site — activate it to use this template.', 'event-page-templates-addon-for-the-events-calendar' ); ?>
                </p>
              </div>

              <div data-promo-for="divi">
                <span class="epta-template-promo__kicker">Different addon required</span>
                <div class="epta-template-promo__head">
                  <span class="epta-template-promo__icon" aria-hidden="true">
                    <img src="<?php echo esc_url( $epta_icons_url . 'events-calendar-modules-for-divi.svg' ); ?>" alt="">
                  </span>
                  <h3 class="epta-template-promo__title">Events Calendar Modules for Divi Pro</h3>
                </div>
                <p class="epta-template-promo__desc">Divi single event templates ship in a dedicated sibling addon so you get the full Divi Builder experience &mdash; native modules, dynamic content, and Divi&rsquo;s own styling system.</p>
                <a href="https://eventscalendaraddons.com/plugin/the-events-calendar-modules-for-divi/" target="_blank" rel="noopener" class="epta-btn-accent" data-divi-upgrade>
                  <span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
                  <span>Get Divi Modules Pro</span>
                </a>
                <button type="button" class="epta-btn-accent" data-divi-activate hidden>
                  <span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
                  <span data-divi-activate-label>Activate Pro &amp; Continue</span>
                </button>
                <p class="epta-template-promo__owned-note" data-divi-owned-note hidden>
                  <?php echo esc_html__( 'Divi Modules is already installed — activate it to open Get Started.', 'event-page-templates-addon-for-the-events-calendar' ); ?>
                </p>
              </div>

            </div>

          </div>
        </div>

        <!-- Step 1 footer nav.
             - Free template (Template 1) selected → primary "Continue".
             - A Pro template (2/3, Elementor or Divi) selected → the primary
               Continue is hidden (you can't proceed with a Pro template on
               the free plugin) and a secondary "Continue with free template"
               takes its place, which reverts the pick to Template 1 and
               advances. The Pro upgrade CTA itself lives in the right-hand
               promo panel, so it isn't duplicated here.
             No Back button — this is the first step; Exit Setup (header)
             covers cancelling. -->
        <div class="epta-wizard-card__nav epta-wizard-card__nav--end">
          <div class="epta-wizard-card__nav-right" data-nav-right data-nav-mode="default">
            <a href="#" role="button" class="epta-btn-primary" data-wizard-next data-nav-variant="default">
              <span data-wizard-next-label>Continue</span>
              <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
            </a>
            <a href="#" role="button" class="epta-btn-secondary" data-nav-variant="continue-free" data-continue-free>
              <span>Continue with free template</span>
              <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
            </a>
          </div>
        </div>
      </section>

      <!-- ============================================================
           STEP 2 — Where to apply + Date format + Pro upsell row
           ============================================================ -->
      <section class="epta-wizard-step" data-step="target">
        <header class="epta-wizard-card__heading">
          <h1 class="epta-wizard-card__title">Where should this template apply?</h1>
          <p class="epta-wizard-card__desc">Pick which events use your template and choose a date format.</p>
        </header>

        <div class="epta-settings-list">

          <!-- ROW 1 — Apply template to. 4 compact cards in a single row:
               text on the left, radio dot on the right, no icon. When
               "Specific categories/events/tags" is selected, the matching
               Select2-style multiselect appears below. -->
          <div class="epta-settings-row" data-section="target">
            <div class="epta-settings-row__info">
              <span class="epta-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-location"></span>
              </span>
              <div class="epta-settings-row__meta">
                <h3 class="epta-settings-row__title">Apply template to</h3>
                <p class="epta-settings-row__desc">Scope where your template shows up.</p>
              </div>
            </div>
            <div class="epta-settings-row__body">
              <div class="epta-target-grid" data-required-selection="target">
                <div role="button" tabindex="0" class="epta-target-card is-selected" data-value="all">
                  <span class="epta-target-card__name">All events</span>
                  <span class="epta-target-card__radio" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </div>
                <div role="button" tabindex="0" class="epta-target-card" data-value="categories">
                  <span class="epta-target-card__name">Specific categories</span>
                  <span class="epta-target-card__radio" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </div>
                <div role="button" tabindex="0" class="epta-target-card" data-value="events">
                  <span class="epta-target-card__name">Specific events</span>
                  <span class="epta-target-card__radio" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </div>
              </div>

              <!-- Select2-style multiselect slots. Only the one matching the
                   selected target card is shown. On WP port: replace the
                   static <a>-based options with server-rendered options from
                   the site's Events categories / posts. First 10
                   render inline; the rest load on typed-search via AJAX. -->
              <div class="epta-target-picker" data-target-picker="categories" hidden>
                <div class="epta-multiselect" data-multiselect data-multiselect-name="target-category">
                  <div class="epta-multiselect__control" data-multiselect-control tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                    <span class="epta-multiselect__chips" data-multiselect-chips></span>
                    <span class="epta-multiselect__placeholder" data-multiselect-placeholder>Search categories&hellip;</span>
                    <span class="epta-multiselect__caret" aria-hidden="true"><span class="dashicons dashicons-arrow-down-alt2"></span></span>
                  </div>
                  <div class="epta-multiselect__dropdown" data-multiselect-dropdown role="listbox" hidden>
                    <div class="epta-multiselect__search">
                      <input type="search" class="epta-multiselect__search-input"
                             data-multiselect-search
                             placeholder="Type to search categories&hellip;"
                             aria-label="Search categories">
                    </div>
                    <div class="epta-multiselect__empty" data-multiselect-empty>No matches. Try a different search.</div>
<?php if ( empty( $epta_categories ) ) : ?>
                    <div class="epta-multiselect__empty is-visible"><?php esc_html_e( 'No event categories found.', 'event-page-templates-addon-for-the-events-calendar' ); ?></div>
<?php else : ?>
<?php foreach ( $epta_categories as $epta_opt_value => $epta_opt_label ) : ?>
                    <a href="#" role="option" class="epta-multiselect__option" data-value="<?php echo esc_attr( $epta_opt_value ); ?>" data-multiselect-option>
                      <span class="epta-multiselect__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                      <span class="epta-multiselect__label"><?php echo esc_html( $epta_opt_label ); ?></span>
                    </a>
<?php endforeach; ?>
<?php endif; ?>
                  </div>
                  <input type="hidden" data-wizard-input="target-category" value="">
                </div>
              </div>

              <div class="epta-target-picker" data-target-picker="events" hidden>
                <div class="epta-multiselect" data-multiselect data-multiselect-name="target-event">
                  <div class="epta-multiselect__control" data-multiselect-control tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                    <span class="epta-multiselect__chips" data-multiselect-chips></span>
                    <span class="epta-multiselect__placeholder" data-multiselect-placeholder>Search events&hellip;</span>
                    <span class="epta-multiselect__caret" aria-hidden="true"><span class="dashicons dashicons-arrow-down-alt2"></span></span>
                  </div>
                  <div class="epta-multiselect__dropdown" data-multiselect-dropdown role="listbox" hidden>
                    <div class="epta-multiselect__search">
                      <input type="search" class="epta-multiselect__search-input"
                             data-multiselect-search
                             placeholder="Type to search events&hellip;"
                             aria-label="Search events">
                    </div>
                    <div class="epta-multiselect__empty" data-multiselect-empty>No matches. Try a different search.</div>
<?php if ( empty( $epta_events ) ) : ?>
                    <div class="epta-multiselect__empty is-visible"><?php esc_html_e( 'No published events found.', 'event-page-templates-addon-for-the-events-calendar' ); ?></div>
<?php else : ?>
<?php foreach ( $epta_events as $epta_opt_value => $epta_opt_label ) : ?>
                    <a href="#" role="option" class="epta-multiselect__option" data-value="<?php echo esc_attr( $epta_opt_value ); ?>" data-multiselect-option>
                      <span class="epta-multiselect__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                      <span class="epta-multiselect__label"><?php echo esc_html( $epta_opt_label ); ?></span>
                    </a>
<?php endforeach; ?>
<?php endif; ?>
                  </div>
                  <input type="hidden" data-wizard-input="target-event" value="">
                </div>
              </div>

            </div>
          </div>

          <!-- ROW 2 — Date format -->
          <div class="epta-settings-row" data-section="date-format">
            <div class="epta-settings-row__info">
              <span class="epta-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-calendar"></span>
              </span>
              <div class="epta-settings-row__meta">
                <h3 class="epta-settings-row__title">Date format</h3>
                <p class="epta-settings-row__desc">How the event date renders in your template.</p>
              </div>
            </div>
            <div class="epta-settings-row__body">
              <select name="date-format" data-selection="date-format" class="epta-native-select">
                <option value="default" selected>default</option>
                <option value="DM">dM (01 Jan)</option>
                <option value="MD">MD (Jan 01)</option>
                <option value="FD">FD (January 01)</option>
                <option value="DF">DF (01 January)</option>
                <option value="FD,Y">FD,Y (January 01, 2019)</option>
                <option value="MD,Y">MD,Y (Jan 01, 2019)</option>
                <option value="MD,YT">MD,YT (Jan 01, 2019 8:00am-5:00pm)</option>
                <option value="full">full (01 January 2019 8:00am-5:00pm)</option>
                <option value="dFY">dFY (01 January 2019)</option>
                <option value="dMY">dMY (01 Jan 2019)</option>
              </select>
            </div>
          </div>

        </div>

        <!-- Telemetry consent — same structure and wording as the Shortcodes
             & Blocks and Events Widgets wizards for consistency across the
             addon family. Container is a <div> so only the checkbox area
             toggles state; clicking the description won't uncheck. -->
        <div class="epta-telemetry"<?php echo $epta_show_telemetry ? '' : ' hidden'; ?>>
          <label class="epta-telemetry__checkbox-wrap">
            <input type="checkbox" class="epta-telemetry__checkbox" data-wizard-telemetry checked>
            <span class="epta-telemetry__mark" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
          </label>
          <div class="epta-telemetry__body">
            <strong class="epta-telemetry__title">Help improve Events Calendar Addons</strong>
            <span class="epta-telemetry__desc">Share non-sensitive usage data &mdash; WP version, addon versions, active builder, theme name. No personal data or event content. Change anytime in Settings. <a class="epta-telemetry__policy" href="https://my.coolplugins.net/terms/usage-tracking/" target="_blank" rel="noopener">View policy<span class="dashicons dashicons-external" aria-hidden="true"></span></a></span>
          </div>
        </div>

        <div class="epta-wizard-card__nav">
          <a href="#" role="button" class="epta-btn-ghost" data-wizard-back>
            <span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
            <span>Back</span>
          </a>
          <div class="epta-wizard-card__nav-right" data-nav-right data-nav-mode="default">
            <a href="#" role="button" class="epta-btn-primary" data-wizard-next data-nav-variant="default">
              <span data-wizard-next-label>Apply Template</span>
              <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
            </a>
          </div>
        </div>
      </section>

      <!-- ============================================================
           STEP 3 — Done
           ============================================================ -->
      <section class="epta-wizard-step" data-step="success" data-always-valid="true">
        <div class="epta-wizard-success">
          <span class="epta-wizard-success__icon epta-wizard-success__icon--lg" aria-hidden="true">
            <span class="dashicons dashicons-yes"></span>
          </span>
          <h2 class="epta-wizard-success__title" data-success-title>Congrats! Template 1 is applied.</h2>
          <p class="epta-wizard-success__lede" data-success-lede>Your single event pages now use Template 1. Watch the walkthrough or jump straight to a live preview.</p>
        </div>

        <!-- YouTube-thumbnail video preview (click-to-play, whole thumbnail).
             Smaller frame here than in Step 1 of the other wizards because
             the Done page carries several elements below (actions + no-events
             fallback + bundle promo) and shouldn't push them below the fold. -->
        <div class="epta-editor-selector__video" data-editor-video data-editor="single-page" data-youtube-id="50FBrcqoB-M"
             style="max-width: 32rem; margin: 1.25rem auto;">
          <img src="<?php echo esc_url( $epta_icons_url . 'event-single-page-icon.svg' ); ?>" alt="" class="epta-editor-selector__video-watermark" data-editor-video-watermark>
          <span class="epta-editor-selector__video-waves" aria-hidden="true"></span>
          <a href="#" class="epta-editor-selector__video-play" aria-label="Play walkthrough video" data-editor-video-play></a>
          <span class="epta-editor-selector__video-label">
            <span class="dashicons dashicons-video-alt3" aria-hidden="true"></span>
            <span>Event Single Page Builder walkthrough</span>
          </span>
          <div class="epta-editor-selector__video-frame" data-video-frame></div>
        </div>

        <div class="epta-wizard-success__actions">
          <a href="<?php echo esc_url( $epta_settings_url ); ?>" class="epta-btn-secondary">
            <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
            <span>Edit Template Settings</span>
          </a>

          <!-- Preview Event — enabled when at least one event is published. -->
          <a href="<?php echo esc_url( $epta_preview_event_url ); ?>" target="_blank" rel="noopener" class="epta-btn-primary" data-preview-event>
            <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
            <span>Preview Your Event</span>
          </a>

          <a href="<?php echo esc_url( $epta_exit_url ); ?>" data-wizard-finish class="epta-btn-success">
            <span>Finish</span>
            <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
          </a>
        </div>

        <!-- No-events fallback — appears when wp_count_posts('tribe_events')
             returns 0. Simulated via preview-state "no-events". -->
        <div class="epta-cross-sell" data-no-events hidden style="border-color: var(--epta-border); background: #fff; margin-top: 1rem;">
          <span class="epta-cross-sell__icon epta-cross-sell__icon--chip" aria-hidden="true" style="color: var(--epta-primary); border-color: var(--epta-primary-border);">
            <span class="dashicons dashicons-info-outline"></span>
          </span>
          <div class="epta-cross-sell__body">
            <strong class="epta-cross-sell__title">No events yet</strong>
            <span class="epta-cross-sell__desc">Publish your first event to see the template live on the front-end.</span>
          </div>
          <div class="epta-cross-sell__actions">
            <a href="<?php echo esc_url( $epta_create_event_url ); ?>" class="epta-btn-primary">
              <span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
              <span>Create your first event</span>
            </a>
          </div>
        </div>

        <!-- Bundle promo — hide when every Pro addon is already installed. -->
<?php if ( ! empty( $epta_show_bundle ) ) : ?>
        <div class="epta-cross-sell epta-cross-sell--bundle" data-cross-sell="bundle">
          <span class="epta-cross-sell__icon epta-cross-sell__icon--chip" aria-hidden="true">
            <span class="dashicons dashicons-awards"></span>
          </span>
          <div class="epta-cross-sell__body">
            <strong class="epta-cross-sell__title">The Events Calendar Addons Bundle</strong>
            <span class="epta-cross-sell__desc">Get events calendar addons discounted bundle for your event website.</span>
          </div>
          <div class="epta-cross-sell__actions">
            <a href="https://eventscalendaraddons.com/pricing/" target="_blank" rel="noopener" class="epta-btn-accent" data-utm="get_bundle">
              <span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
              <span>View Addons Bundle</span>
            </a>
            <a href="<?php echo esc_url( $epta_exit_url ); ?>" data-wizard-finish class="epta-btn-ghost">Not now</a>
          </div>
        </div>
<?php endif; ?>
      </section>

    </div>
  </main>

</div>


</div>

<script>
  window.EPTA_WIZARD = {
    slug: 'single-page-builder',
    steps: [
      { id: 'template', label: 'Select Template' },
      { id: 'target',   label: 'Where to Apply?' },
      { id: 'success',  label: 'Done' }
    ],
    defaultTelemetry: true,
    nextToSuccessLabel: 'Apply Template',
    assetBase: <?php echo wp_json_encode( $epta_images_url ); ?>,
    siblingIconBase: <?php echo wp_json_encode( $epta_icons_url ); ?>,
    summaryLabels: {
      template: 'Template',
      target: 'Apply to',
      'date-format': 'Date format',
      'taxonomy-pages': 'Taxonomy pages'
    }
  };

  (function bootstrap() {
    var slug = window.EPTA_WIZARD.slug;
    var s = null;
    try {
      var raw = localStorage.getItem('epta:wizard:' + slug + ':state');
      if (raw) s = JSON.parse(raw);
    } catch (_) {}
    if (!s || !s.selections) return;

    var tpl = s.selections.template;
    if (tpl && tpl !== 'classic') {
      document.querySelectorAll('.epta-template-card').forEach(function (c) { c.classList.remove('is-selected'); });
      var match = document.querySelector('.epta-template-card[data-value="' + tpl + '"]');
      if (match) match.classList.add('is-selected');
    }
    if (tpl) document.body.setAttribute('data-selected-template', tpl);

    var colors = s.selections.colors;
    if (colors) {
      Object.keys(colors).forEach(function (key) {
        var swatch = document.querySelector('[data-color-input="' + key + '"]');
        var hex    = document.querySelector('[data-color-hex="' + key + '"]');
        if (swatch) swatch.value = colors[key];
        if (hex)    hex.value    = colors[key];
      });
      var picker = document.querySelector('[data-tpl-picker]');
      if (picker) {
        if (colors.primary)   picker.style.setProperty('--epta-tpl-primary',   colors.primary);
        if (colors.alternate) picker.style.setProperty('--epta-tpl-alt',       colors.alternate);
        if (colors.secondary) picker.style.setProperty('--epta-tpl-secondary', colors.secondary);
      }
    }
  })();
</script>
