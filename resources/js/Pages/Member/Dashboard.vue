<script>
import MemberLayout from "@/Layouts/MemberLayout.vue";
import ArticleList from "@/Components/ArticleList.vue";
import axios from "axios";
import QRCodeVue3 from "qrcode-vue3";

export default {
  components: {
    MemberLayout,
    ArticleList,
    QRCodeVue3,
  },
  props: ["member", "articles", "card_style",'organization','forms'],
  data() {
    return {
      qrcode: "",
      interval: 0,
      features: [
        {
          image: "images/features_public.png",
          title: "公眾",
          description:"主要公開內容，無需會員登入。包括網站介紹及所有公開信息，及連結等。",
          tags: ["#通告"],
          link: "/",
        },
        {
          image: "/images/features_forms.png",
          title: "表格",
          description:"所有線上表格，系統會跟據登入狀態及屬會顯示所填的線上表格內容。",
          tags: ["#報名"],
          link: "forms",
        },
        {
          image: "images/features_competition.png",
          title: "賽事",
          description:"與表格相類此，但只適用於賽事報名。同樣不同用戶可能顯示出不同內容。",
          tags: ["#報名", "#比賽"],
          link: "competitions",
        },
        {
          image: "images/features_other.png",
          title: "其它",
          description: "包括線上學習、分享、留言、意見回饋等功能，會逐步開放供用戶使用。",
          tags: ["#學習", "#留言"],
        },
      ],
      data: [
        {
          title: "News",
          url: "./",
          content: "Competition ABC is now open for registration",
        },
        {
          title: "Ant Design Title 2",
          url: "",
          content: "Competition ABC is now open for registration",
        },
        {
          title: "Ant Design Title 3",
          url: "",
          content: "Competition ABC is now open for registration",
        },
        {
          title: "Ant Design Title 4",
          url: "",
          content: "Competition ABC is now open for registration",
        },
      ],
      showQrcode: false,
    };
  },
  created() {
    
  },
  mounted() {},
  methods: {
    getQrcode() {
      axios.get(route("member.getQrcode")).then((response) => {
        this.qrcode = response.data;
      });
    },
    onShowQrcode() {
      this.showQrcode = !this.showQrcode;
      if (this.showQrcode) {
        this.getQrcode();
        this.interval = setInterval(() => {
          this.getQrcode();
        }, 10000);
      } else {
        clearInterval(this.interval);
      }
    },
  },
};
</script>

