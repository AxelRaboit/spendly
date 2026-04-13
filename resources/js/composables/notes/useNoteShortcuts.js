/**
 * Keyboard shortcuts for the note editor textarea.
 *
 * Ctrl+B → bold (**selection**)
 * Ctrl+I → italic (*selection*)
 * Ctrl+K → link ([selection](url))
 * Ctrl+H → heading (# prepend to line)
 * Ctrl+Shift+X → strikethrough (~~selection~~)
 * Ctrl+E → inline code (`selection`)
 * Ctrl+Shift+K → code block (```\nselection\n```)
 * Ctrl+L → unordered list (- prepend to line)
 * Ctrl+Shift+L → ordered list (1. prepend to line)
 * Ctrl+Shift+C → checkbox (- [ ] prepend to line)
 */

const SHORTCUTS = [
    { key: 'b', ctrl: true, shift: false, action: wrapSelection('**', '**') },
    { key: 'i', ctrl: true, shift: false, action: wrapSelection('*', '*') },
    { key: 'k', ctrl: true, shift: false, action: wrapLink },
    { key: 'h', ctrl: true, shift: false, action: prependLine('# ') },
    { key: 'x', ctrl: true, shift: true, action: wrapSelection('~~', '~~') },
    { key: 'e', ctrl: true, shift: false, action: wrapSelection('`', '`') },
    { key: 'k', ctrl: true, shift: true, action: wrapSelection('```\n', '\n```') },
    { key: 'l', ctrl: true, shift: false, action: prependLine('- ') },
    { key: 'l', ctrl: true, shift: true, action: prependLine('1. ') },
    { key: 'c', ctrl: true, shift: true, action: prependLine('- [ ] ') },
];

function wrapSelection(before, after) {
    return (textarea, content) => {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = content.slice(start, end);
        const replacement = before + selected + after;
        const newContent = content.slice(0, start) + replacement + content.slice(end);
        const cursorPos = selected.length > 0 ? start + replacement.length : start + before.length;
        return { newContent, cursorPos };
    };
}

function wrapLink(textarea, content) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = content.slice(start, end);
    const replacement = `[${selected || 'text'}](url)`;
    const newContent = content.slice(0, start) + replacement + content.slice(end);
    // Place cursor on "url"
    const cursorPos = selected
        ? start + selected.length + 3 // after "](", on "url"
        : start + 1; // on "text"
    const cursorEnd = selected ? cursorPos + 3 : cursorPos + 4;
    return { newContent, cursorPos, cursorEnd };
}

function prependLine(prefix) {
    return (textarea, content) => {
        const start = textarea.selectionStart;
        // Find start of current line
        const lineStart = content.lastIndexOf('\n', start - 1) + 1;
        const newContent = content.slice(0, lineStart) + prefix + content.slice(lineStart);
        const cursorPos = start + prefix.length;
        return { newContent, cursorPos };
    };
}

export function handleNoteShortcut(event, textarea, content) {
    const isCtrl = event.ctrlKey || event.metaKey;
    if (!isCtrl) return null;

    const shortcut = SHORTCUTS.find(
        (s) => s.key === event.key.toLowerCase() && s.ctrl === isCtrl && s.shift === event.shiftKey
    );

    if (!shortcut) return null;

    event.preventDefault();
    return shortcut.action(textarea, content);
}
