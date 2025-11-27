<template>
  <WebLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">表格例表</h2>
    </template>

    <div class="py-0">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="p-5 bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div
            v-if="competition.media.find((m) => m.collection_name == 'competitionBanner')"
          >
            <img
              :src="
                competition.media.find((m) => m.collection_name == 'competitionBanner')
                  .original_url
              "
              style="width: 100%"
            />
          </div>
          <a-typography-title :level="3" class="text-center">{{
            competition.title_zh
          }}</a-typography-title>
          <div id="pure-html">
            <div v-html="competition.description" />
          </div>

          <ol>
            <li
              :key="file.id"
              v-for="file in competition.media.filter(
                (m) => m.collection_name == 'competitionAttachment'
              )"
            >
              <a :href="file.original_url" target="_blank" download>{{
                file.file_name
              }}</a>
            </li>
          </ol>
          <a-typography-title :level="4"
            >{{ $t("application_fee") }}： MOP${{ competition.fee }}</a-typography-title
          >

          <a-form
            :model="application"
            v-bind="layout"
            name="nest-messages"
            :validate-messages="validateMessages"
            layout="vertical"
            :rules="rules"
            @finish="onFinish"
          >
            <a-form-item
              :label="$t('organization_or_school')"
              name="organization_id"
              v-if="member == null"
            >
              <a-select
                v-model:value="application.organization_id"
                :options="organizations"
                :field-names="{ value: 'id', label: 'title' }"
              />
            </a-form-item>
            <a-form-item :label="$t('name_zh')" name="name_zh">
              <a-input
                type="input"
                @input="
                  () => {
                    this.application.name_zh = this.application.name_zh.toUpperCase();
                  }
                "
                v-model:value="application.name_zh"
              />
            </a-form-item>
            <a-form-item :label="$t('name_fn')" name="name_fn">
              <a-input
                type="input"
                @input="
                  () => {
                    this.application.name_fn = this.application.name_fn.toUpperCase();
                  }
                "
                v-model:value="application.name_fn"
              />
            </a-form-item>
            <a-form-item :label="$t('id_num')" name="id_num">
              <a-input type="input" v-model:value="application.id_num" />
            </a-form-item>
            <a-form-item
              :label="$t('belt_rank')"
              name="belt_rank"
              :rules="[
                { required: ['athlete', 'referee', 'coach'].includes(application.role) },
              ]"
            >
              <a-select
                v-model:value="application.belt_rank"
                :options="belt_ranks"
                :field-names="{ value: 'rankCode', label: 'name_zh' }"
              />
            </a-form-item>
            <a-form-item :label="$t('dob')" name="dob">
              <a-date-picker
                v-model:value="application.dob"
                :format="dateFormat"
                :valueFormat="dateFormat"
                :disabledDate="disabledDate"
                @change="onDobChange"
              />
            </a-form-item>
            <a-form-item :label="$t('age')" v-if="application.age">
              <span class="text-lg font-semibold">{{ application.age }} 歲</span>
              <span v-if="eligibleCategories.length > 0" class="ml-2 text-green-600">
                (符合 {{ eligibleCategories.length }} 個組別)
              </span>
              <span v-else class="ml-2 text-red-600"> (不符合任何組別的年齡要求) </span>
            </a-form-item>
            <a-form-item :label="$t('gender')" name="gender">
              <a-radio-group v-model:value="application.gender" @change="onGenderChange">
                <a-radio value="M">{{ $t("male") }}</a-radio>
                <a-radio value="F">{{ $t("female") }}</a-radio>
              </a-radio-group>
            </a-form-item>
            <a-form-item :label="$t('email')" name="email">
              <a-input type="input" v-model:value="application.email" />
            </a-form-item>
            <a-form-item :label="$t('mobile_number')" name="mobile">
              <a-input type="input" v-model:value="application.mobile" />
            </a-form-item>
            <a-form-item :label="$t('role')" name="role" v-if="application.gender">
              <a-radio-group v-model:value="application.role">
                <a-radio
                  v-for="role in competition.roles"
                  :value="role.value"
                  :style="virticalStyle"
                  >{{ role.label }}
                </a-radio>
              </a-radio-group>
            </a-form-item>
            <template v-if="application.role == 'athlete' && application.gender">
              <a-form-item :label="$t('category')" name="category">
                <a-alert
                  v-if="eligibleCategories.length === 0 && application.dob"
                  message="不符合年齡要求"
                  description="根據您的出生日期，您不符合任何組別的年齡要求。"
                  type="error"
                  show-icon
                  class="mb-4"
                />
                <a-radio-group v-model:value="application.category">
                  <a-radio
                    v-for="cat in eligibleCategories"
                    :value="cat.code"
                    :style="virticalStyle"
                    @change="onCategoryChange"
                    :disabled="!isCategoryEligible(cat.code)"
                  >
                    {{ cat.name }} - [{{ cat.description }}]
                    <span v-if="!isCategoryEligible(cat.code)" class="text-red-500 ml-2">
                      (年齡不符合)
                    </span>
                  </a-radio>
                </a-radio-group>
              </a-form-item>
              <a-form-item
                :label="$t('weight')"
                name="weight"
                v-if="application.category"
              >
                <a-radio-group v-model:value="application.weight">
                  <a-radio
                    v-for="cat in competition.weights"
                    :style="virticalStyle"
                    :value="cat.code"
                  >
                    {{ cat.name }}
                  </a-radio>
                </a-radio-group>
              </a-form-item>
            </template>
            <template v-if="application.role == 'referee'">
              <a-form-item
                :label="$t('referee_options')"
                name="referee_options"
                v-if="application.gender"
              >
                <a-radio-group v-model:value="application.referee_options">
                  <a-radio
                    v-for="option in competition.referee_options"
                    :value="option.value"
                    :style="virticalStyle"
                    >{{ option.label }}</a-radio
                  >
                </a-radio-group>
              </a-form-item>
            </template>

            <template v-if="application.role == 'staff'">
              <a-form-item
                :label="$t('staff_options')"
                name="staff_options"
                v-if="application.gender"
              >
                <a-checkbox-group v-model:value="application.staff_options">
                  <a-checkbox
                    v-for="option in competition.staff_options"
                    :value="option.value"
                    :style="virticalStyle"
                    >{{ option.label }}</a-checkbox
                  >
                </a-checkbox-group>
              </a-form-item>
            </template>

            <a-button @click="showCropModal = true">{{
              $t("upload_profile_image")
            }}</a-button>
            <CropperModal
              v-if="showCropModal"
              :minAspectRatioProp="{ width: 8, height: 8 }"
              :maxAspectRatioProp="{ width: 8, height: 8 }"
              @croppedImageData="setCroppedImageData"
              @showModal="showCropModal = false"
            />
            <div class="flex flex-wrap mt-4 mb-6">
              <div class="w-full md:w-1/2 px-3">
                <div v-if="avatarPreview !== null">
                  <img :src="avatarPreview" />
                </div>
                <div v-else>
                  <img :src="competition.avatar_url" />
                </div>
              </div>
            </div>

            <div class="flex flex-row item-center justify-center">
              <a-button
                type="primary"
                html-type="submit"
                :loading="loading"
                :disabled="
                  eligibleCategories.length === 0 && application.role === 'athlete'
                "
              >
                {{ $t("submit") }}
              </a-button>
            </div>
          </a-form>
        </div>
      </div>
    </div>
    <a-modal
      v-model:open="modal.isOpen"
      :title="modal.title"
      :cancelButtonProps="{ style: 'display:none' }"
      okText="$t{'understand'}"
    >
      <p>Duplicate entry,</p>
      <p>
        If you are Athlete, you may apply at most 2 categories in the same competition.
      </p>
      <p>Or you may apply one role only.</p>
      <p>{{ modal.content }}</p>
    </a-modal>
  </WebLayout>
