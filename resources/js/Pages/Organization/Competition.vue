<template>
  <OrganizationLayout
    :title="competition ? '賽事修改' : '賽事新增'"
    :breadcrumb="breadcrumb"
  >
    <div class="container mx-auto">
      <div class="bg-white relative shadow rounded-lg p-5">
        <a-form
          ref="formRef"
          :model="competitionData"
          name="nest-messages"
          :validate-messages="validateMessages"
          layout="vertical"
          :rules="rules"
          @finish="onFinish"
        >
          <a-form-item
            :label="$t('competition_score_category')"
            name="competition_score_id"
          >
            <a-select
              v-model:value="competitionData.competition_score_id"
              :options="
                competitionScores.map((x) => ({ value: x.id.toString(), label: x.title }))
              "
            />
          </a-form-item>
          <a-form-item :label="$t('competition_title_zh')" name="title_zh">
            <a-input type="input" v-model:value="competitionData.title_zh" />
          </a-form-item>
          <a-form-item :label="$t('competition_title_fn')" name="title_fn">
            <a-input type="input" v-model:value="competitionData.title_fn" />
          </a-form-item>
          <a-form-item :label="$t('brief')" name="brief">
            <a-textarea v-model:value="competitionData.brief" style="min-height: 100px" />
          </a-form-item>
          <a-form-item :label="$t('description')" name="description">
            <quill-editor
              v-model:value="competitionData.description"
              style="min-height: 200px"
            />
          </a-form-item>
          <a-form-item :label="$t('competition_period')" name="period">
            <a-range-picker
              v-model:value="competitionData.period"
              :format="dateFormat"
              @change="onCompetitionPeriodChange"
            />
          </a-form-item>
          <a-form-item :label="$t('competition_dates')" name="match_dates">
            <a-select v-model:value="competitionData.match_dates" mode="multiple">
              <a-select-option v-for="d in dateList" :key="d" :value="d">{{
                d
              }}</a-select-option>
            </a-select>
          </a-form-item>
          <a-form-item :label="$t('group')" name="cwSelected">
            <a @click="showWeightList = !showWeightList" class="float-right">詳細內容</a>
            <a-checkbox-group v-model:value="competitionData.cwSelected" class="w-full">
              <a-row :span="24">
                <template v-for="cw in categories_weights" :key="cw.code">
                  <a-col :span="6">
                    <!-- 使用 code 作為值，但會映射回完整物件 -->
                    <a-checkbox :value="cw.code">{{ cw.name }}</a-checkbox
                    ><br />
                  </a-col>
                </template>
              </a-row>
            </a-checkbox-group>
          </a-form-item>
          <a-divider></a-divider>

          <a-card v-show="showWeightList">
            <template #title> 各級重量 </template>
            <table width="100%">
              <tr>
                <th v-for="cw in categories_weights" :key="cw.code" class="text-left">
                  <a-typography-title :level="5">{{ cw.name }}</a-typography-title>
                </th>
              </tr>
              <tr class="align-top">
                <td v-for="cw in categories_weights" :key="cw.code">
                  <a-typography-text strong>男子組</a-typography-text>
                  <ol>
                    <li v-for="male in cw.male" :key="male.name">
                      {{ male.name }} : {{ male.limit[0] }} - {{ male.limit[1] }}
                    </li>
                  </ol>
                  <a-typography-text strong>女子組</a-typography-text>
                  <ol>
                    <li v-for="female in cw.female" :key="female.name">
                      {{ female.name }} : {{ female.limit[0] }} - {{ female.limit[1] }}
                    </li>
                  </ol>
                </td>
              </tr>
            </table>
          </a-card>

          <a-form-item :label="$t('role')" name="roleSelected">
            <a-checkbox-group v-model:value="competitionData.roleSelected">
              <a-checkbox
                v-for="role in roles"
                :key="role.value"
                :style="virticalStyle"
                :value="role.value"
                >{{ role.label }}</a-checkbox
              >
            </a-checkbox-group>
          </a-form-item>
          <a-form-item
            :label="$t('referee_options')"
            name="refereeOptionsSelected"
            v-if="hasRole('referee')"
            :rules="hasRole('referee') ? [{ required: true }] : []"
          >
            <a-checkbox-group v-model:value="competitionData.refereeOptionsSelected">
              <a-checkbox
                v-for="option in refereeOptions"
                :key="option.value"
                :style="virticalStyle"
                :value="option.value"
                >{{ option.label }}</a-checkbox
              >
            </a-checkbox-group>
          </a-form-item>
          <a-form-item
            :label="$t('staff_options')"
            name="staffOptionsSelected"
            v-if="hasRole('staff')"
            :rules="hasRole('staff') ? [{ required: true }] : []"
          >
            <a-checkbox-group v-model:value="competitionData.staffOptionsSelected">
              <a-checkbox
                v-for="option in staffOptions"
                :key="option.value"
                :style="virticalStyle"
                :value="option.value"
                >{{ option.label }}</a-checkbox
              >
            </a-checkbox-group>
          </a-form-item>
          <a-form-item :label="$t('banner_image')" name="banner">
            <div
              v-if="
                mode == 'EDIT' &&
                competition.media.find((m) => m.collection_name == 'competitionBanner')
              "
            >
              <inertia-link
                :href="
                  route('manage.competition.deleteMedia', {
                    type: 'banner',
                    competition_id: competition.id,
                  })
                "
                method="post"
                as="button"
                type="button"
                class="float-right text-red-500"
              >
                <svg
                  focusable="false"
                  class=""
                  data-icon="delete"
                  width="1em"
                  height="1em"
                  fill="currentColor"
                  aria-hidden="true"
                  viewBox="64 64 896 896"
                >
                  <path
                    d="M360 184h-8c4.4 0 8-3.6 8-8v8h304v-8c0 4.4 3.6 8 8 8h-8v72h72v-80c0-35.3-28.7-64-64-64H352c-35.3 0-64 28.7-64 64v80h72v-72zm504 72H160c-17.7 0-32 14.3-32 32v32c0 4.4 3.6 8 8 8h60.4l24.7 523c1.6 34.1 29.8 61 63.9 61h454c34.2 0 62.3-26.8 63.9-61l24.7-523H888c4.4 0 8-3.6 8-8v-32c0-17.7-14.3-32-32-32zM731.3 840H292.7l-24.2-512h487l-24.2 512z"
                  ></path>
                </svg>
              </inertia-link>
              <img
                :src="
                  competition.media.find((m) => m.collection_name == 'competitionBanner')
                    .preview_url
                "
                class="max-w-full h-auto"
              />
            </div>
            <div v-else>
              <a-upload
                v-model:file-list="competitionData.banner"
                :multiple="false"
                :before-upload="beforeBannerUpload"
                :max-count="1"
                :custom-request="customUploadRequest"
                :accept="uploadValidator.banner.format.toString()"
                list-type="picture"
                :capture="null"
                @remove="handleRemoveFile($event, 'banner')"
              >
                <a-button>
                  <upload-outlined></upload-outlined>
                  {{ $t("upload_file") }}
                </a-button>
              </a-upload>
            </div>
          </a-form-item>

          <a-form-item :label="$t('attachment')" name="attachment">
            <div v-if="mode == 'EDIT'">
              <div
                v-for="file in competition.media.filter(
                  (m) => m.collection_name == 'competitionAttachment'
                )"
                :key="file.id"
                class="mb-4 p-3 border rounded"
              >
                <inertia-link
                  :href="
                    route('manage.competition.deleteMedia', {
                      type: 'attachment',
                      competition_id: competition.id,
                      media_id: file.id,
                    })
                  "
                  method="post"
                  as="button"
                  type="button"
                  class="float-right text-red-500"
                >
                  <svg
                    focusable="false"
                    class=""
                    data-icon="delete"
                    width="1em"
                    height="1em"
                    fill="currentColor"
                    aria-hidden="true"
                    viewBox="64 64 896 896"
                  >
                    <path
                      d="M360 184h-8c4.4 0 8-3.6 8-8v8h304v-8c0 4.4 3.6 8 8 8h-8v72h72v-80c0-35.3-28.7-64-64-64H352c-35.3 0-64 28.7-64 64v80h72v-72zm504 72H160c-17.7 0-32 14.3-32 32v32c0 4.4 3.6 8 8 8h60.4l24.7 523c1.6 34.1 29.8 61 63.9 61h454c34.2 0 62.3-26.8 63.9-61l24.7-523H888c4.4 0 8-3.6 8-8v-32c0-17.7-14.3-32-32-32zM731.3 840H292.7l-24.2-512h487l-24.2 512z"
                    ></path>
                  </svg>
                </inertia-link>

                <div v-if="file.mime_type.includes('image')">
                  <img :src="file.preview_url" class="max-w-xs h-auto" />
                </div>
                <div v-else>
                  <a
                    :href="file.original_url"
                    target="_blank"
                    class="text-blue-600 hover:underline"
                  >
                    {{ file.file_name }}
                  </a>
                </div>
              </div>
            </div>
            <div>
              <a-upload
                v-model:file-list="competitionData.attachment"
                :multiple="true"
                :before-upload="beforeAttachmentUpload"
                :max-count="5"
                :custom-request="customUploadRequest"
                :accept="uploadValidator.attachment.format.toString()"
                list-type="picture"
                @remove="handleRemoveFile($event, 'attachment')"
              >
                <a-button>
                  <upload-outlined></upload-outlined>
                  {{ $t("upload_file") }}
                </a-button>
              </a-upload>
            </div>
          </a-form-item>

          <a-form-item :label="$t('athlete_card')" name="athlete_card">
            <div v-if="mode == 'EDIT'">
              <div
                v-for="file in competition.media.filter(
                  (m) => m.collection_name == 'competitionAthleteCard'
                )"
                :key="file.id"
                class="mb-4 p-3 border rounded"
              >
                <inertia-link
                  :href="
                    route('manage.competition.deleteMedia', {
                      type: 'athlete_card',
                      competition_id: competition.id,
                      media_id: file.id,
                    })
                  "
                  method="post"
                  as="button"
                  type="button"
                  class="float-right text-red-500"
                >
                  <svg
                    focusable="false"
                    class=""
                    data-icon="delete"
                    width="1em"
                    height="1em"
                    fill="currentColor"
                    aria-hidden="true"
                    viewBox="64 64 896 896"
                  >
                    <path
                      d="M360 184h-8c4.4 0 8-3.6 8-8v8h304v-8c0 4.4 3.6 8 8 8h-8v72h72v-80c0-35.3-28.7-64-64-64H352c-35.3 0-64 28.7-64 64v80h72v-72zm504 72H160c-17.7 0-32 14.3-32 32v32c0 4.4 3.6 8 8 8h60.4l24.7 523c1.6 34.1 29.8 61 63.9 61h454c34.2 0 62.3-26.8 63.9-61l24.7-523H888c4.4 0 8-3.6 8-8v-32c0-17.7-14.3-32-32-32zM731.3 840H292.7l-24.2-512h487l-24.2 512z"
                    ></path>
                  </svg>
                </inertia-link>

                <div v-if="file.mime_type.includes('image')">
                  <img :src="file.preview_url" class="max-w-xs h-auto" />
                </div>
                <div v-else>
                  <a
                    :href="file.original_url"
                    target="_blank"
                    class="text-blue-600 hover:underline"
                  >
                    {{ file.file_name }}
                  </a>
                </div>
              </div>
            </div>
            <div>
              <a-upload
                v-model:file-list="competitionData.athlete_card"
                :multiple="true"
                :max-count="5"
                :before-upload="beforeAthleteCardUpload"
                :custom-request="customUploadRequest"
                :accept="uploadValidator.athlete_card.format.toString()"
                list-type="picture"
                @remove="handleRemoveFile($event, 'athlete_card')"
              >
                <a-button>
                  <upload-outlined></upload-outlined>
                  {{ $t("upload_file") }}
                </a-button>
              </a-upload>
            </div>
          </a-form-item>

          <a-form-item :label="$t('application_fee')" name="fee">
            <a-input-number
              v-model:value="competitionData.fee"
              :min="0"
              :precision="2"
              style="width: 150px"
            />
          </a-form-item>

          <a-form-item :label="$t('published')" name="published">
            <a-switch
              v-model:checked="competitionData.published"
              :checkedValue="1"
              :unCheckedValue="0"
            />
          </a-form-item>

          <a-form-item :label="$t('scope')" name="scope">
            <a-radio-group v-model:value="competitionData.scope" button-style="solid">
              <a-radio-button value="PUB">{{ $t("public") }}</a-radio-button>
              <a-radio-button value="MJA">MJA {{ $t("members") }}</a-radio-button>
              <a-radio-button value="ORG">{{
                $t("organization_member_only")
              }}</a-radio-button>
            </a-radio-group>
          </a-form-item>

          <div class="flex flex-row item-center justify-center mt-8">
            <a-button type="primary" html-type="submit" :loading="loading">
              {{ $t("submit") }}
            </a-button>
          </div>
        </a-form>
      </div>
    </div>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { quillEditor } from "vue3-quill";
