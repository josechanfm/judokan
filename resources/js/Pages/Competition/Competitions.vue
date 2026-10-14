<template>
  <WebLayout title="Dashboard">
    <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
      <div class="mb-4 flex items-center gap-3">
        <span class="h-6 w-1.5 rounded-full bg-[#0081C8]"></span>
        <h2 class="text-xl font-bold tracking-wide text-gray-800">賽事報名</h2>
        <span
          v-if="competitions && competitions.length"
          class="rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-medium text-[#0081C8]"
        >
          {{ competitions.length }} 項
        </span>
      </div>

      <div v-if="competitions && competitions.length" class="grid gap-4 lg:grid-cols-2">
        <article
          v-for="competition in competitions"
          :key="competition.id"
          class="group card-judo flex flex-col transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"
        >
          <!-- 腰帶色條 -->
          <span class="block h-1 bg-gradient-to-r from-[#0081C8] to-[#0b3d78]"></span>

          <div class="flex flex-1 flex-col p-5">
            <div class="flex items-start gap-3">
              <span
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-lg text-[#0081C8] ring-1 ring-sky-100"
              >
                <trophy-outlined />
              </span>
              <div class="min-w-0 flex-1">
                <h3
                  class="truncate text-lg font-bold text-gray-800 transition group-hover:text-[#0081C8]"
                >
                  {{ competition.title_zh || competition.title_fn }}
                </h3>
                <p
                  v-if="competition.title_zh && competition.title_fn"
                  class="truncate text-sm text-gray-500"
                >
                  {{ competition.title_fn }}
                </p>
              </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
              <span
                class="inline-flex items-center rounded-md bg-gray-50 px-2.5 py-1 text-gray-600 ring-1 ring-gray-100"
              >
                <calendar-outlined class="mr-1.5 text-xs text-[#0081C8]" />
                {{ competition.start_date }} ~ {{ competition.end_date }}
              </span>
              <span
                v-if="competition.for_member"
                class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-100"
              >
                <safety-certificate-outlined class="mr-1.5" />
                只限會員
              </span>
              <span
                v-else
                class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100"
              >
                公開報名
              </span>
            </div>

            <p
              v-if="plainText(competition.brief)"
              class="mt-3 line-clamp-2 text-sm leading-6 text-gray-500"
            >
              {{ plainText(competition.brief) }}
            </p>

            <div class="mt-auto flex flex-wrap items-center gap-2 pt-5">
              <button
                type="button"
                class="btn-judo-primary"
                @click="toApply(competition)"
              >
                立即報名
              </button>
              <button
                type="button"
                class="btn-judo-outline"
                @click="toSearch(competition)"
              >
                查詢報名
              </button>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="card-judo py-16">
        <a-empty description="暫無賽事" />
      </div>
    </div>
  </WebLayout>
</template>

<script>
import WebLayout from "@/Layouts/WebLayout.vue";
import { Inertia } from "@inertiajs/inertia";
import {
  TrophyOutlined,
  CalendarOutlined,
  SafetyCertificateOutlined,
} from "@ant-design/icons-vue";

export default {
  components: {
    WebLayout,
    TrophyOutlined,
    CalendarOutlined,
    SafetyCertificateOutlined,
  },
  props: ["competitions"],
  methods: {
    // brief / description 這類欄位可能含 HTML，先去除標籤再顯示
    plainText(html) {
      return (html || "").replace(/<[^>]+>/g, "").trim();
    },
    toApply(competition) {
      // 註：Competition 沒有 require_login 欄位，而且 HandleInertiaRequests 只用
      // `user.roles` / `user.permissions` 產生 props.user，訪客拿到的也是 {}（truthy）。
      // 所以原本的 `record.require_login == 0 || $page.props.user` 等同「一律放行」，
      // 這裡維持同樣行為，不動報名權限。
      Inertia.get(route("competitions.show", competition.id));
    },
    toSearch(competition) {
      Inertia.get(route("competitions.searchForm", competition.id));
    },
  },
};
</script>
