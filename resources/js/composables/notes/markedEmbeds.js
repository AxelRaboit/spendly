/**
 * Marked extension for Obsidian-style note embeds.
 *
 * Syntax: ![[note title]]
 *
 * Renders a placeholder div that gets hydrated with actual content
 * by the Vue component via a click-to-load or cached content approach.
 */
export function createEmbedExtension() {
    return {
        name: 'noteEmbed',
        level: 'block',
        start(src) {
            return src.indexOf('![[');
        },
        tokenizer(src) {
            const match = src.match(/^!\[\[([^\]]+)\]\]/);
            if (match) {
                return {
                    type: 'noteEmbed',
                    raw: match[0],
                    title: match[1].trim(),
                };
            }
        },
        renderer(token) {
            const escaped = token.title
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
            return `<div class="note-embed" data-embed-title="${escaped}">
  <div class="note-embed-header">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
    <span class="note-embed-title">${escaped}</span>
  </div>
  <div class="note-embed-content"><span class="note-embed-placeholder">Click to load</span></div>
</div>\n`;
        },
    };
}
