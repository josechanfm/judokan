<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";
import AuthenticationCard from "@/Components/AuthenticationCard.vue";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
  status: String,
});

const form = useForm();

const submit = () => {
  form.post(route("verification.send"));
};

const verificationLinkSent = computed(() => props.status === "verification-link-sent");
</script>

<template>
  <Head title="Email Verification" />

  <AuthenticationCard>
    <template #logo>
      <AuthenticationCardLogo />
    </template>

    <div class="mb-4 text-sm text-gray-600">
      在繼續之前，您可以通過點擊我們剛剛發送到您郵箱的鏈接來驗證您的郵箱地址嗎？如果您沒有收到郵件，我們將樂意重新發送給您。
    </div>

    <div v-if="verificationLinkSent" class="mb-4 font-medium text-sm text-green-600">
      一個新的驗證鏈接已經發送到您在個人檔案設置中提供的郵箱地址。
    </div>

    <form @submit.prevent="submit">
      <div class="mt-4 flex items-center justify-between">
        <PrimaryButton
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          重新發送驗證郵件
        </PrimaryButton>

        <div>
          <Link
            :href="route('profile.show')"
            class="underline text-sm text-gray-600 hover:text-gray-900"
          >
            編輯個人檔案</Link
          >

          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="underline text-sm text-gray-600 hover:text-gray-900 ml-2"
          >
            登出
          </Link>
        </div>
      </div>
    </form>
  </AuthenticationCard>
</template>
