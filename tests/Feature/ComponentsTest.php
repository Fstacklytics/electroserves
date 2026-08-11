<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Design system component tests.
 *
 * Each component is rendered in isolation and asserted on its markup, so a
 * regression in a shared primitive is caught here rather than surfacing as a
 * subtle page-level failure.
 */
class ComponentsTest extends TestCase
{
    /**
     * Render a Blade string with the given data.
     *
     * @param  array<string, mixed>  $data
     */
    private function render(string $template, array $data = []): string
    {
        return (string) Blade::render($template, $data);
    }

    // =================================================================
    // Button
    // =================================================================

    public function test_button_renders_with_its_label(): void
    {
        $html = $this->render('<x-ui.button>Request a quote</x-ui.button>');

        $this->assertStringContainsString('Request a quote', $html);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('type="button"', $html);
    }

    public function test_button_renders_as_an_anchor_when_given_an_href(): void
    {
        $html = $this->render('<x-ui.button href="/contact">Contact</x-ui.button>');

        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('href="/contact"', $html);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function buttonVariants(): array
    {
        return [['primary'], ['secondary'], ['outline'], ['ghost'], ['danger'], ['white']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('buttonVariants')]
    public function test_button_renders_every_variant(string $variant): void
    {
        $html = $this->render('<x-ui.button :variant="$variant">Label</x-ui.button>', ['variant' => $variant]);

        $this->assertStringContainsString('Label', $html);
        // Every variant must provide a visible focus ring.
        $this->assertStringContainsString('focus-visible:ring-2', $html);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function buttonSizes(): array
    {
        return [['sm'], ['md'], ['lg']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('buttonSizes')]
    public function test_every_button_size_meets_the_minimum_touch_target(string $size): void
    {
        $html = $this->render('<x-ui.button :size="$size">Label</x-ui.button>', ['size' => $size]);

        // 44px minimum: min-h-touch (2.75rem) for sm/md, 3rem for lg.
        $this->assertTrue(
            str_contains($html, 'min-h-touch') || str_contains($html, 'min-h-[3rem]'),
            "Button size [{$size}] does not meet the 44px minimum touch target.",
        );
    }

    public function test_disabled_button_is_marked_up_as_disabled(): void
    {
        $html = $this->render('<x-ui.button disabled>Label</x-ui.button>');

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringContainsString('cursor-not-allowed', $html);
    }

    public function test_a_disabled_link_button_renders_as_a_button_so_it_leaves_the_tab_order(): void
    {
        $html = $this->render('<x-ui.button href="/contact" disabled>Label</x-ui.button>');

        // An <a> without href is not focusable; a disabled <button> is correct.
        $this->assertStringContainsString('<button', $html);
        $this->assertStringNotContainsString('href="/contact"', $html);
    }

    public function test_loading_button_announces_busy_and_shows_a_spinner(): void
    {
        $html = $this->render('<x-ui.button loading>Save</x-ui.button>');

        $this->assertStringContainsString('aria-busy="true"', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringContainsString('animate-spin', $html);
        // The resting label is replaced by the loading text.
        $this->assertStringContainsString(__('common.states.loading'), $html);
    }

    public function test_loading_button_can_use_a_custom_loading_label(): void
    {
        $html = $this->render('<x-ui.button loading loading-text="Sending…">Send</x-ui.button>');

        $this->assertStringContainsString('Sending…', $html);
    }

    // =================================================================
    // Form controls
    // =================================================================

    public function test_input_always_renders_an_associated_label(): void
    {
        $html = $this->render('<x-ui.input name="email" label="Email address" />');

        $this->assertMatchesRegularExpression('/<label for="([^"]+)"/', $html);

        preg_match('/<label for="([^"]+)"/', $html, $labelMatch);
        $this->assertStringContainsString('id="'.$labelMatch[1].'"', $html);
        $this->assertStringContainsString('Email address', $html);
    }

    public function test_required_input_is_marked_for_both_sighted_and_screen_reader_users(): void
    {
        $html = $this->render('<x-ui.input name="name" label="Full name" required />');

        $this->assertStringContainsString('required', $html);
        // The asterisk is decorative; the word "required" is announced.
        $this->assertStringContainsString('aria-hidden="true">*', $html);
        $this->assertStringContainsString('(required)', $html);
    }

    public function test_input_error_is_announced_via_aria(): void
    {
        $html = $this->render('<x-ui.input name="email" label="Email" error="That address is not valid." />');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('That address is not valid.', $html);

        // The message is linked to the input.
        preg_match('/aria-describedby="([^"]+)"/', $html, $describedBy);
        $this->assertNotEmpty($describedBy, 'Error message is not linked with aria-describedby.');
        $this->assertStringContainsString('id="'.trim($describedBy[1]).'"', $html);
    }

    public function test_input_help_text_is_linked_with_aria_describedby(): void
    {
        $html = $this->render('<x-ui.input name="phone" label="Phone" help-text="Optional." />');

        $this->assertStringContainsString('Optional.', $html);
        $this->assertStringContainsString('aria-describedby=', $html);
    }

    public function test_disabled_input_is_marked_up_correctly(): void
    {
        $html = $this->render('<x-ui.input name="locked" label="Locked" disabled />');

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
    }

    public function test_textarea_renders_a_label_and_its_value(): void
    {
        $html = $this->render('<x-ui.textarea name="message" label="Message" value="Hello" />');

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('Message', $html);
        $this->assertStringContainsString('Hello', $html);
        $this->assertMatchesRegularExpression('/<label for="[^"]+"/', $html);
    }

    public function test_textarea_error_state_is_accessible(): void
    {
        $html = $this->render('<x-ui.textarea name="message" label="Message" error="Required." />');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('Required.', $html);
    }

    public function test_select_renders_its_options_and_placeholder(): void
    {
        $html = $this->render(
            '<x-ui.select name="service" label="Service" :options="$options" placeholder="Choose one" />',
            ['options' => ['residential' => 'Residential', 'commercial' => 'Commercial']],
        );

        $this->assertStringContainsString('Choose one', $html);
        $this->assertStringContainsString('value="residential"', $html);
        $this->assertStringContainsString('Commercial', $html);
        $this->assertMatchesRegularExpression('/<label for="[^"]+"/', $html);
    }

    public function test_select_marks_the_current_value_as_selected(): void
    {
        $html = $this->render(
            '<x-ui.select name="service" label="Service" :options="$options" value="commercial" />',
            ['options' => ['residential' => 'Residential', 'commercial' => 'Commercial']],
        );

        $this->assertStringContainsString('value="commercial" selected', $html);
    }

    public function test_checkbox_renders_a_label_bound_to_the_input(): void
    {
        $html = $this->render('<x-ui.checkbox name="consent" label="I agree" required />');

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('I agree', $html);
        $this->assertStringContainsString('(required)', $html);

        preg_match('/<label\s+for="([^"]+)"/s', $html, $labelMatch);
        $this->assertNotEmpty($labelMatch);
        $this->assertStringContainsString('id="'.$labelMatch[1].'"', $html);
    }

    public function test_checkbox_error_state_is_accessible(): void
    {
        $html = $this->render('<x-ui.checkbox name="consent" label="I agree" error="You must agree." />');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('You must agree.', $html);
    }

    // =================================================================
    // Feedback components
    // =================================================================

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function alertTypes(): array
    {
        return [
            ['success', 'status'],
            ['info', 'status'],
            // Problems interrupt; confirmations wait their turn.
            ['warning', 'alert'],
            ['error', 'alert'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('alertTypes')]
    public function test_alert_uses_the_correct_aria_role_for_its_severity(string $type, string $role): void
    {
        $html = $this->render('<x-ui.alert :type="$type">Message body</x-ui.alert>', ['type' => $type]);

        $this->assertStringContainsString('role="'.$role.'"', $html);
        $this->assertStringContainsString('Message body', $html);
    }

    public function test_alert_renders_an_optional_title(): void
    {
        $html = $this->render('<x-ui.alert type="error" title="Could not send">Try again.</x-ui.alert>');

        $this->assertStringContainsString('Could not send', $html);
        $this->assertStringContainsString('Try again.', $html);
    }

    public function test_dismissible_alert_has_a_labelled_dismiss_control(): void
    {
        $html = $this->render('<x-ui.alert type="info" dismissible>Notice</x-ui.alert>');

        $this->assertStringContainsString('Dismiss', $html);
        $this->assertStringContainsString('x-data', $html);
    }

    public function test_spinner_announces_itself_to_assistive_technology(): void
    {
        $html = $this->render('<x-ui.spinner />');

        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString(__('common.states.loading'), $html);
        $this->assertStringContainsString('animate-spin', $html);
    }

    public function test_decorative_spinner_is_hidden_from_assistive_technology(): void
    {
        // Inside a button that already announces aria-busy, the spinner must
        // not duplicate the announcement.
        $html = $this->render('<x-ui.spinner :label="false" />');

        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringNotContainsString('role="status"', $html);
    }

    public function test_skeleton_marks_the_region_as_busy(): void
    {
        $html = $this->render('<x-ui.skeleton :lines="3" />');

        $this->assertStringContainsString('aria-busy="true"', $html);
        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('animate-pulse', $html);
        $this->assertSame(3, substr_count($html, 'animate-pulse'));
    }

    public function test_empty_state_renders_its_message_and_optional_action(): void
    {
        $html = $this->render(
            '<x-ui.empty-state title="Nothing here" message="No services yet." action-label="Contact us" :action-href="route(\'contact\')" />'
        );

        $this->assertStringContainsString('Nothing here', $html);
        $this->assertStringContainsString('No services yet.', $html);
        $this->assertStringContainsString('Contact us', $html);
        $this->assertStringContainsString(route('contact'), $html);
    }

    // =================================================================
    // Surfaces
    // =================================================================

    public function test_card_renders_header_body_and_footer_slots(): void
    {
        $html = $this->render(<<<'BLADE'
        <x-ui.card>
            <x-slot:header>Header content</x-slot:header>
            Body content
            <x-slot:footer>Footer content</x-slot:footer>
        </x-ui.card>
        BLADE);

        $this->assertStringContainsString('Header content', $html);
        $this->assertStringContainsString('Body content', $html);
        $this->assertStringContainsString('Footer content', $html);
    }

    public function test_hoverable_card_responds_to_focus_as_well_as_hover(): void
    {
        $html = $this->render('<x-ui.card hoverable>Body</x-ui.card>');

        $this->assertStringContainsString('hover:shadow-lg', $html);
        // Keyboard users must get the same affordance as mouse users.
        $this->assertStringContainsString('focus-within:shadow-lg', $html);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function badgeVariants(): array
    {
        return [['neutral'], ['primary'], ['secondary'], ['success'], ['warning'], ['danger']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badgeVariants')]
    public function test_badge_renders_every_variant(string $variant): void
    {
        $html = $this->render('<x-ui.badge :variant="$variant">Label</x-ui.badge>', ['variant' => $variant]);

        $this->assertStringContainsString('Label', $html);
    }

    public function test_star_rating_conveys_the_score_as_text_not_colour_alone(): void
    {
        $html = $this->render('<x-ui.star-rating :rating="4" />');

        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString(__('testimonials.rating_label', ['rating' => 4]), $html);
        // Five stars are always drawn; four are filled.
        $this->assertSame(5, substr_count($html, '<svg'));
    }

    public function test_star_rating_clamps_out_of_range_values(): void
    {
        $tooHigh = $this->render('<x-ui.star-rating :rating="99" />');
        $tooLow = $this->render('<x-ui.star-rating :rating="-3" />');

        $this->assertStringContainsString(__('testimonials.rating_label', ['rating' => 5]), $tooHigh);
        $this->assertStringContainsString(__('testimonials.rating_label', ['rating' => 0]), $tooLow);
    }

    // =================================================================
    // Media
    // =================================================================

    public function test_media_renders_a_placeholder_when_the_image_is_missing(): void
    {
        $html = $this->render('<x-ui.media :src="null" alt="" :width="400" :height="300" />');

        // No broken <img>, and no external placeholder service.
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('image-placeholder', $html);
        $this->assertStringContainsString('aspect-ratio: 400 / 300', $html);
    }

    public function test_media_placeholder_is_hidden_from_assistive_technology_when_decorative(): void
    {
        $html = $this->render('<x-ui.media :src="null" alt="" :width="400" :height="300" />');

        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_media_placeholder_is_labelled_when_it_carries_meaning(): void
    {
        $html = $this->render('<x-ui.media :src="null" :width="400" :height="300" label="Project photograph" />');

        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('Project photograph', $html);
    }

    public function test_media_rejects_an_unsafe_image_reference(): void
    {
        $html = $this->render('<x-ui.media src="javascript:alert(1)" alt="x" :width="10" :height="10" />');

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('image-placeholder', $html);
    }

    // =================================================================
    // Interactive components
    // =================================================================

    public function test_modal_renders_dialog_semantics(): void
    {
        $html = $this->render('<div x-data="modal()"><x-ui.modal title="Confirm">Body</x-ui.modal></div>');

        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        // Focus trapping and Escape-to-close.
        $this->assertStringContainsString('x-trap', $html);
        $this->assertStringContainsString('keydown.escape', $html);
        // The title labels the dialog.
        $this->assertStringContainsString('aria-labelledby=', $html);
        $this->assertStringContainsString('Confirm', $html);
    }

    public function test_modal_without_a_title_still_has_an_accessible_name(): void
    {
        $html = $this->render('<div x-data="modal()"><x-ui.modal>Body</x-ui.modal></div>');

        $this->assertStringContainsString('aria-label="'.__('common.modal.label').'"', $html);
    }

    public function test_dropdown_renders_menu_semantics_and_keyboard_bindings(): void
    {
        $html = $this->render('<x-ui.dropdown label="Options"><button role="menuitem">One</button></x-ui.dropdown>');

        $this->assertStringContainsString('aria-haspopup="true"', $html);
        $this->assertStringContainsString('aria-expanded', $html);
        $this->assertStringContainsString('role="menu"', $html);
        $this->assertStringContainsString('keydown.arrow-down', $html);
        $this->assertStringContainsString('keydown.escape', $html);
    }

    public function test_accordion_renders_the_wai_aria_pattern(): void
    {
        $html = $this->render('<x-ui.accordion :items="$items" />', [
            'items' => [
                ['id' => 'one', 'heading' => 'First question', 'content' => 'First answer'],
                ['id' => 'two', 'heading' => 'Second question', 'content' => 'Second answer'],
            ],
        ]);

        $this->assertStringContainsString('aria-controls="one-panel"', $html);
        $this->assertStringContainsString('id="one-header"', $html);
        $this->assertStringContainsString('aria-labelledby="one-header"', $html);
        $this->assertStringContainsString('role="region"', $html);
        $this->assertStringContainsString('aria-expanded', $html);
        // Arrow key navigation between headers.
        $this->assertStringContainsString('keydown.arrow-down', $html);
        $this->assertStringContainsString('First question', $html);
        $this->assertStringContainsString('Second question', $html);
    }

    public function test_accordion_renders_an_empty_state(): void
    {
        $html = $this->render('<x-ui.accordion :items="[]" />');

        $this->assertStringContainsString(__('common.states.empty'), $html);
    }

    public function test_accordion_escapes_plain_string_content(): void
    {
        $html = $this->render('<x-ui.accordion :items="$items" />', [
            'items' => [['id' => 'x', 'heading' => 'H', 'content' => '<script>alert(1)</script>']],
        ]);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_tabs_render_tablist_semantics(): void
    {
        $html = $this->render('<x-ui.tabs :tabs="$tabs" label="Filter services" />', [
            'tabs' => ['all' => 'All', 'residential' => 'Residential'],
        ]);

        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('role="tab"', $html);
        $this->assertStringContainsString('aria-label="Filter services"', $html);
        $this->assertStringContainsString('aria-selected', $html);
        // Roving tabindex and arrow key navigation.
        $this->assertStringContainsString(':tabindex', $html);
        $this->assertStringContainsString('keydown.arrow-right', $html);
    }

    // =================================================================
    // Layout: navbar, footer, breadcrumb
    // =================================================================

    public function test_navbar_renders_both_desktop_and_mobile_layouts(): void
    {
        $html = $this->get('/')->getContent();

        // Desktop list is hidden below lg; the hamburger is hidden from lg up.
        $this->assertStringContainsString('hidden items-center gap-1 lg:flex', $html);
        $this->assertStringContainsString('lg:hidden', $html);

        // The mobile panel exists and is wired to the trigger.
        $this->assertStringContainsString('id="mobile-menu"', $html);
        $this->assertStringContainsString('aria-controls="mobile-menu"', $html);
    }

    public function test_navbar_mobile_trigger_exposes_its_expanded_state(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString(':aria-expanded="mobileOpen ? \'true\' : \'false\'"', $html);
        $this->assertStringContainsString(__('common.nav.open_menu'), $html);
    }

    public function test_mobile_menu_traps_focus_and_closes_on_escape(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('x-trap.noscroll="mobileOpen"', $html);
        $this->assertStringContainsString('keydown.escape.window="mobileOpen = false"', $html);
    }

    public function test_navbar_marks_the_current_page(): void
    {
        $html = $this->get('/services')->getContent();

        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_navbar_is_a_labelled_landmark(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('aria-label="'.__('common.nav.label').'"', $html);
    }

    public function test_footer_renders_all_of_its_sections(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString(__('common.footer.quick_links'), $html);
        $this->assertStringContainsString(__('common.footer.our_services'), $html);
        $this->assertStringContainsString(__('common.footer.get_in_touch'), $html);
        $this->assertStringContainsString(__('common.footer.business_hours'), $html);
        $this->assertStringContainsString(__('common.cta.back_to_top'), $html);
        // Legal links are reachable from every page.
        $this->assertStringContainsString(route('privacy'), $html);
        $this->assertStringContainsString(route('terms'), $html);
    }

    public function test_footer_social_links_are_labelled_and_safe(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString(__('common.social.facebook'), $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    public function test_breadcrumb_marks_the_final_crumb_as_current(): void
    {
        $html = $this->render('<x-common.breadcrumb :crumbs="$crumbs" />', [
            'crumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Services', 'url' => null],
            ],
        ]);

        $this->assertStringContainsString('aria-label="'.__('common.breadcrumb.label').'"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('Services', $html);
    }

    public function test_breadcrumb_is_omitted_when_there_is_only_one_crumb(): void
    {
        $html = $this->render('<x-common.breadcrumb :crumbs="$crumbs" />', [
            'crumbs' => [['label' => 'Home', 'url' => null]],
        ]);

        $this->assertStringNotContainsString('<nav', $html);
    }

    // =================================================================
    // Global chrome
    // =================================================================

    public function test_skip_to_content_link_is_the_first_focusable_element(): void
    {
        $html = $this->get('/')->getContent();

        $bodyStart = strpos($html, '<body');
        $skipLink = strpos($html, 'class="skip-link"');
        $navStart = strpos($html, '<header');

        $this->assertNotFalse($skipLink);
        $this->assertGreaterThan($bodyStart, $skipLink);
        $this->assertLessThan($navStart, $skipLink, 'The skip link must precede the navigation.');
        $this->assertStringContainsString('href="#main-content"', $html);
    }

    public function test_main_landmark_is_the_skip_link_target(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('id="main-content"', $html);
        // tabindex="-1" lets the skip link move focus, not just scroll.
        $this->assertStringContainsString('<main id="main-content" tabindex="-1"', $html);
    }

    public function test_toast_container_is_a_polite_live_region(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('x-data="toast"', $html);
    }

    public function test_cookie_banner_is_rendered_and_links_to_the_privacy_policy(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString(__('common.cookies.message'), $html);
        $this->assertStringContainsString(__('common.cookies.dismiss'), $html);
        $this->assertStringContainsString(route('privacy'), $html);
    }

    public function test_the_cookie_notice_does_not_appear_on_error_pages(): void
    {
        // The error layout is intentionally minimal.
        $html = $this->get('/no-such-page')->getContent();

        $this->assertStringNotContainsString(__('common.cookies.message'), $html);
    }

    // =================================================================
    // Section components
    // =================================================================

    public function test_hero_renders_a_complete_panel_when_no_slides_exist(): void
    {
        $html = $this->render('<x-sections.hero :slides="collect()" />');

        $this->assertStringContainsString(__('home.hero.empty_heading'), $html);
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('aria-roledescription="carousel"', $html);
    }

    public function test_services_grid_renders_an_empty_state(): void
    {
        $html = $this->render('<x-sections.services-grid :services="collect()" />');

        $this->assertStringContainsString(__('home.services.empty'), $html);
    }

    public function test_featured_projects_renders_an_empty_state(): void
    {
        $html = $this->render('<x-sections.featured-projects :projects="collect()" />');

        $this->assertStringContainsString(__('home.projects.empty'), $html);
    }

    public function test_testimonials_carousel_renders_an_empty_state(): void
    {
        $html = $this->render('<x-sections.testimonials-carousel :testimonials="collect()" />');

        $this->assertStringContainsString(__('home.testimonials.empty'), $html);
    }

    public function test_stats_section_is_omitted_when_there_are_no_figures(): void
    {
        $html = $this->render('<x-sections.stats :settings="$settings" />', [
            'settings' => \App\DataObjects\SiteSettings::fallback(),
        ]);

        $this->assertSame('', trim($html));
    }

    public function test_cta_banner_renders_both_variants(): void
    {
        $default = $this->render(
            '<x-sections.cta-banner heading="Ready?" body="Body." button-label="Go" button-href="/contact" />'
        );
        $emergency = $this->render(
            '<x-sections.cta-banner heading="Urgent" body="Body." variant="emergency" button-label="Call" button-href="/contact" />'
        );

        $this->assertStringContainsString('bg-primary-800', $default);
        $this->assertStringContainsString('Ready?', $default);
        $this->assertStringContainsString('bg-danger-700', $emergency);
    }

    public function test_page_header_renders_its_title_and_breadcrumbs(): void
    {
        $html = $this->render('<x-sections.page-header title="Our services" subtitle="Sub." :breadcrumbs="$crumbs" />', [
            'crumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Services', 'url' => null],
            ],
        ]);

        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('Our services', $html);
        $this->assertStringContainsString('Sub.', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_error_panel_offers_recovery_routes(): void
    {
        $html = $this->render('<x-sections.error-panel code="404" title="Not found" body="Sorry." />');

        $this->assertStringContainsString('Not found', $html);
        $this->assertStringContainsString(__('errors.actions.home'), $html);
        $this->assertStringContainsString(__('errors.actions.contact'), $html);
        // The large numeral is decorative; the heading carries the meaning.
        $this->assertStringContainsString('aria-hidden="true">404', $html);
    }

    // =================================================================
    // Icons
    // =================================================================

    public function test_service_icon_falls_back_for_an_unknown_name(): void
    {
        // The CMS stores the icon name as free text, so an unrecognised value
        // must not break the page.
        $html = $this->render('<x-icons.service name="not-a-real-icon" class="h-6 w-6" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_social_icon_falls_back_for_an_unknown_network(): void
    {
        $html = $this->render('<x-icons.social network="myspace" class="h-5 w-5" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_icons_are_hidden_from_assistive_technology(): void
    {
        foreach (['home', 'building', 'cpu', 'wrench', 'shield', 'battery', 'lightbulb'] as $name) {
            $html = $this->render('<x-icons.service :name="$name" />', ['name' => $name]);

            $this->assertStringContainsString('aria-hidden="true"', $html);
            $this->assertStringContainsString('focusable="false"', $html);
        }
    }
}
