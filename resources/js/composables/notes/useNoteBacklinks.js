import { ref, watch } from 'vue';
import axios from 'axios';

export function useNoteBacklinks(loadedNote) {
    const backlinks = ref([]);
    const unlinkedMentions = ref([]);
    const loadingBacklinks = ref(false);

    watch(loadedNote, async (note) => {
        backlinks.value = [];
        unlinkedMentions.value = [];

        if (!note?.id || !note?.title) return;

        loadingBacklinks.value = true;
        try {
            const [backlinksResponse, mentionsResponse] = await Promise.all([
                axios.get(route('notes.backlinks', note.id)),
                axios.get(route('notes.unlinkedMentions', note.id)),
            ]);
            backlinks.value = backlinksResponse.data;
            unlinkedMentions.value = mentionsResponse.data;
        } finally {
            loadingBacklinks.value = false;
        }
    });

    return {
        backlinks,
        unlinkedMentions,
        loadingBacklinks,
    };
}
