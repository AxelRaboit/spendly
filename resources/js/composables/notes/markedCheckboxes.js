/**
 * Marked renderer override to make checkboxes interactive.
 *
 * Adds data-checkbox-index to each checkbox so click handlers
 * can map back to the source markdown and toggle the state.
 */
let checkboxCounter = 0;

export function resetCheckboxCounter() {
    checkboxCounter = 0;
}

export function createCheckboxRenderer() {
    return {
        listitem({ text, task, checked }) {
            if (!task) {
                return `<li>${text}</li>\n`;
            }
            const index = checkboxCounter++;
            const checkedAttr = checked ? 'checked' : '';
            return `<li class="task-list-item">
  <input type="checkbox" class="task-checkbox" data-checkbox-index="${index}" ${checkedAttr} />
  <span>${text}</span>
</li>\n`;
        },
    };
}

/**
 * Toggle the Nth checkbox in raw markdown content.
 * Returns the updated content string.
 */
export function toggleCheckboxInContent(content, checkboxIndex) {
    let counter = 0;
    return content.replace(/^(\s*[-*+]\s+)\[([ xX])\]/gm, (match, prefix, state) => {
        if (counter++ === checkboxIndex) {
            return state.trim() === '' ? `${prefix}[x]` : `${prefix}[ ]`;
        }
        return match;
    });
}
