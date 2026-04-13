import { ref, computed } from 'vue';

const COMMANDS = [
    { id: 'h1', label: 'Heading 1', icon: 'H1', insert: '# ', type: 'line' },
    { id: 'h2', label: 'Heading 2', icon: 'H2', insert: '## ', type: 'line' },
    { id: 'h3', label: 'Heading 3', icon: 'H3', insert: '### ', type: 'line' },
    { id: 'bullet', label: 'Bullet list', icon: '•', insert: '- ', type: 'line' },
    { id: 'numbered', label: 'Numbered list', icon: '1.', insert: '1. ', type: 'line' },
    { id: 'checkbox', label: 'Checkbox', icon: '☐', insert: '- [ ] ', type: 'line' },
    { id: 'quote', label: 'Quote', icon: '❝', insert: '> ', type: 'line' },
    { id: 'divider', label: 'Divider', icon: '—', insert: '\n---\n', type: 'block' },
    { id: 'code', label: 'Code block', icon: '</>', insert: '```\n\n```', type: 'block', cursorOffset: 4 },
    { id: 'callout', label: 'Callout', icon: '!', insert: '> [!info] \n> ', type: 'block', cursorOffset: 10 },
    { id: 'link', label: 'Wiki link', icon: '[[', insert: '[[]]', type: 'inline', cursorOffset: 2 },
    { id: 'bold', label: 'Bold', icon: 'B', insert: '****', type: 'inline', cursorOffset: 2 },
    { id: 'italic', label: 'Italic', icon: 'I', insert: '**', type: 'inline', cursorOffset: 1 },
    { id: 'strikethrough', label: 'Strikethrough', icon: 'S̶', insert: '~~~~', type: 'inline', cursorOffset: 2 },
    {
        id: 'table',
        label: 'Table',
        icon: '⊞',
        insert: '| Column 1 | Column 2 |\n| --- | --- |\n| Cell | Cell |\n',
        type: 'block',
    },
];

export function useSlashCommands() {
    const showSlash = ref(false);
    const slashQuery = ref('');
    const slashIndex = ref(0);
    const slashPosition = ref({ top: 0, left: 0 });
    const slashStart = ref(null);

    const filteredCommands = computed(() => {
        const query = slashQuery.value.toLowerCase();
        return COMMANDS.filter((command) => command.label.toLowerCase().includes(query) || command.id.includes(query));
    });

    function onInput(event) {
        const textarea = event.target;
        const cursor = textarea.selectionStart;
        const text = textarea.value;
        const before = text.slice(0, cursor);

        // Check if / is at start of line or after whitespace
        const lineStart = before.lastIndexOf('\n') + 1;
        const lineContent = before.slice(lineStart);

        if (lineContent.startsWith('/')) {
            slashStart.value = lineStart;
            slashQuery.value = lineContent.slice(1);
            slashIndex.value = 0;
            showSlash.value = true;
            positionDropdown(textarea, lineStart);
            return;
        }

        closeSlash();
    }

    function positionDropdown(textarea, startIndex) {
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

        slashPosition.value = {
            top: markerRect.top - mirrorRect.top - textarea.scrollTop + 24,
            left: markerRect.left - mirrorRect.left,
        };

        document.body.removeChild(mirror);
    }

    function onKeydown(event) {
        if (!showSlash.value) return null;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            slashIndex.value = Math.min(slashIndex.value + 1, filteredCommands.value.length - 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            slashIndex.value = Math.max(slashIndex.value - 1, 0);
        } else if (event.key === 'Enter' || event.key === 'Tab') {
            if (filteredCommands.value.length > 0) {
                event.preventDefault();
                return filteredCommands.value[slashIndex.value];
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            closeSlash();
        }
        return null;
    }

    function insertCommand(textarea, command, content) {
        const start = slashStart.value;
        const cursor = textarea.selectionStart;
        const before = content.slice(0, start);
        const after = content.slice(cursor);
        const newContent = before + command.insert + after;
        const newCursor = command.cursorOffset ? start + command.cursorOffset : start + command.insert.length;

        closeSlash();
        return { newContent, newCursor };
    }

    function closeSlash() {
        showSlash.value = false;
        slashQuery.value = '';
        slashIndex.value = 0;
        slashStart.value = null;
    }

    return {
        showSlash,
        slashIndex,
        slashPosition,
        filteredCommands,
        onInput,
        onKeydown,
        insertCommand,
        closeSlash,
    };
}
