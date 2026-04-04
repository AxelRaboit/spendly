import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import DateInput from '@/components/form/DateInput.vue';
import DeleteButton from '@/components/ui/DeleteButton.vue';
import EditButton from '@/components/ui/EditButton.vue';
import AppPagination from '@/components/ui/AppPagination.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import FilterSelect from '@/components/form/FilterSelect.vue';
import SearchInput from '@/components/form/SearchInput.vue';
import SubmitButton from '@/components/form/SubmitButton.vue';
import SelectInput from '@/components/form/SelectInput.vue';

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
        app.component('FilterSelect', FilterSelect);
        app.component('SearchInput', SearchInput);
        app.component('SubmitButton', SubmitButton);
    },
};
