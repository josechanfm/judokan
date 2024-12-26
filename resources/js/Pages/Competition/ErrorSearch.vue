<template>
  <WebLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">比賽報名查詢</h2>
    </template>
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div
        class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg p-4 flex flex-col gap-6"
      >
        <div class="text-center font-bold text-2xl">查詢失敗，請確認資料是否有錯誤</div>
        <div class="text-center">
          <a-button @click="returnBack()">返回查詢頁面</a-button>
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
    returnBack() {
      Inertia.get(route("competitions.searchForm", this.competition.id));
    },
  },
};
</script>
