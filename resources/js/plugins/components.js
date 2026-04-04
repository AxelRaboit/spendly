import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import DateInput from '@/components/DateInput.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import EditButton from '@/components/EditButton.vue';
import AppPagination from '@/components/AppPagination.vue';
import EmptyState from '@/components/EmptyState.vue';
import SelectInput from '@/components/SelectInput.vue';

export default {
    install(app) {
        app.component('PrimaryButton', PrimaryButton);
        app.component('SecondaryButton', SecondaryButton);
        app.component('DangerButton', DangerButton);
        app.component('TextInput', TextInput);
        app.component('SelectInput', SelectInput);
        app.component('DateInput', DateInput);
        app.component('InputLabel', InputLabel);
        app.component('InputError', InputError);
        app.component('ConfirmModal', ConfirmModal);
        app.component('DeleteButton', DeleteButton);
        app.component('EditButton', EditButton);
        app.component('AppPagination', AppPagination);
        app.component('EmptyState', EmptyState);
    },
};
