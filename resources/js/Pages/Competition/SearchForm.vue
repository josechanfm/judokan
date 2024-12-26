<template>
  <WebLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">比賽報名查詢</h2>
    </template>
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
        <div class="text-center font-bold text-2xl">{{ competition.title_zh }}</div>
        <div class="p-4">
          <a-form
            :model="data"
            :label-col="{ span: 4 }"
            :wrapper-col="{ span: 16 }"
            :rules="rules"
            @finish="onFinish"
          >
            <a-form-item :label="$t('id_num')" name="id_num">
              <a-input v-model:value="data.id_num" />
            </a-form-item>
            <a-form-item :label="$t('email')" name="email">
              <a-input v-model:value="data.email" />
            </a-form-item>
            <a-form-item :label="$t('mobile_number')" name="mobile">
              <a-input v-model:value="data.mobile" />
            </a-form-item>
            <div class="flex flex-row item-center justify-center">
              <a-button type="primary" html-type="submit" :loading="loading">{{
                $t("submit")
              }}</a-button>
            </div>
          </a-form>
        </div>
      </div>
    </div>
  </WebLayout>
</template>

<script>
import WebLayout from "@/Layouts/WebLayout.vue";
import { Inertia } from "@inertiajs/inertia";

export default {
  components: {
    WebLayout,
  },
  props: ["competition"],
  data() {
    return {
      data: {},
      loading: false,
      rules: {
        id_num: { required: true },
        email: { required: true, type: "email" },
        mobile: { required: true },
      },
    };
  },
  created() {},
  methods: {
    onFinish() {
      this.loading = true;
      this.$inertia.post(
        route("competitions.confirmSearchForm", this.competition.id),
        this.data,
        {
          onSuccess: (page) => {
            console.log(page);
            this.loading = false;
          },
          onError: (error) => {
            console.log(error);
            this.loading = false;
          },
        }
      );
    },
  },
};
</script>
