<template>
  <WebLayout title="Dashboard">
    <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
      <div class="mb-4 flex items-center gap-3">
        <span class="h-6 w-1.5 rounded-full bg-[#22a45d]"></span>
        <h2 class="text-xl font-bold tracking-wide text-gray-800">
          {{ $t("event_registration") }}
        </h2>
        <span
          v-if="forms && forms.length"
          class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-[#22a45d]"
        >
          {{ forms.length }} 項
        </span>
      </div>

      <div v-if="forms && forms.length" class="grid gap-4 lg:grid-cols-2">
        <article
          v-for="form in forms"
          :key="form.id"
          class="group card-judo flex flex-col transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"
        >
          <!-- 腰帶色條 -->
          <span class="block h-1 bg-gradient-to-r from-[#22a45d] to-[#17713f]"></span>

          <div class="flex flex-1 flex-col p-5">
            <div class="flex items-start gap-3">
              <span
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-lg text-[#22a45d] ring-1 ring-emerald-100"
              >
                <form-outlined />
              </span>
              <div class="min-w-0 flex-1">
                <h3
                  class="truncate text-lg font-bold text-gray-800 transition group-hover:text-[#22a45d]"
                >
                  {{ form.title || form.name }}
                </h3>
                <p v-if="form.organization" class="truncate text-sm text-gray-500">
                  {{ form.organization.abbr }}
                </p>
              </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
              <span
                v-if="form.require_login"
                class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-100"
              >
                <lock-outlined class="mr-1.5" />
                需要登入
              </span>
              <span
                v-else
                class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100"
              >
                公開填寫
              </span>
              <span
                v-if="form.for_member"
                class="inline-flex items-center rounded-md bg-sky-50 px-2.5 py-1 text-xs font-medium text-[#0081C8] ring-1 ring-sky-100"
              >
                只供會員
              </span>
            </div>

            <p
              v-if="plainText(form.description)"
              class="mt-3 line-clamp-2 text-sm leading-6 text-gray-500"
            >
              {{ plainText(form.description) }}
            </p>

            <div class="mt-auto flex flex-wrap items-center gap-2 pt-5">
              <button type="button" class="btn-judo-success" @click="toApply(form)">
                立即填寫
              </button>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="card-judo py-16">
        <a-empty description="暫無活動" />
      </div>
    </div>
  </WebLayout>
</template>

<script>
import WebLayout from "@/Layouts/WebLayout.vue";
import { Inertia } from "@inertiajs/inertia";
import { message } from "ant-design-vue";
import { FormOutlined, LockOutlined } from "@ant-design/icons-vue";

export default {
  components: {
    WebLayout,
    FormOutlined,
    LockOutlined,
  },
  props: ["forms"],
  methods: {
    // description 是 HTML（其他頁用 v-html 顯示），這裡去標籤後截成純文字預覽
    plainText(html) {
      return (html || "").replace(/<[^>]+>/g, "").trim();
    },
    toApply(form) {
      // FIXME: props.user 對訪客也是 {}（truthy），所以這個判斷目前不會擋下任何人。
      // 要真正實作「需要登入」請改用 this.$page.props.user.id（WebLayout 用的是這個）。
      if (form.require_login == 0 || this.$page.props.user) {
        Inertia.get(route("forms.show", form.id));
      } else {
        message.warning("此活動需要登入後才能填寫");
      }
    },
  },
};
</script>


<style>
.fade-in-out {
  opacity: 0;
  animation: fade-in 1s ease-in forwards, fade-out 4s 1s ease-out forwards;
}
</style>