<template>
  <MemberLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $t("profile") }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-5">
          <a-form
            ref="formRef"
            name="Form"
            autocomplete="off"
            v-bind="layout"
            :model="member"
            layout="vertical"
            :rules="rules"
            :validate-messages="validateMessages"
            enctype="multipart/form-data"
          >
            <a-collapse v-model:activeKey="activeKey">
              <a-collapse-panel key="1" :header="$t('profile_title')">
                <a-row :gutter="24" :span="24">
                  <a-col :span="12">
                    <a-form-item :label="$t('name_zh')" name="name_zh">
                      <a-input v-model:value="member.name_zh" />
                    </a-form-item>
                    <a-form-item :label="$t('name_fn')" name="name_fn">
                      <a-input v-model:value="member.name_fn" />
                    </a-form-item>
                  </a-col>
                  <a-col :span="12">
                    <a-form-item :label="$t('display_name')" name="display_name">
                      <a-input v-model:value="member.display_name" />
                    </a-form-item>
                  </a-col>
                </a-row>
              </a-collapse-panel>
              <a-collapse-panel key="2" :header="$t('personal_title')">
                <a-row :gutter="24" :span="24">
                  <a-col :span="12">
                    <a-form-item :label="$t('dob')" name="dob">
                      <a-date-picker
                        v-model:value="member.dob"
                        :format="dateFormat"
                        :valueFormat="dateFormat"
                      />
                    </a-form-item>
                    <a-form-item :label="$t('country')" name="country">
                      <a-input v-model:value="member.country" />
                    </a-form-item>
                    <a-form-item :label="$t('street')" name="street">
                      <a-input v-model:value="member.street" />
                    </a-form-item>
                    <a-form-item :label="$t('zip')" name="zip">
                      <a-input v-model:value="member.zip" />
                    </a-form-item>
                    <a-form-item :label="$t('email')" name="email">
                      <a-input v-model:value="member.email" />
                    </a-form-item>
                    <a-form-item :label="$t('nationality')" name="nationality">
                      <a-input v-model:value="member.nationality" />
                    </a-form-item>
                  </a-col>
                  <a-col :span="12">
                    <a-form-item :label="$t('gender')" name="gender">
                      <a-radio-group v-model:value="member.gender" button-style="solid">
                        <a-radio-button value="M">{{ $t("male") }}</a-radio-button>
                        <a-radio-button value="F">{{ $t("female") }}</a-radio-button>
                      </a-radio-group>
                    </a-form-item>
                    <a-form-item :label="$t('club')" name="club">
                      <a-input v-model:value="member.club" />
                    </a-form-item>
                    <a-form-item :label="$t('city')" name="city">
                      <a-input v-model:value="member.city" />
                    </a-form-item>
                    <a-form-item :label="$t('vat')" name="vat">
                      <a-input v-model:value="member.vat" />
                    </a-form-item>
                    <a-form-item :label="$t('mobile_number')" name="mobile">
                      <div v-if="member.user.mobile_verified_at" class="">
                        {{ member.user.mobile }}
                      </div>
                      <a-button
                        v-else
                        @click="
                          () => {
                            mobileModal = true;
                          }
                        "
                        >{{ $t("mobile_number_confirm") }}</a-button
                      >
                    </a-form-item>
                  </a-col>
                </a-row>
              </a-collapse-panel>
              <a-collapse-panel key="3" :header="$t('position_title')">
                <a-checkbox-group v-model:value="member.positions">
                  <a-checkbox :style="virticalStyle" value="ATH">{{
                    $t("compettitor")
                  }}</a-checkbox>
                  <a-checkbox :style="virticalStyle" value="REF">{{
                    $t("referee_title")
                  }}</a-checkbox>
                  <a-checkbox :style="virticalStyle" value="COA">{{
                    $t("coach")
                  }}</a-checkbox>
                  <a-checkbox :style="virticalStyle" value="OFF">{{
                    $t("official_title")
                  }}</a-checkbox>
                  <a-checkbox :style="virticalStyle" value="GUE"
                    >{{ $t("guest_title") }}
                    <a-typography-text type="secondary">
                      (VIP, VVIP, Media etc.)</a-typography-text
                    ></a-checkbox
                  >
                </a-checkbox-group>
              </a-collapse-panel>
              <a-collapse-panel
                key="4"
                :header="$t('athlete_title')"
                v-if="member.positions.includes('ATH')"
              >
                <a-row :gutter="24" :span="24">
                  <a-col :span="12">
                    <a-form-item :label="$t('coach')" name="coach">
                      <a-input v-model:value="member.athlete_coach" />
                    </a-form-item>
                    <a-form-item :label="$t('favorite_technique')" name="technique">
                      <a-input v-model:value="member.athlete_technique" />
                    </a-form-item>
                    <a-form-item :label="$t('side')" name="side">
                      <a-input v-model:value="member.athlete_side" />
                    </a-form-item>
                  </a-col>
                  <a-col :span="12">
                    <a-form-item :label="$t('belt')" name="belt">
                      <a-input v-model:value="member.athlete_belt" />
                    </a-form-item>
                    <a-form-item :label="$t('height')" name="height">
                      <a-input v-model:value="member.athlete_height" />
                    </a-form-item>
                    <a-form-item :label="$t('weight')" name="weight">
                      <a-input v-model:value="member.athlete_weight" />
                    </a-form-item>
                  </a-col>
                </a-row>
              </a-collapse-panel>
              <a-collapse-panel
                key="5"
                :header="$t('referee_title')"
                v-if="member.positions.includes('REF')"
              >
                <a-checkbox-group v-model:checked="member.role_referees">
                  <a-checkbox
                    v-for="position in positions.filter((p) => p.scope == 'REFEREE')"
                    :style="virticalStyle"
                    :value="position.code"
                    >{{ position.title_en }}</a-checkbox
                  >
                </a-checkbox-group>
              </a-collapse-panel>
              <!-- 
              <a-collapse-panel key="6" :header="$t('coach_title')" v-if="member.positions.includes('COA')">
                Coach
              </a-collapse-panel>
              -->
              <a-collapse-panel
                key="7"
                :header="$t('official_title')"
                v-if="member.positions.includes('OFF')"
              >
                <a-row :gutter="24" :span="24">
                  <a-col :span="12">
                    <a-form-item
                      :label="$t('federation_function')"
                      name="federation_officials"
                    >
                      <a-checkbox-group v-model:value="member.federation_officials">
                        <a-checkbox
                          v-for="position in positions.filter(
                            (p) => p.scope == 'FEDERATION'
                          )"
                          :style="virticalStyle"
                          :value="position.code"
                          >{{ position.title_en }}</a-checkbox
                        >
                      </a-checkbox-group>
                    </a-form-item>
                  </a-col>
                  <a-col :span="12">
                    <a-form-item
                      :label="$t('organization_function')"
                      name="organization_officials"
                    >
                      <a-checkbox-group v-model:value="member.organization_officials">
                        <a-checkbox
                          v-for="position in positions.filter(
                            (p) => p.scope == 'ORGANIZATION'
                          )"
                          :style="virticalStyle"
                          :value="position.code"
                          >{{ position.title_en }}</a-checkbox
                        >
                      </a-checkbox-group>
                    </a-form-item>
                  </a-col>
                </a-row>
              </a-collapse-panel>
              <!--
              <a-collapse-panel key="8" :header="$t('guest_title')" v-if="member.positions.includes('GUE')">
                      888
              </a-collapse-panel>
              -->
              <a-collapse-panel key="9" :header="$t('picture_title')">
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
                      <img :src="member.avatar_url" />
                    </div>
                  </div>
                </div>
              </a-collapse-panel>
            </a-collapse>
            <a-form-item
              :wrapper-col="{ offset: 10, span: 24 }"
              style="padding-top: 20px"
            >
              <a-button @click="onSubmit" type="primary">{{ $t("submit") }}</a-button>
            </a-form-item>
          </a-form>
        </div>
      </div>
    </div>
    <a-modal
      v-model:visible="mobileModal"
      :title="$t('mobile_number_confirm')"
      :ok-text="$t('confirm')"
      :cancel-text="$t('cancel')"
      @ok="confirmMobile"
    >
      <div class="flex flex-col gap-3">
        <div class="flex items-center gap-3">
          <div class="w-16">{{ $t("mobile_number") }}:</div>
          <div class="w-1/2"><a-input v-model:value="confirm_mobile"></a-input></div>
          <div class="">
            <a-button disabled v-if="countdown > 0">{{
              $t("count_down") + ":" + countdown + $t("second")
            }}</a-button>
            <a-button v-else @click="getVerificationCode()">{{
              $t("get_verification_code")
            }}</a-button>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <div class="w-16">{{ $t("verification_code") }}:</div>
          <div class="w-1/2"><a-input v-model:value="verification_code"></a-input></div>
        </div>
      </div>
    </a-modal>
  </MemberLayout>
