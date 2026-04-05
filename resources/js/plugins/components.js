import AppButton from '@/components/ui/AppButton.vue';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
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
import SelectInput from '@/components/form/SelectInput.vue';

export default {
    install(app) {
        app.component('AppButton', AppButton);
        app.component('AppPageHeader', AppPageHeader);
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
    },
};
