import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DateInput from '@/Components/DateInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

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
    },
};