</template>

<script>
import MemberLayout from "@/Layouts/MemberLayout.vue";
import { PlusOutlined, LoadingOutlined } from "@ant-design/icons-vue";
import { message } from "ant-design-vue";
import { quillEditor } from "vue3-quill";
import { UploadOutlined } from "@ant-design/icons-vue";
import CropperModal from "@/Components/Member/CropperModal.vue";

export default {
  components: {
    MemberLayout,
    PlusOutlined,
    LoadingOutlined,
    quillEditor,
    UploadOutlined,
    CropperModal,
  },
  props: ["member", "positions"],
  data() {
    return {
      dateFormat: "YYYY-MM-DD",
      countdown: 0,
      showCropModal: false,
      avatarPreview: null,
      confirm_mobile: null,
      verification_code: null,
      mobileModal: false,
      avatarData: null,
      activeKey: ["1", "3", "4", "5", "6", "7", "8", "9"],
      loading: false,
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      rules: {
        name_zh: { required: true },
        name_fn: { required: true },
        display_name: { required: true },
        email: { required: true, type: "email" },
        field: { required: true },
        label: { required: true },
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
        height: "30px",
        lineHeight: "30px",
        marginLeft: "8px",
      },
    };
  },
  created() {
    this.member.athlete = [];
  },
  methods: {
    setCroppedImageData(data) {
      this.avatarPreview = data.imageUrl;
      this.avatarData = data;
    },

    handleUploaded({ form, request, response }) {
      // update user avatar attribute
    },
    onSubmit() {
      if (this.avatarData) {
        this.member.avatar = this.avatarData.blob;
      }
      //this.member.avatar = this.avatarData.blob;
      this.member._method = "PATCH";
      this.$inertia.post(route("member.profile.update", this.member.id), this.member, {
        onSuccess: (page) => {
          console.log(page);
        },
        onError: (err) => {
          console.log(err);
        },
      });
    },
    getVerificationCode() {
      // this.startCountdown();
      this.$inertia.post(
        route("member.phoneConfirm.sms", this.member.id),
        { confirm_mobile: this.confirm_mobile },
        {
          onSuccess: (page) => {
            console.log(page);
          },
          onError: (err) => {
            console.log(err);
          },
        }
      );
    },
    startCountdown() {
      this.countdown = 60;
      let timer = setInterval(() => {
        if (this.countdown > 0) {
          this.countdown--;
        } else {
          clearInterval(timer);
        }
      }, 1000);
    },
    confirmMobile() {
      this.$inertia.post(
        route("member.confirmMobile", this.member.id),
        {
          confirm_mobile: this.confirm_mobile,
          verification_code: this.verification_code,
        },
        {
          onSuccess: (page) => {
            console.log(page);
          },
          onError: (err) => {
            console.log(err);
          },
        }
      );
    },
  },
};
</script>
