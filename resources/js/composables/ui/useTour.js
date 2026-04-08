/**
 * Tour Composable - Public API
 *
 * Re-exports the main functions from the split tour composables.
 * Maintains backwards compatibility with existing code.
 *
 * Split into:
 * - useTourState: state management and localStorage
 * - useTourSteps: step building and DOM helpers
 * - useTourDriver: tour orchestration and execution
 */

import { useTourState } from './useTourState';
import { useTourDriver } from './useTourDriver';

export function useTour() {
    const { getState, setState, isActive, isCompleted, startTour, resetTour, tourActive } = useTourState();
    const { initForPage } = useTourDriver();

    return {
        startTour,
        resetTour,
        initForPage,
        isActive,
        isCompleted,
        getState,
        setState,
        tourActive,
    };
}