import dayjs from "dayjs";
import { UploadOutlined } from "@ant-design/icons-vue";
import { message, Upload } from "ant-design-vue";

export default {
  components: {
    OrganizationLayout,
    quillEditor,
    dayjs,
    UploadOutlined,
  },
  props: [
    "competitionScores",
    "competition",
    "scoreCategories",
    "staffOptions",
    "refereeOptions",
    "medias",
    "categories_weights",
    "roles",
  ],
  data() {
    return {
      breadcrumb: [
        { label: "賽事列表", url: route("manage.competitions.index") },
        { label: this.competition ? "賽事修改" : "賽事新增", url: null },
      ],
      mode: null,
      showWeightList: false,
      dateFormat: "YYYY-MM-DD",
      dateList: [],
      loading: false,
      competitionData: {
        banner: [],
        attachment: [],
        athlete_card: [],
        roleSelected: [], // 儲存值（如 'athlete', 'referee' 等）
        cwSelected: [], // 儲存 code 值
        refereeOptionsSelected: [], // 儲存值
        staffOptionsSelected: [], // 儲存值
        match_dates: [],
        fee: 0,
        published: 0,
        scope: "PUB",
      },
      uploadValidator: {
        banner: {
          size: 2, // Magabyte
          format: ["image/jpeg", "image/png"],
        },
        attachment: {
          size: 1, // Magabyte
          format: [
            "image/jpeg",
            "image/png",
            "application/pdf",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
          ],
        },
        athlete_card: {
          size: 2, // Magabyte
          format: ["image/jpeg", "image/png"],
        },
      },
      rules: {
        competition_score_id: { required: true, message: "請選擇積分類別" },
        title_zh: { required: true, message: "請輸入中文標題" },
        period: { required: true, message: "請選擇賽事期間" },
        match_dates: { required: true, message: "請選擇賽事日期" },
        roleSelected: { required: true, message: "請選擇角色" },
        cwSelected: { required: true, message: "請選擇組別" },
        scope: { required: true, message: "請選擇參與範圍" },
      },
      validateMessages: {
        required: "${label} 是必填項!",
        types: {
          email: "${label} 不是有效的郵箱!",
          number: "${label} 不是有效的數字!",
        },
        number: {
          range: "${label} 必須在 ${min} 和 ${max} 之間",
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
  mounted() {
    // 如果有錯誤訊息，顯示提示
    if (this.$page.props.errors && Object.keys(this.$page.props.errors).length > 0) {
      message.error("請檢查表單中的錯誤");
    }
  },
  created() {
    if (this.competitionScores != null) {
      this.competitionScores.unshift({ id: 0, title: "無積分" });
    }

    if (this.competition == null) {
      this.mode = "CREATE";
      this.competitionData.roleSelected = [];
    } else {
      this.mode = "EDIT";

      // 複製比賽資料，但不包括檔案列表
      this.competitionData = { ...this.competition };
      console.log("原始資料:", this.competitionData);

      // 初始化檔案列表
      this.competitionData.banner = [];
      this.competitionData.attachment = [];
      this.competitionData.athlete_card = [];

      // 設置日期範圍
      if (this.competition.start_date && this.competition.end_date) {
        this.competitionData.period = [
          dayjs(this.competition.start_date),
          dayjs(this.competition.end_date),
        ];

        // 生成日期列表
        this.getDaysArray(this.competition.start_date, this.competition.end_date);
      }

      // 關鍵修正：提取值而不是整個物件
      // 解析 JSON 字串並提取值
      this.competitionData.cwSelected = this.extractValues(
        this.parseJsonArray(this.competition.categories_weights),
        "code"
      );

      this.competitionData.roleSelected = this.extractValues(
        this.parseJsonArray(this.competition.roles),
        "value"
      );

      this.competitionData.refereeOptionsSelected = this.extractValues(
        this.parseJsonArray(this.competition.referee_options),
        "value"
      );

      this.competitionData.staffOptionsSelected = this.extractValues(
        this.parseJsonArray(this.competition.staff_options),
        "value"
      );

      console.log("提取後的組別選項:", this.competitionData.cwSelected);
      console.log("提取後的角色選項:", this.competitionData.roleSelected);

      // 設置賽事日期（如果存在）
      if (this.competition.match_dates) {
        this.competitionData.match_dates = this.parseJsonArray(
          this.competition.match_dates
        );
      }

      // 確保數值型欄位有值
      this.competitionData.fee = this.competition.fee || 0;
      this.competitionData.published = this.competition.published || 0;
      this.competitionData.scope = this.competition.scope || "PUB";
      this.competitionData.competition_score_id =
        this.competition.competition_score_id || null;
    }
  },
  methods: {
    // 檢查是否包含特定角色
    hasRole(roleValue) {
      if (
        !this.competitionData.roleSelected ||
        !Array.isArray(this.competitionData.roleSelected)
      ) {
        return false;
      }

      // 現在直接比較值
      return this.competitionData.roleSelected.includes(roleValue);
    },

    // 提取值從物件陣列
    extractValues(items, keyField) {
      if (!items || !Array.isArray(items)) return [];

      const values = [];
      items.forEach((item) => {
        if (typeof item === "object" && item !== null && item[keyField] !== undefined) {
          // 如果是物件，提取指定的鍵值
          values.push(item[keyField]);
        } else if (typeof item === "string" || typeof item === "number") {
          // 如果已經是值，直接使用
          values.push(item);
        }
      });

      return values;
    },

    // 根據值獲取完整物件
    getObjectsByValues(values, allItems, keyField) {
      if (!values || !Array.isArray(values) || values.length === 0) return [];
      if (!allItems || !Array.isArray(allItems) || allItems.length === 0) return [];

      const objects = [];
      values.forEach((value) => {
        const found = allItems.find((item) => item[keyField] === value);
        if (found) {
          objects.push(found);
        }
      });

      return objects;
    },

    // 解析 JSON 字串為陣列
    parseJsonArray(value) {
      if (!value) return [];

      // 如果已經是陣列，直接返回
      if (Array.isArray(value)) return value;

      // 如果是字串，嘗試解析為 JSON
      if (typeof value === "string") {
        try {
          const parsed = JSON.parse(value);
          return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
          console.warn("JSON 解析失敗:", e, value);
          return [];
        }
      }

      return [];
    },

    onCompetitionPeriodChange() {
      if (this.competitionData.period && this.competitionData.period.length === 2) {
        this.competitionData.match_dates = [];
        this.getDaysArray(this.competitionData.period[0], this.competitionData.period[1]);
      }
    },

    getDaysArray(start, end) {
      const arr = [];
      const startDate = dayjs(start);
      const endDate = dayjs(end);
      let currentDate = startDate;

      while (currentDate.isSame(endDate) || currentDate.isBefore(endDate)) {
        arr.push(currentDate.format("YYYY-MM-DD"));
        currentDate = currentDate.add(1, "day");
      }

      this.dateList = arr;
    },

    beforeBannerUpload(file) {
      const isOverSize = file.size / 1024 / 1024 > this.uploadValidator.banner.size;
      const isFormatInvalid = !this.uploadValidator.banner.format.includes(file.type);

      if (isOverSize || isFormatInvalid) {
        message.error("檔案格式不符或大小超過限制 (最大 2MB，僅限 JPG/PNG 格式)");
        return Upload.LIST_IGNORE;
      }
      return true;
    },

    beforeAthleteCardUpload(file) {
      const isOverSize = file.size / 1024 / 1024 > this.uploadValidator.athlete_card.size;
      const isFormatInvalid = !this.uploadValidator.athlete_card.format.includes(
        file.type
      );

      if (isOverSize || isFormatInvalid) {
        message.error("檔案格式不符或大小超過限制 (最大 2MB，僅限 JPG/PNG 格式)");
        return Upload.LIST_IGNORE;
      }
      return true;
    },

    beforeAttachmentUpload(file) {
      const isOverSize = file.size / 1024 / 1024 > this.uploadValidator.attachment.size;
      const isFormatInvalid = !this.uploadValidator.attachment.format.includes(file.type);

      if (isOverSize || isFormatInvalid) {
        message.error(
          "檔案格式不符或大小超過限制 (最大 1MB，支援圖片、PDF、Word、Excel 格式)"
        );
        return Upload.LIST_IGNORE;
      }
      return true;
    },

    async customUploadRequest({ file, onSuccess, onError, onProgress }) {
      try {
        // 模擬上傳進度
        let progress = 0;
        const progressInterval = setInterval(() => {
          progress += 20;
          onProgress({ percent: progress });

          if (progress >= 100) {
            clearInterval(progressInterval);
            onSuccess("ok", file);
          }
        }, 50);
      } catch (error) {
        onError(error);
      }
    },

    handleRemoveFile(file, type) {
      console.log(`移除 ${type} 檔案:`, file.name);
    },

    prepareFormData() {
      const formData = new FormData();

      // 添加基本資料
      const formFields = [
        "competition_score_id",
        "title_zh",
        "title_fn",
        "brief",
        "description",
        "fee",
        "published",
        "scope",
      ];

      formFields.forEach((field) => {
        if (
          this.competitionData[field] !== undefined &&
          this.competitionData[field] !== null
        ) {
          formData.append(field, this.competitionData[field]);
        }
      });

      // 處理日期
      if (this.competitionData.period && this.competitionData.period[0]) {
        formData.append(
          "start_date",
          this.competitionData.period[0].format("YYYY-MM-DD")
        );
        formData.append("end_date", this.competitionData.period[1].format("YYYY-MM-DD"));
      }

      // 處理複選項目 - 關鍵修正：儲存完整物件
      if (
        this.competitionData.match_dates &&
        this.competitionData.match_dates.length > 0
      ) {
        formData.append("match_dates", JSON.stringify(this.competitionData.match_dates));
      }

      // 組別：根據 code 獲取完整物件
      if (this.competitionData.cwSelected && this.competitionData.cwSelected.length > 0) {
        const cwObjects = this.getObjectsByValues(
          this.competitionData.cwSelected,
          this.categories_weights,
          "code"
        );
        // 只儲存必要的欄位，避免循環引用
        const cwData = cwObjects.map((cw) => ({
          code: cw.code,
          name: cw.name,
          male: cw.male,
          description: cw.description,
          age_range: cw.age_range,
          female: cw.female,
        }));
        formData.append("cwSelected", JSON.stringify(cwData));
      }

      // 角色：根據 value 獲取完整物件
      if (
        this.competitionData.roleSelected &&
        this.competitionData.roleSelected.length > 0
      ) {
        const roleObjects = this.getObjectsByValues(
          this.competitionData.roleSelected,
          this.roles,
          "value"
        );
        const roleData = roleObjects.map((role) => ({
          value: role.value,
          label: role.label,
        }));
        formData.append("roleSelected", JSON.stringify(roleData));
      }

      // 裁判選項：根據 value 獲取完整物件
      if (
        this.competitionData.refereeOptionsSelected &&
        this.competitionData.refereeOptionsSelected.length > 0
      ) {
        const refereeObjects = this.getObjectsByValues(
          this.competitionData.refereeOptionsSelected,
          this.refereeOptions,
          "value"
        );
        const refereeData = refereeObjects.map((option) => ({
          value: option.value,
          label: option.label,
        }));
        formData.append("refereeOptionsSelected", JSON.stringify(refereeData));
      }

      // 工作人員選項：根據 value 獲取完整物件
      if (
        this.competitionData.staffOptionsSelected &&
        this.competitionData.staffOptionsSelected.length > 0
      ) {
        const staffObjects = this.getObjectsByValues(
          this.competitionData.staffOptionsSelected,
          this.staffOptions,
          "value"
        );
        const staffData = staffObjects.map((option) => ({
          value: option.value,
          label: option.label,
        }));
        formData.append("staffOptionsSelected", JSON.stringify(staffData));
      }

      // 處理檔案上傳
      if (this.competitionData.banner && this.competitionData.banner.length > 0) {
        const bannerFile = this.competitionData.banner[0];
        if (bannerFile.originFileObj) {
          formData.append("banner", bannerFile.originFileObj);
        }
      }

      if (this.competitionData.attachment && this.competitionData.attachment.length > 0) {
        this.competitionData.attachment.forEach((file) => {
          if (file.originFileObj) {
            formData.append("attachment[]", file.originFileObj);
          }
        });
      }

      if (
        this.competitionData.athlete_card &&
        this.competitionData.athlete_card.length > 0
      ) {
        this.competitionData.athlete_card.forEach((file) => {
          if (file.originFileObj) {
            formData.append("athlete_card[]", file.originFileObj);
          }
        });
      }

      console.log("準備儲存的資料:", {
        cwSelected: this.competitionData.cwSelected,
        roleSelected: this.competitionData.roleSelected,
      });

      return formData;
    },

    async onFinish() {
      this.loading = true;

      try {
        // 準備 FormData
        const formData = this.prepareFormData();

        if (this.mode === "CREATE") {
          await this.$inertia.post(route("manage.competitions.store"), formData, {
            onSuccess: () => {
              message.success("賽事創建成功");
            },
            onError: (errors) => {
              console.error("創建錯誤:", errors);
              if (errors && Object.keys(errors).length > 0) {
                message.error("請檢查表單中的錯誤");
              }
            },
          });
        } else {
          formData.append("_method", "PATCH");
          await this.$inertia.post(
            route("manage.competitions.update", this.competition.id),
            formData,
            {
              onSuccess: () => {
                message.success("賽事更新成功");
              },
              onError: (errors) => {
                console.error("更新錯誤:", errors);
                if (errors && Object.keys(errors).length > 0) {
                  message.error("請檢查表單中的錯誤");
                }
              },
            }
          );
        }
      } catch (error) {
        console.error("提交錯誤:", error);
        message.error("提交失敗，請稍後再試");
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
img {
  max-width: 100%;
  height: auto;
}
.ant-upload-list-item {
  margin-top: 8px;
}
</style>
