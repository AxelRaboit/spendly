import { computed, ref } from 'vue';
import axios from 'axios';

export function useImportUpload(wallets, t, onPreviewSuccess) {
    const step = ref(1);
    const file = ref(null);
    const isDragging = ref(false);
    const uploadError = ref('');
    const uploading = ref(false);
    const walletId = ref(wallets[0]?.id ?? '');

    const selectedWalletName = computed(() => wallets.find((w) => w.id == walletId.value)?.name ?? '');

    function onFileInput(e) {
        const f = e.target.files?.[0];
        if (f) selectFile(f);
    }

    function onDrop(e) {
        isDragging.value = false;
        const f = e.dataTransfer.files?.[0];
        if (f) selectFile(f);
    }

    function selectFile(f) {
        if (!f.name.match(/\.xlsx$/i)) {
            uploadError.value = t('import.errorFile');
            return;
        }
        file.value = f;
        uploadError.value = '';
    }

    async function uploadAndPreview(bulkCategory, onRows) {
        if (!file.value) {
            uploadError.value = t('import.errorFile');
            return;
        }
        uploading.value = true;
        uploadError.value = '';

        const fd = new FormData();
        fd.append('file', file.value);

        try {
            const res = await axios.post('/import/preview', fd, {
                headers: { 'Content-Type': 'multipart/form-data', 'X-Requested-With': 'XMLHttpRequest' },
            });
            onRows(res.data, bulkCategory);
            step.value = 2;
        } catch (err) {
            uploadError.value = err.response?.data?.message ?? t('import.errorParse');
        } finally {
            uploading.value = false;
        }
    }

    return {
        step,
        file,
        isDragging,
        uploadError,
        uploading,
        walletId,
        selectedWalletName,
        onFileInput,
        onDrop,
        uploadAndPreview,
    };
}
