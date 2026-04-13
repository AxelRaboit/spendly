import { ref, computed } from 'vue';

/**
 * Marked extension that converts [[note title]] and [[note#heading]] into clickable wiki-links.
 *
 * Supported syntax:
 *   [[note title]]           → link to another note
 *   [[note title#heading]]   → link to a heading in another note
 *   [[#heading]]             → link to a heading in the current note
 */
export function createWikiLinkExtension() {
    return {
        name: 'wikiLink',
        level: 'inline',
        start(src) {
            return src.indexOf('[[');
        },
        tokenizer(src) {
            const match = src.match(/^\[\[([^\]]+)\]\]/);
            if (match) {
                const raw = match[1].trim();
                const hashIndex = raw.indexOf('#');
                let noteTitle = raw;
                let heading = '';

                if (hashIndex !== -1) {
                    noteTitle = raw.slice(0, hashIndex).trim();
                    heading = raw.slice(hashIndex + 1).trim();
                }

                return {
                    type: 'wikiLink',
                    raw: match[0],
                    title: raw,
                    noteTitle,
                    heading,
                };
            }
        },
        renderer(token) {
            const escape = (str) =>
                str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

            const displayText =
                token.heading && token.noteTitle
                    ? `${token.noteTitle} > ${token.heading}`
                    : token.heading
                      ? token.heading
                      : token.noteTitle;

            const escapedDisplay = escape(displayText);
            const escapedNoteTitle = escape(token.noteTitle);
            const escapedHeading = escape(token.heading);

            return `<a class="wiki-link" data-note-title="${escapedNoteTitle}" data-heading="${escapedHeading}">${escapedDisplay}</a>`;
        },
    };
}

/**
 * Autocomplete logic for [[wiki-links]] inside a textarea.
 */
export function useWikiLinkAutocomplete(flatNotes) {
    const showSuggestions = ref(false);
    const suggestionQuery = ref('');
    const suggestionIndex = ref(0);
    const suggestionPosition = ref({ top: 0, left: 0 });
    const bracketStart = ref(null);

    const filteredSuggestions = computed(() => {
        const query = suggestionQuery.value.toLowerCase();
        return flatNotes.value.filter((note) => (note.title ?? '').toLowerCase().includes(query)).slice(0, 8);
    });

    function onInput(event) {
        const textarea = event.target;
        const cursor = textarea.selectionStart;
        const text = textarea.value;

        // Look backwards from cursor for an opening [[ without a closing ]]
        const before = text.slice(0, cursor);
        const lastOpen = before.lastIndexOf('[[');
        const lastClose = before.lastIndexOf(']]');

        if (lastOpen !== -1 && lastOpen > lastClose) {
            const query = before.slice(lastOpen + 2);
            // Only show if no newline in query
            if (!query.includes('\n')) {
                bracketStart.value = lastOpen;
                suggestionQuery.value = query;
                suggestionIndex.value = 0;
                showSuggestions.value = true;
                positionDropdown(textarea, lastOpen);
                return;
            }
        }

        closeSuggestions();
    }

    function positionDropdown(textarea, startIndex) {
        // Create a mirror div to compute caret position
        const text = textarea.value.substring(0, startIndex);
        const mirror = document.createElement('div');
        const style = window.getComputedStyle(textarea);

        mirror.style.position = 'absolute';
        mirror.style.visibility = 'hidden';
        mirror.style.whiteSpace = 'pre-wrap';
        mirror.style.overflowWrap = 'break-word';
        mirror.style.width = style.width;
        mirror.style.font = style.font;
        mirror.style.letterSpacing = style.letterSpacing;
        mirror.style.padding = style.padding;
        mirror.style.lineHeight = style.lineHeight;

        mirror.textContent = text;
        const marker = document.createElement('span');
        marker.textContent = '|';
        mirror.appendChild(marker);

        document.body.appendChild(mirror);

        const markerRect = marker.getBoundingClientRect();
        const mirrorRect = mirror.getBoundingClientRect();

        suggestionPosition.value = {
            top: markerRect.top - mirrorRect.top - textarea.scrollTop + 24,
            left: markerRect.left - mirrorRect.left,
        };

        document.body.removeChild(mirror);
    }

    function onKeydown(event) {
        if (!showSuggestions.value) return;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            suggestionIndex.value = Math.min(suggestionIndex.value + 1, filteredSuggestions.value.length - 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            suggestionIndex.value = Math.max(suggestionIndex.value - 1, 0);
        } else if (event.key === 'Enter' || event.key === 'Tab') {
            if (filteredSuggestions.value.length > 0) {
                event.preventDefault();
                return filteredSuggestions.value[suggestionIndex.value];
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            closeSuggestions();
        }
        return null;
    }

    function insertSuggestion(textarea, note, editContent) {
        const start = bracketStart.value;
        const cursor = textarea.selectionStart;
        const title = note.title || 'Untitled';
        const before = editContent.slice(0, start);
        const after = editContent.slice(cursor);
        const insertion = `[[${title}]]`;
        const newContent = before + insertion + after;
        const newCursor = start + insertion.length;

        closeSuggestions();

        return { newContent, newCursor };
    }

    function closeSuggestions() {
        showSuggestions.value = false;
        suggestionQuery.value = '';
        suggestionIndex.value = 0;
        bracketStart.value = null;
    }

    return {
        showSuggestions,
        suggestionQuery,
        suggestionIndex,
        suggestionPosition,
        filteredSuggestions,
        onInput,
        onKeydown,
        insertSuggestion,
        closeSuggestions,
    };
}
