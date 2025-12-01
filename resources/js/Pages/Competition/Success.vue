<template>
  <WebLayout title="Dashboard">
    <template #header> </template>
    <div class="py-0">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="p-5 bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <!-- 成功訊息 -->
          <div class="mb-8 text-center">
            <div
              class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4"
            >
              <svg
                class="w-8 h-8 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                ></path>
              </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">報名成功！</h1>
            <p class="text-gray-600">你已成功報名參賽，請妥善保存以下報名資料</p>
          </div>

          <!-- 賽事名稱 -->
          <div class="text-center mb-8">
            <a-typography-title :level="2" class="text-primary-600">
              {{ application.competition.title_zh }}
            </a-typography-title>
          </div>

          <!-- 報名資料表格 -->
          <div class="ant-table ant-table-bordered overflow-x-auto mb-10">
            <div class="ant-table-container">
              <div class="ant-table-content">
                <table id="applicationSuccess" class="w-full">
                  <tbody class="ant-table-tbody">
                    <tr class="ant-table-row">
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("name_zh") }}
                      </td>
                      <td class="ant-table-cell" colspan="3">
                        {{ application.name_zh }}
                      </td>
                    </tr>
                    <tr class="ant-table-row">
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("name_fn") }}
                      </td>
                      <td class="ant-table-cell" colspan="3">
                        {{ application.name_fn }}
                      </td>
                    </tr>
                    <tr class="ant-table-row">
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("gender") }}
                      </td>
                      <td class="ant-table-cell">
                        {{ application.gender == "M" ? "男" : "女" }}
                      </td>
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("dob") }}
                      </td>
                      <td class="ant-table-cell">{{ application.dob }}</td>
                    </tr>
                    <tr class="ant-table-row">
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("email") }}
                      </td>
                      <td class="ant-table-cell">{{ application.email }}</td>
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("mobile_number") }}
                      </td>
                      <td class="ant-table-cell">{{ application.mobile }}</td>
                    </tr>
                    <tr class="ant-table-row">
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("belt_rank") }}
                      </td>
                      <td class="ant-table-cell">
                        {{
                          belt_ranks.find((b) => b.rankCode == application.belt_rank)
                            .name_zh
                        }}
                      </td>
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("role") }}
                      </td>
                      <td class="ant-table-cell">
                        {{
                          application.competition.roles.find(
                            (r) => r.value == application.role
                          ).label
                        }}
                      </td>
                    </tr>
                    <tr v-if="application.role == 'athlete'" class="ant-table-row">
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("category") }}
                      </td>
                      <td class="ant-table-cell">
                        {{
                          application.competition.categories_weights.find(
                            (x) => x.code == application.category
                          ).name
                        }}
                      </td>
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("weight") }}
                      </td>
                      <td class="ant-table-cell">{{ application.weight }}</td>
                    </tr>
                    <tr v-if="application.role == 'staff'" class="ant-table-row">
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("staff_options") }}
                      </td>
                      <td class="ant-table-cell" colspan="3">
                        <ol class="list-decimal pl-5">
                          <li v-for="option in application.staff_options">
                            {{
                              application.competition.staff_options.find(
                                (o) => o.value == option
                              ).label
                            }}
                          </li>
                        </ol>
                      </td>
                    </tr>
                    <tr v-if="application.role == 'referee'" class="ant-table-row">
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("referee_options") }}
                      </td>
                      <td class="ant-table-cell" colspan="3">
                        {{
                          application.competition.referee_options.find(
                            (o) => o.value == application.referee_options
                          ).label
                        }}
                      </td>
                    </tr>
                    <tr v-if="application.avatar" class="ant-table-row">
                      <td class="ant-table-cell font-medium bg-gray-50">
                        {{ $t("avatar") }}
                      </td>
                      <td class="ant-table-cell" colspan="3">
                        <img
                          :src="application.avatar_url"
                          width="200"
                          class="rounded-lg shadow"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- 重要提示 -->
          <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg
                  class="h-5 w-5 text-yellow-400"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-yellow-700">
                  <strong>重要提示：</strong>建議立即下載報名表作為記錄，並妥善保存。
                </p>
              </div>
            </div>
          </div>

          <!-- 按鈕區域 -->
          <div
            class="flex flex-col sm:flex-row justify-between items-center gap-6 py-8 border-t"
          >
            <a
              :href="route('/')"
              class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 transition-colors duration-200"
            >
              <svg
                class="w-5 h-5 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18"
                ></path>
              </svg>
              返回主頁
            </a>

            <div class="flex flex-col sm:flex-row items-center gap-4">
              <a
                :href="
                  '/competition/application/' + application.id + '/success?format=pdf'
                "
                download
                class="inline-flex items-center px-8 py-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg hover:shadow-xl"
              >
                <svg
                  class="w-6 h-6 mr-3"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                  ></path>
                </svg>
                下載報名表 (PDF)
              </a>
            </div>

            <a
              :href="route('competitions.index')"
              class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 transition-colors duration-200"
            >
              <svg
                class="w-5 h-5 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                ></path>
              </svg>
              查看其他賽事
            </a>
          </div>
        </div>
      </div>
    </div>
  </WebLayout>
</template>

<script>
import WebLayout from "@/Layouts/WebLayout.vue";
import dayjs from "dayjs";
import { message } from "ant-design-vue";
import { Modal } from "ant-design-vue";
import { Inertia } from "@inertiajs/inertia";

export default {
  components: {
    WebLayout,
    dayjs,
    message,
    Modal,
  },
  props: ["organizations", "competition", "application", "belt_ranks"],
  data() {
    return {};
  },
  mounted() {
    window.onbeforeunload = function (e) {
      Inertia.get(route("competitions.searchForm", this.competition.id));
      return false;
    };
  },
  methods: {
    printApplication() {
      // 創建打印樣式
      const printStyle = `
        <style>
          @media print {
            body * {
              visibility: hidden;
            }
            #applicationSuccess, #applicationSuccess * {
              visibility: visible;
            }
            #applicationSuccess {
              position: absolute;
              left: 0;
              top: 0;
              width: 100%;
              font-size: 14px;
            }
            .no-print {
              display: none !important;
            }
          }
        </style>
      `;

      // 獲取表格內容
      const tableContent = document.getElementById("applicationSuccess").outerHTML;
      const title = `<h2 style="text-align: center; margin-bottom: 20px;">${this.application.competition.title_zh} - 報名表</h2>`;
      const footer = `<div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">報名編號：${
        this.application.id
      } | 打印日期：${new Date().toLocaleDateString()}</div>`;

      // 創建打印窗口
      const printWindow = window.open("", "_blank");
      printWindow.document.write(`
        <html>
          <head>
            <title>報名表 - ${this.application.competition.title_zh}</title>
            ${printStyle}
          </head>
          <body>
            ${title}
            ${tableContent}
            ${footer}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.focus();

      // 延遲打印以確保內容加載完成
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 250);
    },
  },
};
</script>

<style scoped>
#pure-html {
  all: initial;
}

#pure-html * {
  all: revert;
}

/* 表格樣式優化 */
#applicationSuccess {
  width: 100%;
  border-collapse: collapse;
}

#applicationSuccess td {
  padding: 12px 16px;
  border: 1px solid #e5e7eb;
}

#applicationSuccess tr:nth-child(even) {
  background-color: #f9fafb;
}

/* 響應式調整 */
@media (max-width: 640px) {
  .ant-table {
    font-size: 14px;
  }

  #applicationSuccess td {
    padding: 8px 12px;
  }
}
</style>
