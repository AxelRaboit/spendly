/**
 * Tour State Management
 *
 * Handles all tour state persistence in localStorage and reactivity.
 * This is a singleton composable that manages the global tour state.
 */

import { ref } from 'vue';

const STORAGE_KEY = 'spendly_tour';

// Module-level reactive ref so all components react when the tour ends
const tourActive = ref(false);

function _readActive() {
    try {
        const s = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null') ?? null;
        return s !== null && !s.completed && !s.skipped;
    } catch {
        return false;
    }
}

// Initialise on module load
tourActive.value = _readActive();

export function useTourState() {
    function getState() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null') ?? null;
        } catch {
            return null;
        }
    }

    function setState(patch) {
        const current = getState() ?? {};
        const next = { ...current, ...patch };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
        tourActive.value = next !== null && !next.completed && !next.skipped;
    }

    function isActive() {
        return tourActive.value;
    }

    function isCompleted() {
        const s = getState();
        return s?.completed === true || s?.skipped === true;
    }

    function startTour() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ page: 0, completed: false, skipped: false }));
        tourActive.value = true;
        window.location.href = route('wallets.index');
    }

    function resetTour() {
        localStorage.removeItem(STORAGE_KEY);
        tourActive.value = false;
    }

    return { getState, setState, isActive, isCompleted, startTour, resetTour, tourActive };
}
