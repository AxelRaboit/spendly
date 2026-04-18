import { computed, ref } from 'vue';
import axios from 'axios';

export function useImportUpload(wallets, translate, onPreviewSuccess) {
    const step = ref(1);
    const file = ref(null);
    const isDragging = ref(false);
    const uploadError = ref('');
    const uploading = ref(false);
    const walletId = ref(wallets[0]?.id ?? '');

    const selectedWalletName = computed(() => wallets.find((w) => w.id == walletId.value)?.name ?? '');

    function onFileInput(event) {
        const selectedFile = event.target.files?.[0];
        if (selectedFile) selectFile(selectedFile);
    }

    function onDrop(event) {
        isDragging.value = false;
        const droppedFile = event.dataTransfer.files?.[0];
        if (droppedFile) selectFile(droppedFile);
    }

    function selectFile(uploadedFile) {
        if (!uploadedFile.name.match(/\.xlsx$/i)) {
            uploadError.value = translate('import.errorFile');
            return;
        }
        file.value = uploadedFile;
        uploadError.value = '';
    }

    async function uploadAndPreview(bulkCategory, onRows) {
        if (!file.value) {
            uploadError.value = translate('import.errorFile');
            return;
        }
        uploading.value = true;
        uploadError.value = '';

        const fd = new FormData();
        fd.append('file', file.value);

        try {
            const response = await axios.post('/import/preview', fd, {
                headers: { 'Content-Type': 'multipart/form-data', 'X-Requested-With': 'XMLHttpRequest' },
            });
            onRows(response.data, bulkCategory);
            step.value = 2;
        } catch (err) {
            uploadError.value = err.response?.data?.message ?? translate('import.errorParse');
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