<template>
  <MemberLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{$t('membership')}}
      </h2>
    </template>
    <div class="container mx-auto">
      <div class="flex flex-col-reverse md:flex-row gap-6">
        <div class="flex-auto">

          <!-- Feature Section -->
          <div class="container mx-auto mt-5 bg-white rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 py-3 px-2">
              <template v-for="feature in features">
                <a :href="feature.link">
                  <div class="gutter-row">
                    <div class="max-w rounded overflow-hidden shadow-lg">
                      <img class="w-full" alt="Use any sample image here..." :src="feature.image">
                      <div class="px-6 py-4">
                        <div class="font-bold text-xl mb-2">{{ feature.title }}</div>
                        <p class="text-gray-700 text-base pl-3">
                          {{ feature.description }}
                        <!-- <ol class="list-disc">
                          <li v-for="form in forms">
                            <inertia-link :href="route('forms.show', form.id)">{{ form.title }}</inertia-link>
                          </li>
                        </ol> -->
                        </p>
                      </div>
                      <div class="px-6 py-4">
                        <span v-for="tag in feature.tags"
                          class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">{{
                            tag }}</span>
                      </div>
                    </div>
                  </div>
                </a>
              </template>
            </div>
          </div>
          <!-- Feature Section end-->

          <!-- News Section-->
          <div class="container mx-auto pt-5">
            <div class="bg-white relative shadow rounded-lg pl-5">
              <ArticleList :articles="articles"/>
            </div>
          </div>
          <!-- News Section end-->
        </div>

        <div class="flex-none w-[400px]">
          <div class="container mx-auto pt-5">
            <div class="bg-white relative shadow rounded-lg">
              <!-- QRcode -->
              <div class="flex flex-col justify-center items-center" v-if="showQrcode">
                <div>
                  <QRCodeVue3 :key="qrcode" v-bind:value="qrcode" :image="'/images/' + card_style['logo']" :dotsOptions="{
                    type: 'dots',
                    color: '#26249a',
                    gradient: {
                      type: 'linear',
                      rotation: 0,
                      colorStops: [
                        { offset: 0, color: '#26249a' },
                        { offset: 1, color: '#26249a' },
                      ],
                    },
                  }" :cornersSquareOptions="{
                    type: 'square',
                    color: '#e00404'
                  }" :cornersDotOptions="{
                    color: '#e00404'
                  }" />
                </div>
              </div>
              <!-- card start -->
              <div class="mx-auto relative py-4 w-96 hover:scale-105 transform transition-transform mb-4">
                <div :style="card_style['font_style']"
                  class="absolute z-50 h-52 flex rounded-lg flex-col py-3 px-8 shadow-xl text-sm w-full"
                  @click="onShowQrcode">
                  <div class="flex flex-col w-xl">
                    <div class="flex justify-center">
                      <div class="text-lg font-bold">{{ organization.full_name }}...</div>
                    </div>
                    <div class="flex">
                      <div class="flex flex-col flex-auto gap-1">
                        <div class="">Name: </div>
                        <div class="mb-2">{{ member.display_name }}</div>
                        <div class="">Number: </div>
                        <div class="font-sans mb-2">{{ member.member_number }}</div>
                      </div>
                      <div class="flex text-right">
                        <img class="w-20 h-20" :src="'/images/' + card_style['logo']" />
                      </div>
                    </div>
                  </div>
                  <div class="flex text-xs">
                    <div class="flex flex-col gap-1 flex-auto">
                      <div class="">Valid at:</div>
                      <div class="font-sans text-base">{{ member.valid_at }}</div>
                    </div>
                    <div class="flex flex-col gap-1 flex-auto">
                      <div class="">Expired at:</div>
                      <div class="font-sans text-base">{{ member.expired_at }}</div>
                    </div>
                  </div>
                </div>
                <img class="relative object-cover w-96 h-52 rounded-lg z-0" :src="'/images/' + card_style['background']"/>
              </div>
              <!-- card end -->

              <div class="mt-16">
                <h1 class="font-bold text-center text-3xl text-gray-900">
                  {{ member.name_zh }} {{ member.name_fn }}
                </h1>
                <p class="text-center text-sm text-gray-400 font-medium">

                  {{ organization.full_name }}
                </p>
                <p>
                  <span> </span>
                </p>
                <div class="my-5 px-6">
                  <a href="#"
                    class="text-gray-200 block rounded-lg text-center font-medium leading-6 px-6 py-3 bg-gray-900 hover:bg-black hover:text-white">Connect
                    with <span class="font-bold">{{ member.email }}</span></a>
                </div>
                <div class="flex justify-between items-center my-5 px-6">
                  <a href=""
                    class="text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded transition duration-150 ease-in font-medium text-sm text-center w-full py-3">Facebook</a>
                  <a href=""
                    class="text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded transition duration-150 ease-in font-medium text-sm text-center w-full py-3">Twitter</a>
                  <a href=""
                    class="text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded transition duration-150 ease-in font-medium text-sm text-center w-full py-3">Instagram</a>
                  <a href=""
                    class="text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded transition duration-150 ease-in font-medium text-sm text-center w-full py-3">Email</a>
                </div>
                <div class="w-full">
                  <div v-if="member.organizations.length > 1">
                    <h3 class="font-medium text-gray-900 text-left px-6">Your Organizations</h3>
                    <div class="mt-5 w-full flex flex-col items-center overflow-hidden text-sm">
                      <template v-for="organization in member.organizations">
                        <a href="#"
                          class="w-full border-t border-gray-100 text-gray-600 py-4 pl-6 pr-3 w-full block hover:bg-gray-100 transition duration-150">
                          <img src="https://avatars0.githubusercontent.com/u/35900628?v=4" alt=""
                            class="rounded-full h-6 shadow-md inline-block mr-2" />
                          {{ organization.abbr }} - {{ organization.full_name }}
                          <span class="text-gray-500 text-xs">24 min ago</span>
                        </a>
                      </template>
                    </div>
                  </div>

                  <h3 class="font-medium text-gray-900 text-left px-6">Recent updates</h3>
                  <div class="mt-5 w-full flex flex-col items-center overflow-hidden text-sm">
                    <template v-for="organization in member.organizations">
                      <a href="#"
                        class="w-full border-t border-gray-100 text-gray-600 py-4 pl-6 pr-3 w-full block hover:bg-gray-100 transition duration-150">
                        <img src="https://avatars0.githubusercontent.com/u/35900628?v=4" alt=""
                          class="rounded-full h-6 shadow-md inline-block mr-2" />
                        {{ organization.abbr }} - {{ organization.full_name }}
                        <span class="text-gray-500 text-xs">24 min ago</span>
                      </a>
                    </template>

                    <template v-for="portfolio in member.portfolios">
                      <a href="#"
                        class="w-full border-t border-gray-100 text-gray-600 py-4 pl-6 pr-3 w-full block hover:bg-gray-100 transition duration-150">
                        <img src="https://avatars0.githubusercontent.com/u/35900628?v=4" alt=""
                          class="rounded-full h-6 shadow-md inline-block mr-2" />
                        {{ portfolio.title }} - {{ portfolio.description }}
                        <span class="text-gray-500 text-xs">{{ portfolio.start_date }}</span>
                      </a>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
  </MemberLayout>
</template>

<style scope>
#pure-html {
  all: initial;
}

#pure-html * {
  all: revert;
}
</style>