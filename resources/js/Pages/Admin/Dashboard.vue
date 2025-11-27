<template>
  <AdminLayout title="Dashboard">
    <template #header>
      <a-page-header title="行政後台管理" class="px-0" />
    </template>

    <div class="py-6">
      <div class="sm:px-6 lg:px-8">
        <!-- 統計卡片區域 -->
        <a-row :gutter="[16, 16]" class="mb-8">
          <!-- 屬會數量卡片 -->
          <a-col :xs="24" :sm="12" :lg="6">
            <a-card
              class="shadow-sm border-l-4 border-l-blue-500 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-500 mb-2">屬會數量</p>
                  <p class="text-3xl font-bold text-gray-900">
                    {{ stats.organizations_count }}
                  </p>
                  <p class="text-xs text-gray-400 mt-1">總屬會數量</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-full">
                  <HomeOutlined class="text-blue-500 text-xl" />
                </div>
              </div>
            </a-card>
          </a-col>

          <!-- 會員數量卡片 -->
          <a-col :xs="24" :sm="12" :lg="6">
            <a-card
              class="shadow-sm border-l-4 border-l-green-500 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-500 mb-2">會員數量</p>
                  <p class="text-3xl font-bold text-gray-900">
                    {{ stats.members_count }}
                  </p>
                  <p class="text-xs text-gray-400 mt-1">總會員人數</p>
                </div>
                <div class="p-3 bg-green-50 rounded-full">
                  <TeamOutlined class="text-green-500 text-xl" />
                </div>
              </div>
            </a-card>
          </a-col>

          <!-- 用戶數量卡片 -->
          <a-col :xs="24" :sm="12" :lg="6">
            <a-card
              class="shadow-sm border-l-4 border-l-purple-500 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-500 mb-2">用戶數量</p>
                  <p class="text-3xl font-bold text-gray-900">
                    {{ stats.users_count }}
                  </p>
                  <p class="text-xs text-gray-400 mt-1">系統用戶總數</p>
                </div>
                <div class="p-3 bg-purple-50 rounded-full">
                  <UserOutlined class="text-purple-500 text-xl" />
                </div>
              </div>
            </a-card>
          </a-col>

          <!-- 平均會員數卡片 -->
          <a-col :xs="24" :sm="12" :lg="6">
            <a-card
              class="shadow-sm border-l-4 border-l-orange-500 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-500 mb-2">平均會員數</p>
                  <p class="text-3xl font-bold text-gray-900">
                    {{
                      stats.organizations_count > 0
                        ? Math.round(stats.members_count / stats.organizations_count)
                        : 0
                    }}
                  </p>
                  <p class="text-xs text-gray-400 mt-1">每屬會平均人數</p>
                </div>
                <div class="p-3 bg-orange-50 rounded-full">
                  <BarChartOutlined class="text-orange-500 text-xl" />
                </div>
              </div>
            </a-card>
          </a-col>
        </a-row>

        <!-- 屬會管理區域 -->
        <a-card
          title="屬會管理"
          class="shadow-sm"
          :extra="organizations.length > 0 ? h(manageButton) : null"
        >
          <template #title>
            <div class="flex items-center">
              <HomeOutlined class="text-blue-500 mr-2" />
              <span class="text-lg font-semibold">屬會管理</span>
            </div>
          </template>

          <!-- 屬會列表 -->
          <a-table
            v-if="organizations.length > 0"
            :dataSource="organizations"
            :columns="columns"
            :pagination="false"
            rowKey="id"
            class="mt-4"
          >
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'members_count'">
                <a-tag color="blue"> {{ record.members_count || 0 }} 人 </a-tag>
              </template>
              <template v-else-if="column.key === 'created_at'">
                <span class="text-gray-600">
                  {{ formatDate(record.created_at) }}
                </span>
              </template>
              <template v-else-if="column.key === 'full_name'">
                <span class="font-medium text-gray-900">
                  {{ record.full_name }}
                </span>
              </template>
            </template>
          </a-table>

          <!-- 無屬會時的提示 -->
          <a-empty v-else :image="simpleImage" description="暫無屬會">
            <template #description>
              <div class="text-center py-4">
                <p class="text-gray-900 font-medium text-base mb-1">暫無屬會</p>
                <p class="text-gray-500 text-sm">目前沒有任何屬會資料</p>
              </div>
            </template>
            <a-button type="primary" :href="route('admin.organizations.index')">
              創建屬會
            </a-button>
          </a-empty>
        </a-card>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { h } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
  HomeOutlined,
  TeamOutlined,
  UserOutlined,
  BarChartOutlined,
  RightOutlined,
} from "@ant-design/icons-vue";
import { Empty } from "ant-design-vue";

// Props
const props = defineProps({
  organizations: Array,
  stats: Object,
});

// Empty 組件圖像
const simpleImage = Empty.PRESENTED_IMAGE_SIMPLE;

// 表格列定義
const columns = [
  {
    title: "屬會名稱",
    dataIndex: "full_name",
    key: "full_name",
  },
  {
    title: "會員數量",
    key: "members_count",
    width: 120,
    align: "center",
  },
  {
    title: "創建時間",
    key: "created_at",
    width: 200,
  },
];

// 管理按鈕組件
const manageButton = () =>
  h(
    "a",
    {
      href: route("admin.organizations.index"),
      class:
        "inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium",
    },
    ["管理屬會", h(RightOutlined, { class: "ml-1 text-xs" })]
  );

// 方法
const formatDate = (dateString) => {
  if (!dateString) return "-";
  const date = new Date(dateString);
  return date.toLocaleDateString("zh-TW", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
};

// 掛載後的生命週期
import { onMounted } from "vue";
onMounted(() => {
  console.log("Dashboard loaded with stats:", props.stats);
});
</script>

<style scoped>
/* 自定義樣式 */
:deep(.ant-card-head) {
  @apply border-b border-gray-200;
}

:deep(.ant-card-head-title) {
  @apply font-semibold text-gray-900 text-lg;
}

:deep(.ant-table-thead > tr > th) {
  @apply bg-gray-50 text-gray-500 font-medium uppercase tracking-wider text-xs;
}

:deep(.ant-table-tbody > tr:hover > td) {
  @apply bg-blue-50;
}

:deep(.ant-card-body) {
  @apply p-6;
}
</style>
