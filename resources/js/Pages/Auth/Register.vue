<script setup>
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import AuthenticationCard from "@/Components/AuthenticationCard.vue";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Icon, { QuestionCircleOutlined } from "@ant-design/icons-vue";

defineProps({
  organizations: Object,
});

const form = useForm({
  name_zh: "",
  name_fn: "",
  email: "",
  organization_id: "",
  password: "",
  password_confirmation: "",
});

const submit = () => {
  form.post(route("register"), {
    onFinish: () => form.reset("password", "password_confirmation"),
  });
};
</script>

<template>
  <Head title="Register" />

  <AuthenticationCard>
    <template #logo>
      <AuthenticationCardLogo />
    </template>

    <a-form
      :model="form"
      name="basic"
      layout="vertical"
      autocomplete="off"
      @submit.prevent="submit"
    >
      <a-form-item
        :label="$t('name_zh')"
        name="name_zh"
        :rules="[{ required: true, message: 'Please input your given name!' }]"
      >
        <a-input v-model:value="form.name_zh" />
      </a-form-item>
      <a-form-item :label="$t('name_fn')" name="name_fn">
        <a-input v-model:value="form.name_fn" />
      </a-form-item>
      <a-form-item
        :label="$t('organization')"
        name="organization_id"
        :rules="[
          { required: true, message: 'Please input your organization belongs to!' },
        ]"
      >
        <a-select
          v-model:value="form.organization_id"
          :options="organizations"
          :fieldNames="{ value: 'id', label: 'full_name' }"
        />
      </a-form-item>
      <!-- <a-form-item label="Registration Code" name="registration_code"
                        :rules="[{ required: true, message: 'Please input the organization registration' }]">
                        <a-input v-model:value="form.registration_code" />
                    </a-form-item> -->
      <a-form-item
        :label="$t('login_email')"
        name="email"
        :rules="[{ required: true, message: 'Please input your email!' }]"
      >
        <a-input v-model:value="form.email" type="email" />
      </a-form-item>
      <a-form-item
        :label="$t('password')"
        name="password"
        :rules="[{ required: true, message: 'Please input your password!' }]"
      >
        <a-input-password v-model:value="form.password" />
      </a-form-item>
      <a-form-item
        :label="$t('confirm_password')"
        name="password_confirmation"
        :rules="[{ required: true, message: 'Please input your confirm password!' }]"
      >
        <a-input-password v-model:value="form.password_confirmation" />
      </a-form-item>
      <div class="flex flex-row item-center justify-center">
        <a-button type="primary" html-type="submit">{{ $t("submit") }}</a-button>
      </div>
    </a-form>
  </AuthenticationCard>
</template>
