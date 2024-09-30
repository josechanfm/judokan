<script>
import WebLayout from '@/Layouts/WebLayout.vue';
import { Modal } from 'ant-design-vue';

export default {
    components: {
        WebLayout
    },
    props: ['organizations'],
    data() {
        return {
            formState: {
                username: '',
                password: '',
            }
        }
    },
    methods: {
        onFinish(values) {
            this.$inertia.post(route('registration.store'), this.formState, {
                onSuccess: (page) => {
                    console.log(page);
                },
                onError: (err) => {
                    console.log(err)
                    Modal.error({
                        title:'title',
                        content: 'content'
                        // title: this.$t('registration_error'),
                        // content: this.$t(err.code),
                    });
                }
            });

        },
        onFinishFailed(errorInfo) {
            console.log('Failed:', errorInfo);
        }
    }
}

</script>

<template>
    <WebLayout title="Dashboard">
        <div class="flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <a-typography-title :level="3">Registration Form</a-typography-title>
            <div class="w-full max-w-lg mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg"><!--v-if-->
                <a-form :model="formState" name="basic" layout="vertical" autocomplete="off" @finish="onFinish"
                    @finishFailed="onFinishFailed">
                    <a-form-item label="Name Zh" name="name_zh"
                        :rules="[{ required: true, message: 'Please input your name zh!' }]">
                        <a-input v-model:value="formState.name_zh" />
                    </a-form-item>
                    <a-form-item label="Name Fn" name="name_fn">
                        <a-input v-model:value="formState.name_fn" />
                    </a-form-item>
                    <a-form-item label="Organization" name="organization_id"
                        :rules="[{ required: true, message: 'Please input your organization belongs to!' }]">
                        <a-select v-model:value="formState.organization_id" :options="organizations"
                            :fieldNames="{ value: 'id', label: 'full_name' }" />
                    </a-form-item>
                    <!-- <a-form-item label="Registration Code" name="registration_code"
                        :rules="[{ required: true, message: 'Please input the organization registration' }]">
                        <a-input v-model:value="formState.registration_code" />
                    </a-form-item> -->
                    <a-form-item label="Email (for login)" name="email"
                        :rules="[{ required: true, message: 'Please input your email!' }]">
                        <a-input v-model:value="formState.email" type="email" />
                    </a-form-item>
                    <a-form-item label="Password" name="password"
                        :rules="[{ required: true, message: 'Please input your password!' }]">
                        <a-input-password v-model:value="formState.password" />
                    </a-form-item>
                    <div class="flex flex-row item-center justify-center">
                        <a-button type="primary" html-type="submit">{{$t('submit')}}</a-button>
                    </div>
                </a-form>
            </div>
        </div>
    </WebLayout>
</template>

