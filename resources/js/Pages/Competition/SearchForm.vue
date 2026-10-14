<template>
  <WebLayout title="Dashboard">
    <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
      <!-- 頁面標題 -->
      <div class="mb-4 flex items-center gap-3">
        <span class="h-6 w-1.5 rounded-full bg-[#0081C8]"></span>
        <h2 class="text-xl font-bold tracking-wide text-gray-800">比賽報名查詢</h2>
        <inertia-link
          :href="route('competitions.index')"
          class="ml-auto inline-flex items-center text-sm text-gray-500 transition hover:text-[#0081C8]"
        >
          返回賽事列表
        </inertia-link>
      </div>

      <div class="overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-gray-200/70">
        <!-- 腰帶色條 -->
        <span class="block h-1 bg-gradient-to-r from-[#0081C8] to-[#0b3d78]"></span>

        <div class="p-6">
          <!-- 賽事名稱 -->
          <div class="mb-6 flex items-start gap-3">
            <span
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-lg text-[#0081C8] ring-1 ring-sky-100"
            >
              <search-outlined />
            </span>
            <div class="min-w-0">
              <h3 class="text-lg font-bold text-gray-800">{{ competition.title_zh }}</h3>
              <p class="mt-0.5 text-sm text-gray-500">
                請輸入報名時填寫的資料，以查詢您的報名紀錄
              </p>
            </div>
          </div>

          <a-form :model="data" layout="vertical" :rules="rules" @finish="onFinish">
            <a-form-item :label="$t('id_num')" name="id_num">
              <a-input
                v-model:value="data.id_num"
                size="large"
                placeholder="請輸入身份證號碼"
                allow-clear
              />
            </a-form-item>
            <a-form-item :label="$t('email')" name="email">
              <a-input
                v-model:value="data.email"
                size="large"
                placeholder="請輸入電郵地址"
                allow-clear
              />
            </a-form-item>
            <a-form-item :label="$t('mobile_number')" name="mobile">
              <a-input
                v-model:value="data.mobile"
                size="large"
                placeholder="請輸入手機號碼"
                allow-clear
              />
            </a-form-item>

            <div class="pt-2">
              <a-button
                type="primary"
                html-type="submit"
                size="large"
                :loading="loading"
                class="w-full"
              >
                {{ $t("submit") }}
              </a-button>
            </div>
          </a-form>
        </div>
      </div>
    </div>
  </WebLayout>
</template>

<script>
import WebLayout from "@/Layouts/WebLayout.vue";
import { SearchOutlined } from "@ant-design/icons-vue";

export default {
  components: {
    WebLayout,
    SearchOutlined,
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
<style scoped>
/* === AntD 元件改用柔道藍 === */
:deep(.ant-btn-primary) {
  background-color: #0081c8;
  box-shadow: none;
}

:deep(.ant-btn-primary:not(:disabled):hover) {
  background-color: #0b6ea8;
}

:deep(.ant-btn-primary:not(:disabled):active) {
  background-color: #0a5f91;
}

:deep(.ant-input:focus),
:deep(.ant-input-focused) {
  border-color: #0081c8 !important;
  box-shadow: 0 0 0 2px rgba(0, 129, 200, 0.12) !important;
}
</style>