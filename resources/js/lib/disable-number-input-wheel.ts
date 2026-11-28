/**
 * Prevent a focused number input from changing its value when the mouse wheel
 * is scrolled over it. Blurring the input before the browser's default wheel
 * action keeps the value untouched while allowing the page to scroll normally.
 */
export function disableNumberInputWheel(): void {
    document.addEventListener(
        'wheel',
        (event: WheelEvent) => {
            if (
                event.target instanceof HTMLInputElement &&
                event.target.type === 'number' &&
                document.activeElement === event.target
            ) {
                event.target.blur();
            }
        },
        { passive: true },
    );
}
