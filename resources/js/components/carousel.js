/**
 * Accessible carousel used for hero slides and testimonials.
 *
 * Accessibility:
 *   - the region is labelled with aria-roledescription="carousel";
 *   - the live region politeness switches to "off" while auto-rotating, per the
 *     APG, and to "polite" once the visitor takes manual control;
 *   - auto-rotation pauses on hover, on focus within, and when the tab is
 *     hidden, and never starts when prefers-reduced-motion is set;
 *   - Arrow keys move between slides, Home/End jump to the ends.
 *
 * @param {{ total?: number, interval?: number, autoplay?: boolean }} options
 */
export default function carousel(options = {}) {
    return {
        current: 0,
        total: options.total ?? 0,
        interval: options.interval ?? 6000,
        autoplayRequested: options.autoplay ?? true,
        playing: false,
        userControlled: false,
        timer: null,

        init() {
            if (this.total <= 1) {
                return;
            }

            if (this.autoplayRequested && !this.prefersReducedMotion()) {
                this.play();
            }

            // Stop consuming resources (and moving content) in a hidden tab.
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopTimer();
                } else if (this.playing) {
                    this.startTimer();
                }
            });

            this.$root.addEventListener('alpine:destroyed', () => this.stopTimer());
        },

        prefersReducedMotion() {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        },

        isActive(index) {
            return this.current === index;
        },

        go(index) {
            if (this.total === 0) {
                return;
            }

            this.current = (index + this.total) % this.total;
        },

        next() {
            this.go(this.current + 1);
        },

        previous() {
            this.go(this.current - 1);
        },

        /** Manual navigation: stop auto-rotation so content stops moving. */
        manualNext() {
            this.takeControl();
            this.next();
        },

        manualPrevious() {
            this.takeControl();
            this.previous();
        },

        manualGo(index) {
            this.takeControl();
            this.go(index);
        },

        takeControl() {
            this.userControlled = true;
            this.pause();
        },

        play() {
            if (this.total <= 1) {
                return;
            }

            this.playing = true;
            this.startTimer();
        },

        pause() {
            this.playing = false;
            this.stopTimer();
        },

        toggle() {
            this.playing ? this.pause() : this.play();
        },

        startTimer() {
            this.stopTimer();
            this.timer = window.setInterval(() => this.next(), this.interval);
        },

        stopTimer() {
            if (this.timer !== null) {
                window.clearInterval(this.timer);
                this.timer = null;
            }
        },

        /** Pause while hovered or focused, resume only if still in play mode. */
        onEnter() {
            this.stopTimer();
        },

        onLeave() {
            if (this.playing) {
                this.startTimer();
            }
        },

        /**
         * Live region politeness. While slides advance automatically the region
         * must be silent, otherwise a screen reader announces every rotation.
         */
        get liveSetting() {
            return this.playing ? 'off' : 'polite';
        },
    };
}