</template>

<script>
import WebLayout from "@/Layouts/WebLayout.vue";
import dayjs from "dayjs";
import { message } from "ant-design-vue";
import { Modal } from "ant-design-vue";
import CropperModal from "@/Components/Member/CropperModal.vue";

export default {
  components: {
    WebLayout,
    dayjs,
    message,
    Modal,
    CropperModal,
  },
  props: ["organizations", "belt_ranks", "member", "competition"],
  data() {
    return {
      loading: false,
      showCropModal: false,
      avatarPreview: null,
      modal: {
        isOpen: false,
        title: "Application Failed",
        content: "Error message",
      },
      mode: null,
      dateFormat: "YYYY-MM-DD",
      dateList: ["2023-01-02"],
      application: {
        age: null,
      },
      eligibleCategories: [],
      rules: {
        organization_id: { required: true },
        name_fn: { required: true },
        id_num: { required: true },
        dob: { required: true },
        gender: { required: true },
        email: { required: true, type: "email" },
        mobile: { required: true },
        category: { required: true },
        weight: { required: true },
        role: { required: true },
      },
      validateMessages: {
        required: "${label} is required!",
        types: {
          email: "${label} is not a valid email!",
          number: "${label} is not a valid number!",
        },
        number: {
          range: "${label} must be between ${min} and ${max}",
        },
      },
      layout: {
        labelCol: {
          span: 8,
        },
        wrapperCol: {
          span: 16,
        },
      },
      virticalStyle: {
        display: "flex",
        minHeight: "30px",
        lineHeight: "30px",
        marginLeft: "8px",
      },
    };
  },
  mounted() {
    this.application.competition_id = this.competition.id;
    if (this.member) {
      this.application.member_id = this.member.id;
      this.application.name_zh = this.member.name_zh;
      this.application.name_fn = this.member.name_fn;
      this.application.display_name = this.member.display_name;
      this.application.gender = this.member.gender;
      this.application.dob = this.member.dob;
      this.application.email = this.member.email;
      this.application.mobile = this.member.mobile;

      // 计算会员年龄
      if (this.member.dob) {
        this.calculateAge(this.member.dob);
      }
    }
  },
  created() {},
  methods: {
    setCroppedImageData(data) {
      this.avatarPreview = data.imageUrl;
      this.avatarData = data;
    },
    onDobChange(date) {
      if (date) {
        this.calculateAge(date);
      } else {
        this.application.age = null;
        this.eligibleCategories = [];
      }
    },
    calculateAge(dob) {
      const birthDate = dayjs(dob);
      const today = dayjs();
      const age = today.diff(birthDate, "year");
      this.application.age = age;
      this.updateEligibleCategories(age);
    },
    updateEligibleCategories(age) {
      this.eligibleCategories = this.competition.categories_weights.filter((category) => {
        if (category.age_range && category.age_range.length === 2) {
          return age >= category.age_range[0] && age <= category.age_range[1];
        }
        return true; // 如果没有age_range，默认显示所有组别
      });

      // 如果当前选择的category不在符合条件的组别中，清空选择
      if (
        this.application.category &&
        !this.isCategoryEligible(this.application.category)
      ) {
        this.application.category = null;
        this.application.weight = null;
      }
    },
    isCategoryEligible(categoryCode) {
      return this.eligibleCategories.some((cat) => cat.code === categoryCode);
    },
    onGenderChange(event) {
      if (this.application.category) {
        this.weightSelection(event.target.value, this.application.category);
      }
      this.application.role = null;
      this.application.category = null;
      this.application.weight = null;
    },
    onCategoryChange(event) {
      this.application.weight = null;
      if (this.application.gender) {
        this.weightSelection(this.application.gender, event.target.value);
      }
    },
    weightSelection(gender, category) {
      if (gender == "M") {
        this.competition.weights = this.competition.categories_weights.find(
          (cat) => cat.code == category
        ).male;
      } else {
        this.competition.weights = this.competition.categories_weights.find(
          (cat) => cat.code == category
        ).female;
      }
    },
    onFinish() {
      // 如果是运动员角色，检查年龄是否符合所选组别
      if (this.application.role === "athlete" && this.application.category) {
        const selectedCategory = this.competition.categories_weights.find(
          (cat) => cat.code === this.application.category
        );

        if (selectedCategory && selectedCategory.age_range) {
          const [minAge, maxAge] = selectedCategory.age_range;
          if (this.application.age < minAge || this.application.age > maxAge) {
            message.error(
              `您选择的${selectedCategory.name}组别要求年龄在${minAge}至${maxAge}岁之间，您当前${this.application.age}岁不符合要求。`
            );
            return;
          }
        }
      }

      this.loading = true;
      if (this.avatarData) {
        this.application.avatar = this.avatarData.blob;
      }
      this.$inertia.post(route("competitions.store"), this.application, {
        onSuccess: (page) => {
          console.log(page);
          this.loading = false;
        },
        onError: (error) => {
          console.log(error);
          this.loading = false;
        },
      });
    },
    disabledDate(current) {
      return current > dayjs(new Date()).subtract(3, "year");
    },
  },
};
</script>

<style scope>
#pure-html {
  all: initial;
}

#pure-html * {
  all: revert;
}
</style>
