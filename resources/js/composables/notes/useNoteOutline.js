import { computed } from 'vue';

/**
 * Parse headings from markdown content for an outline/TOC panel.
 */
export function useNoteOutline(editContent) {
    const headings = computed(() => {
        const content = editContent.value || '';
        const results = [];
        const regex = /^(#{1,6})\s+(.+)$/gm;
        let match;

        while ((match = regex.exec(content)) !== null) {
            const level = match[1].length;
            const text = match[2].trim();
            const slug = text
                .toLowerCase()
                .replace(/[^\w]+/g, '-')
                .replace(/(^-|-$)/g, '');
            results.push({ level, text, slug });
        }

        return results;
    });

    return { headings };
}
