<script setup>
import WebLayout from "@/Layouts/WebLayout.vue";
import {
  TrophyOutlined,
  FormOutlined,
  FlagOutlined,
  CalendarOutlined,
  RightOutlined,
} from "@ant-design/icons-vue";

defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
  laravelVersion: String,
  phpVersion: String,
  isMember: Boolean,
  isOrganizer: Boolean,
  articles: Array,
  competitions: Array,
  forms: Array,
});
</script>

<template>
  <WebLayout title="Dashboard">
    <!-- Hero：道場藏青底 + 榻榻米格紋 + 直書「柔道」浮水印 + 腰帶色階 -->
    <div
      class="judo-hero relative flex items-center overflow-hidden rounded-xl shadow-lg lg:h-96"
    >
      <div class="judo-tatami absolute inset-0"></div>
      <span class="judo-kanji absolute top-1/2 -right-4 -translate-y-1/2 select-none"
        >柔道</span
      >

      <div class="relative z-10 ml-5 py-10 lg:ml-20 lg:w-3/5 lg:py-0">
        <span class="mb-3 block h-1 w-12 rounded-full bg-[#b4e600]"></span>
        <h2 class="text-3xl font-bold tracking-wide text-white lg:text-4xl">這是什麼?</h2>
        <p class="mt-4 leading-7 tracking-wider text-sky-100/90 lg:text-lg">
          澳門柔道協會會員系統是為全澳柔道愛好者提供的綜合服務平台。作為會員，您可以建立您的個人會員卡和個人資料，這使您可以連接與其他柔道運動員一起展示您在這項運動中的成就。
        </p>
      </div>

      <div class="judo-belt absolute inset-x-0 bottom-0"></div>
    </div>

    <!-- 最新賽事報名（藍帶） -->
    <section
      class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/70"
    >
      <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
        <span class="h-6 w-1.5 rounded-full bg-[#0081C8]"></span>
        <trophy-outlined class="text-lg text-[#0081C8]" />
        <h2 class="text-xl font-bold tracking-wide text-gray-800">最新賽事報名</h2>
        <inertia-link
          :href="route('competitions.index')"
          class="ml-auto inline-flex items-center text-sm text-gray-500 transition hover:text-[#0081C8]"
        >
          全部賽事 <right-outlined class="ml-1 text-xs" />
        </inertia-link>
      </div>

      <ul v-if="competitions && competitions.length" class="divide-y divide-gray-100">
        <li v-for="item in competitions" :key="item.id">
          <inertia-link
            :href="route('competitions.show', item.id)"
            class="group flex items-center gap-4 px-5 py-4 transition hover:bg-sky-50/70"
          >
            <span
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-[#0081C8] ring-1 ring-sky-100"
            >
              <flag-outlined />
            </span>
            <span class="min-w-0 flex-1">
              <span
                class="block truncate font-medium text-gray-800 transition group-hover:text-[#0081C8]"
              >
                {{ item.title_zh }}
              </span>
              <span class="mt-0.5 flex items-center text-sm text-gray-500">
                <calendar-outlined class="mr-1 text-xs" />
                {{ item.start_date }} ~ {{ item.end_date }}
              </span>
            </span>
            <right-outlined
              class="text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-[#0081C8]"
            />
          </inertia-link>
        </li>
      </ul>
      <div v-else class="px-5 py-10">
        <a-empty description="暫無賽事" />
      </div>
    </section>

    <!-- 最新活動報名（綠帶） -->
    <section
      class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/70"
    >
      <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
        <span class="h-6 w-1.5 rounded-full bg-[#22a45d]"></span>
        <form-outlined class="text-lg text-[#22a45d]" />
        <h2 class="text-xl font-bold tracking-wide text-gray-800">最新活動報名</h2>
        <inertia-link
          :href="route('forms.index')"
          class="ml-auto inline-flex items-center text-sm text-gray-500 transition hover:text-[#22a45d]"
        >
          全部活動 <right-outlined class="ml-1 text-xs" />
        </inertia-link>
      </div>

      <ul v-if="forms && forms.length" class="divide-y divide-gray-100">
        <li v-for="item in forms" :key="item.id">
          <inertia-link
            :href="route('forms.show', item.id)"
            class="group flex items-center gap-4 px-5 py-4 transition hover:bg-emerald-50/70"
          >
            <span
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-[#22a45d] ring-1 ring-emerald-100"
            >
              <form-outlined />
            </span>
            <span class="min-w-0 flex-1">
              <span
                class="block truncate font-medium text-gray-800 transition group-hover:text-[#22a45d]"
              >
                {{ item.title }}
              </span>
              <span
                v-if="item.organization"
                class="mt-1 inline-block rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-500"
              >
                {{ item.organization.abbr }}
              </span>
            </span>
            <right-outlined
              class="text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-[#22a45d]"
            />
          </inertia-link>
        </li>
      </ul>
      <div v-else class="px-5 py-10">
        <a-empty description="暫無活動" />
      </div>
    </section>
  </WebLayout>
</template>

<style scoped>
/* 道場藏青底：深藍 → 奧運藍，右上角帶一抹藍光 */
.judo-hero {
  background: radial-gradient(
      130% 130% at 100% 0%,
      rgba(0, 129, 200, 0.55) 0%,
      rgba(0, 129, 200, 0) 55%
    ),
    linear-gradient(135deg, #060a22 0%, #101c4d 45%, #0b3d78 100%);
}

/* 榻榻米 2:1 格紋，往下淡出 */
.judo-tatami {
  background-image: linear-gradient(
      to right,
      rgba(255, 255, 255, 0.07) 1px,
      transparent 1px
    ),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.07) 1px, transparent 1px);
  background-size: 88px 44px;
  -webkit-mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.15));
  mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.15));
}

/* 直書「柔道」浮水印 */
.judo-kanji {
  font-size: 9rem;
  font-weight: 700;
  line-height: 1;
  letter-spacing: 0.1em;
  color: #fff;
  opacity: 0.05;
  writing-mode: vertical-rl;
}

/* 腰帶色階：白・黃・橙・綠・藍・棕・黑 */
.judo-belt {
  height: 4px;
  background: linear-gradient(
    to right,
    #ffffff 0%,
    #ffffff 14.28%,
    #ffd400 14.28%,
    #ffd400 28.57%,
    #ff8a00 28.57%,
    #ff8a00 42.85%,
    #22a45d 42.85%,
    #22a45d 57.14%,
    #0081c8 57.14%,
    #0081c8 71.42%,
    #8b5e34 71.42%,
    #8b5e34 85.71%,
    #111111 85.71%,
    #111111 100%
  );
}
</style>
