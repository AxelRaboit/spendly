import { ref } from 'vue';

const STORAGE_KEY = 'note-templates';

function loadTemplates() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    } catch {
        return [];
    }
}

function saveTemplates(templates) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(templates));
}

export function useNoteTemplates() {
    const templates = ref(loadTemplates());

    function addTemplate(name, content) {
        const template = {
            id: Date.now().toString(36),
            name,
            content,
            createdAt: new Date().toISOString(),
        };
        templates.value.push(template);
        saveTemplates(templates.value);
        return template;
    }

    function deleteTemplate(id) {
        templates.value = templates.value.filter((template) => template.id !== id);
        saveTemplates(templates.value);
    }

    function getTemplate(id) {
        return templates.value.find((template) => template.id === id);
    }

    return {
        templates,
        addTemplate,
        deleteTemplate,
        getTemplate,
    };
}
