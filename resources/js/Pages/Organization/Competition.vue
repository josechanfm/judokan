<template>
  <OrganizationLayout
    :title="mode === 'EDIT' ? '賽事修改' : '賽事新增'"
    :breadcrumb="breadcrumb"
  >
    <div class="container mx-auto pt-5">
      <a-form
        ref="formRef"
        :model="competitionData"
        name="competition-form"
        :validate-messages="validateMessages"
        layout="vertical"
        :rules="rules"
        @finish="onFinish"
      >
        <!-- 頁首 -->
        <div class="card-judo mb-4 flex flex-wrap items-center justify-between gap-3 p-5">
          <div class="min-w-0">
            <h2 class="m-0 text-lg font-bold text-gray-800">
              {{ mode === "EDIT" ? "賽事修改" : "賽事新增" }}
            </h2>
            <p class="m-0 mt-1 text-sm text-gray-500">
              {{
                mode === "EDIT"
                  ? "修改賽事資料後按「儲存」套用變更。"
                  : "填寫賽事資料，儲存後可再加入報名與成績。"
              }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <inertia-link
              :href="route('manage.competitions.index')"
              class="btn-judo-outline"
            >
              {{ $t("cancel") }}
            </inertia-link>
            <button type="submit" class="btn-judo-primary" :disabled="loading">
              <SaveOutlined />
              {{ loading ? "儲存中…" : $t("save") }}
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
          <!-- 左欄 -->
          <div class="space-y-4 xl:col-span-2">
            <!-- 基本資料 -->
            <div class="card-judo p-5">
              <h3 class="ja-section-title">{{ $t("competition") }}</h3>

              <a-form-item :label="$t('competition_title_zh')" name="title_zh">
                <a-input
                  v-model:value="competitionData.title_zh"
                  placeholder="例如：2026 全澳柔道計分賽"
                />
              </a-form-item>

              <a-form-item :label="$t('competition_title_fn')" name="title_fn">
                <a-input
                  v-model:value="competitionData.title_fn"
                  placeholder="例如：2026 Macau Judo Ranking Competition"
                />
              </a-form-item>

              <a-form-item
                :label="$t('competition_score_category')"
                name="competition_score_id"
              >
                <a-select
                  v-model:value="competitionData.competition_score_id"
                  :options="scoreOptions"
                  :placeholder="$t('all')"
                  :allow-clear="false"
                  show-search
                  option-filter-prop="label"
                />

                <!-- 選了積分表就顯示該積分表的分數 -->
                <div v-if="selectedScore" class="mt-3 rounded-lg bg-slate-50 p-3">
                  <div class="mb-2 flex flex-wrap items-center gap-2">
                    <TrophyOutlined class="text-[#0081C8]" />
                    <span class="text-sm font-semibold text-gray-700">
                      {{ selectedScore.title }}
                    </span>
                    <span class="ja-pill ja-pill-blue">
                      {{ $t("competition_score_category") }}：{{ scoreCategoryLabel(selectedScore.category) }}
                    </span>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <span v-for="rank in rankFields" :key="rank.key" class="ja-score-chip">
                      <span class="ja-score-chip-label">{{ $t(rank.i18n) }}</span>
                      <span class="ja-score-chip-value">
                        {{ selectedScore[rank.key] ?? 0 }}
                      </span>
                    </span>
                  </div>
                </div>
                <p
                  v-else-if="!competitionData.competition_score_id || competitionData.competition_score_id === '0'"
                  class="m-0 mt-2 text-xs text-gray-400"
                >
                  未選擇積分表：這場賽事的成績將不會計算積分。
                </p>
              </a-form-item>
            </div>

            <!-- 賽事日期 -->
            <div class="card-judo p-5">
              <h3 class="ja-section-title">{{ $t("date") }}</h3>

              <a-form-item :label="$t('competition_period')" name="period">
                <a-range-picker
                  v-model:value="competitionData.period"
                  :format="dateFormat"
                  style="width: 100%"
                  @change="onCompetitionPeriodChange"
                />
              </a-form-item>

              <a-form-item :label="$t('competition_dates')" name="match_dates">
                <a-select
                  v-model:value="competitionData.match_dates"
                  mode="multiple"
                  :placeholder="$t('all')"
                  style="width: 100%"
                >
                  <a-select-option v-for="d in dateList" :key="d" :value="d">
                    {{ d }}
                  </a-select-option>
                </a-select>
                <p class="m-0 mt-1 text-xs text-gray-400">
                  選擇賽事期間後會自動帶出每一天，可再自行調整比賽日。
                </p>
              </a-form-item>
            </div>

            <!-- 賽事內容 -->
            <div class="card-judo p-5">
              <h3 class="ja-section-title">{{ $t("description") }}</h3>

              <a-form-item :label="$t('brief')" name="brief">
                <a-textarea
                  v-model:value="competitionData.brief"
                  :auto-size="{ minRows: 3, maxRows: 6 }"
                  placeholder="簡短介紹這場賽事"
                />
              </a-form-item>

              <a-form-item :label="$t('description')" name="description">
                <quill-editor
                  v-model:value="competitionData.description"
                  style="min-height: 200px"
                />
              </a-form-item>
            </div>

            <!-- 組別與量級 -->
            <div class="card-judo p-5">
              <div class="mb-3">
                <h3 class="ja-section-title m-0 border-0 pb-0">{{ $t("group") }}</h3>
                <p class="m-0 mt-1 text-xs text-gray-400">
                  勾選組別後，可在組別下方選擇要舉辦的公斤級，預設為全部選取。
                </p>
              </div>

              <a-form-item name="cwSelected">
                <a-checkbox-group v-model:value="competitionData.cwSelected" class="w-full">
                  <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                    <div
                      v-for="cw in categories_weights"
                      :key="cw.code"
                      class="ja-option-box"
                      :class="{
                        'ja-option-box-active': competitionData.cwSelected.includes(cw.code),
                      }"
                    >
                      <a-checkbox :value="cw.code">
                        <span class="font-medium">{{ cw.name }}</span>
                        <span v-if="cw.description" class="ml-1 text-xs text-gray-400">
                          {{ cw.description }}
                        </span>
                      </a-checkbox>

                      <!-- 選了組別才顯示該組別的各級重量 -->
                      <div
                        v-if="competitionData.cwSelected.includes(cw.code)"
                        class="mt-2 space-y-2 border-t border-slate-100 pt-2"
                      >
                        <div v-for="group in weightGroups(cw)" :key="group.gender">
                          <div class="mb-1 flex items-center justify-between gap-2">
                            <span class="ja-mini-label">{{ genderLabel(group.gender) }}</span>
                            <span class="flex items-center gap-2">
                              <button
                                type="button"
                                class="ja-link"
                                @click="selectAllWeights(cw.code, group.gender, true)"
                              >
                                全選
                              </button>
                              <span class="text-xs text-slate-300">|</span>
                              <button
                                type="button"
                                class="ja-link ja-link-muted"
                                @click="selectAllWeights(cw.code, group.gender, false)"
                              >
                                全不選
                              </button>
                            </span>
                          </div>
                          <div class="flex flex-wrap gap-1">
                            <button
                              v-for="weight in group.items"
                              :key="group.gender + '-' + weight.code"
                              type="button"
                              class="ja-weight-chip ja-weight-chip-toggle"
                              :class="{
                                'ja-weight-chip-off': !isWeightSelected(
                                  cw.code,
                                  group.gender,
                                  weight.code
                                ),
                              }"
                              :aria-pressed="
                                isWeightSelected(cw.code, group.gender, weight.code)
                              "
                              @click="toggleWeight(cw.code, group.gender, weight.code)"
                            >
                              {{ weight.name }}
                            </button>
                          </div>
                        </div>
                        <p class="m-0 text-xs text-gray-400">
                          已選 {{ selectedWeightCount(cw) }} / {{ totalWeightCount(cw) }} 個公斤級
                        </p>
                      </div>
                    </div>
                  </div>
                </a-checkbox-group>
              </a-form-item>
            </div>

            <!-- 角色與選項 -->
            <div class="card-judo p-5">
              <h3 class="ja-section-title">{{ $t("role") }}</h3>

              <a-form-item name="roleSelected" class="mb-4">
                <a-checkbox-group v-model:value="competitionData.roleSelected">
                  <div class="flex flex-wrap gap-2">
                    <div
                      v-for="role in roles"
                      :key="role.value"
                      class="ja-option-box ja-option-box-sm"
                      :class="{
                        'ja-option-box-active': competitionData.roleSelected.includes(
                          role.value
                        ),
                      }"
                    >
                      <a-checkbox :value="role.value">{{ role.label }}</a-checkbox>
                    </div>
                  </div>
                </a-checkbox-group>
              </a-form-item>

              <a-form-item
                v-if="hasRole('referee')"
                :label="$t('referee_options')"
                name="refereeOptionsSelected"
                :rules="hasRole('referee') ? [{ required: true }] : []"
              >
                <a-checkbox-group v-model:value="competitionData.refereeOptionsSelected">
                  <div class="flex flex-wrap gap-2">
                    <div
                      v-for="option in refereeOptions"
                      :key="option.value"
                      class="ja-option-box ja-option-box-sm"
                    >
                      <a-checkbox :value="option.value">{{ option.label }}</a-checkbox>
                    </div>
                  </div>
                </a-checkbox-group>
              </a-form-item>

              <a-form-item
                v-if="hasRole('staff')"
                :label="$t('staff_options')"
                name="staffOptionsSelected"
                :rules="hasRole('staff') ? [{ required: true }] : []"
              >
                <a-checkbox-group v-model:value="competitionData.staffOptionsSelected">
                  <div class="flex flex-wrap gap-2">
                    <div
                      v-for="option in staffOptions"
                      :key="option.value"
                      class="ja-option-box ja-option-box-sm"
                    >
                      <a-checkbox :value="option.value">{{ option.label }}</a-checkbox>
                    </div>
                  </div>
                </a-checkbox-group>
              </a-form-item>
            </div>

            <!-- 圖片與附件 -->
            <div class="card-judo p-5">
              <h3 class="ja-section-title">{{ $t("upload_file") }}</h3>

              <a-form-item :label="$t('banner_image')" name="banner">
                <div
                  v-if="
                    mode == 'EDIT' &&
                    competition.media.find((m) => m.collection_name == 'competitionBanner')
                  "
                  class="relative inline-block"
                >
                  <img
                    :src="
                      competition.media.find((m) => m.collection_name == 'competitionBanner')
                        .preview_url
                    "
                    class="max-h-48 w-auto rounded-lg ring-1 ring-gray-200"
                  />
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
                    class="absolute right-2 top-2 rounded bg-white/90 px-2 py-1 text-xs font-medium text-red-600 shadow ring-1 ring-red-100 hover:bg-white"
                  >
                    {{ $t("delete") }}
                  </inertia-link>
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
                    <a-button class="ja-upload-btn">
                      <upload-outlined></upload-outlined>
                      {{ $t("upload_file") }}
                    </a-button>
                  </a-upload>
                </div>
              </a-form-item>

              <a-form-item :label="$t('attachment')" name="attachment">
                <div v-if="mode == 'EDIT'" class="mb-3 space-y-2">
                  <div
                    v-for="file in competition.media.filter(
                      (m) => m.collection_name == 'competitionAttachment'
                    )"
                    :key="file.id"
                    class="flex items-center gap-3 rounded-lg border border-slate-200 p-2"
                  >
                    <div v-if="file.mime_type.includes('image')">
                      <img :src="file.preview_url" class="max-h-20 w-auto rounded" />
                    </div>
                    <a
                      v-else
                      :href="file.original_url"
                      target="_blank"
                      class="text-sm text-[#0081C8] hover:underline"
                    >
                      {{ file.file_name }}
                    </a>
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
                      class="ml-auto text-xs font-medium text-red-600 hover:underline"
                    >
                      {{ $t("delete") }}
                    </inertia-link>
                  </div>
                </div>
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
                  <a-button class="ja-upload-btn">
                    <upload-outlined></upload-outlined>
                    {{ $t("upload_file") }}
                  </a-button>
                </a-upload>
              </a-form-item>

              <a-form-item :label="$t('athlete_card')" name="athlete_card">
                <div v-if="mode == 'EDIT'" class="mb-3 space-y-2">
                  <div
                    v-for="file in competition.media.filter(
                      (m) => m.collection_name == 'competitionAthleteCard'
                    )"
                    :key="file.id"
                    class="flex items-center gap-3 rounded-lg border border-slate-200 p-2"
                  >
                    <div v-if="file.mime_type.includes('image')">
                      <img :src="file.preview_url" class="max-h-20 w-auto rounded" />
                    </div>
                    <a
                      v-else
                      :href="file.original_url"
                      target="_blank"
                      class="text-sm text-[#0081C8] hover:underline"
                    >
                      {{ file.file_name }}
                    </a>
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
                      class="ml-auto text-xs font-medium text-red-600 hover:underline"
                    >
                      {{ $t("delete") }}
                    </inertia-link>
                  </div>
                </div>
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
                  <a-button class="ja-upload-btn">
                    <upload-outlined></upload-outlined>
                    {{ $t("upload_file") }}
                  </a-button>
                </a-upload>
              </a-form-item>
            </div>
          </div>

          <!-- 右欄 -->
          <div class="space-y-4">
            <div class="card-judo p-5 xl:sticky xl:top-4">
              <h3 class="ja-section-title">{{ $t("status") }}</h3>

              <a-form-item :label="$t('published')" name="published" class="mb-4">
                <a-switch
                  v-model:checked="competitionData.published"
                  :checkedValue="1"
                  :unCheckedValue="0"
                  :checked-children="$t('published')"
                  :un-checked-children="$t('draft')"
                />
              </a-form-item>

              <a-form-item :label="$t('scope')" name="scope" class="mb-4">
                <a-radio-group v-model:value="competitionData.scope" class="w-full">
                  <div class="flex flex-col gap-2">
                    <div
                      v-for="option in scopeOptions"
                      :key="option.value"
                      class="ja-option-box"
                      :class="{
                        'ja-option-box-active': competitionData.scope === option.value,
                      }"
                    >
                      <a-radio :value="option.value">
                        <span class="font-medium">{{ $t(option.i18n) }}</span>
                        <span class="ml-1 text-xs text-gray-400">
                          {{ $t(option.hintI18n) }}
                        </span>
                      </a-radio>
                    </div>
                  </div>
                </a-radio-group>
              </a-form-item>

              <a-form-item :label="$t('application_fee')" name="fee" class="mb-0">
                <a-input-number
                  v-model:value="competitionData.fee"
                  :min="0"
                  :precision="2"
                  style="width: 100%"
                />
              </a-form-item>
            </div>

            <div class="card-judo p-5">
              <h3 class="ja-section-title">{{ $t("competition_score_category") }}</h3>
              <template v-if="selectedScore">
                <p class="m-0 mb-2 text-sm font-semibold text-gray-800">
                  {{ selectedScore.title }}
                </p>
                <div class="space-y-1">
                  <div
                    v-for="rank in rankFields"
                    :key="'s-' + rank.key"
                    class="flex items-center justify-between border-b border-slate-100 py-1 text-sm last:border-0"
                  >
                    <span class="text-gray-600">{{ $t(rank.i18n) }}</span>
                    <span class="font-semibold text-[#0081C8]">
                      {{ selectedScore[rank.key] ?? 0 }} 分
                    </span>
                  </div>
                </div>
              </template>
              <p v-else class="m-0 text-sm text-gray-400">
                請先在上方選擇積分表，這裡會顯示各名次的積分。
              </p>
            </div>
          </div>
        </div>

        <!-- 底部儲存 -->
        <div class="mt-4 mb-8 flex justify-end gap-2">
          <inertia-link
            :href="route('manage.competitions.index')"
            class="btn-judo-outline"
          >
            {{ $t("cancel") }}
          </inertia-link>
          <button type="submit" class="btn-judo-primary" :disabled="loading">
            <SaveOutlined />
            {{ loading ? "儲存中…" : $t("save") }}
          </button>
        </div>
      </a-form>
    </div>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { quillEditor } from "vue3-quill";
import dayjs from "dayjs";
import { SaveOutlined, TrophyOutlined, UploadOutlined } from "@ant-design/icons-vue";
import { message, Upload } from "ant-design-vue";

const RANK_FIELDS = [
  { key: "first", i18n: "competition_score_first" },
  { key: "second", i18n: "competition_score_second" },
  { key: "third", i18n: "competition_score_third" },
  { key: "fifth", i18n: "competition_score_fifth" },
  { key: "seventh", i18n: "competition_score_seventh" },
  { key: "advance", i18n: "competition_score_advance" },
  { key: "participate", i18n: "competition_score_participate" },
];

export default {
  components: {
    OrganizationLayout,
    quillEditor,
    dayjs,
    SaveOutlined,
    TrophyOutlined,
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
      // { [組別 code]: { male: [量級 code], female: [量級 code], mixed: [量級 code] } }
      cwWeightSelected: {},
      dateFormat: "YYYY-MM-DD",
      dateList: [],
      loading: false,
      rankFields: RANK_FIELDS,
      scopeOptions: [
        { value: "PUB", i18n: "public", hintI18n: "scope_public_hint" },
        { value: "MJA", i18n: "organization_member_only_mja", hintI18n: "scope_mja_hint" },
        { value: "ORG", i18n: "organization_member_only", hintI18n: "scope_org_hint" },
      ],
      competitionData: {
        banner: [],
        attachment: [],
        athlete_card: [],
        roleSelected: [],
        cwSelected: [],
        refereeOptionsSelected: [],
        staffOptionsSelected: [],
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
    };
  },
  computed: {
    // 積分表選項（0 = 不計分）
    scoreOptions() {
      const options = (this.competitionScores || []).map((score) => ({
        value: String(score.id),
        label: score.title,
      }));

      return [{ value: "0", label: "不計分" }, ...options];
    },
    // 目前選擇的積分表
    selectedScore() {
      const id = this.competitionData.competition_score_id;
      if (!id || String(id) === "0") {
        return null;
      }

      return (
        (this.competitionScores || []).find((score) => String(score.id) === String(id)) ||
        null
      );
    },
  },
  watch: {
    // 勾選新組別時，預設選取該組別的全部量級
    "competitionData.cwSelected"(selected) {
      (selected || []).forEach((code) => this.ensureWeightSelection(code));
    },
  },
  mounted() {
    if (this.$page.props.errors && Object.keys(this.$page.props.errors).length > 0) {
      message.error("請檢查表單中的錯誤");
    }
  },
  created() {
    if (this.competition == null) {
      this.mode = "CREATE";
      this.competitionData.roleSelected = [];
    } else {
      this.mode = "EDIT";

      // 複製比賽資料，但不包括檔案列表
      this.competitionData = { ...this.competition };

      this.competitionData.banner = [];
      this.competitionData.attachment = [];
      this.competitionData.athlete_card = [];

      // 設置日期範圍
      if (this.competition.start_date && this.competition.end_date) {
        this.competitionData.period = [
          dayjs(this.competition.start_date),
          dayjs(this.competition.end_date),
        ];

        this.getDaysArray(this.competition.start_date, this.competition.end_date);
      }

      this.competitionData.cwSelected = this.extractValues(
        this.parseJsonArray(this.competition.categories_weights),
        "code"
      );

      this.initWeightSelectionFromSaved(
        this.parseJsonArray(this.competition.categories_weights)
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

      if (this.competition.match_dates) {
        this.competitionData.match_dates = this.parseJsonArray(
          this.competition.match_dates
        );
      }

      this.competitionData.fee = this.competition.fee || 0;
      this.competitionData.published = this.competition.published || 0;
      this.competitionData.scope = this.competition.scope || "PUB";
      this.competitionData.competition_score_id = this.competition.competition_score_id
        ? String(this.competition.competition_score_id)
        : "0";
    }
  },
  methods: {
    // 積分表所屬級別的名稱
    scoreCategoryLabel(code) {
      const category = (this.scoreCategories || []).find((item) => item.value === code);

      return category?.label || code || "—";
    },

    // 檢查是否包含特定角色
    hasRole(roleValue) {
      if (
        !this.competitionData.roleSelected ||
        !Array.isArray(this.competitionData.roleSelected)
      ) {
        return false;
      }

      return this.competitionData.roleSelected.includes(roleValue);
    },

    // 取得組別中某性別的完整量級清單
    weightListOf(category, gender) {
      if (!category || !Array.isArray(category[gender])) return [];

      return category[gender].filter((weight) => weight && weight.code);
    },

    // 組別要顯示的量級分組（沒有量級的性別不顯示）
    weightGroups(category) {
      return ["male", "female", "mixed"]
        .map((gender) => ({ gender, items: this.weightListOf(category, gender) }))
        .filter((group) => group.items.length > 0);
    },

    genderLabel(gender) {
      if (gender === "male") return this.$t("male");
      if (gender === "female") return this.$t("female");

      return "混合";
    },

    // 組別的量級選擇尚未建立時，預設全部選取
    ensureWeightSelection(categoryCode) {
      if (!categoryCode || this.cwWeightSelected[categoryCode]) return;

      const category = (this.categories_weights || []).find(
        (cw) => cw.code === categoryCode
      );
      if (!category) return;

      this.cwWeightSelected[categoryCode] = {
        male: this.weightListOf(category, "male").map((weight) => weight.code),
        female: this.weightListOf(category, "female").map((weight) => weight.code),
        mixed: this.weightListOf(category, "mixed").map((weight) => weight.code),
      };
    },

    isWeightSelected(categoryCode, gender, weightCode) {
      const selection = this.cwWeightSelected[categoryCode];
      if (!selection) return true;

      return (selection[gender] || []).includes(weightCode);
    },

    toggleWeight(categoryCode, gender, weightCode) {
      this.ensureWeightSelection(categoryCode);

      const selection = this.cwWeightSelected[categoryCode];
      if (!selection) return;

      const codes = selection[gender] || [];
      selection[gender] = codes.includes(weightCode)
        ? codes.filter((code) => code !== weightCode)
        : [...codes, weightCode];
    },

    selectAllWeights(categoryCode, gender, selected) {
      this.ensureWeightSelection(categoryCode);

      const selection = this.cwWeightSelected[categoryCode];
      if (!selection) return;

      const category = (this.categories_weights || []).find(
        (cw) => cw.code === categoryCode
      );
      selection[gender] = selected
        ? this.weightListOf(category, gender).map((weight) => weight.code)
        : [];
    },

    // 依勾選狀態篩選出要儲存的量級
    filteredWeights(category, gender) {
      const weights = this.weightListOf(category, gender);
      const selection = this.cwWeightSelected[category.code];
      if (!selection) return weights;

      const codes = selection[gender] || [];

      return weights.filter((weight) => codes.includes(weight.code));
    },

    totalWeightCount(category) {
      return ["male", "female", "mixed"].reduce(
        (total, gender) => total + this.weightListOf(category, gender).length,
        0
      );
    },

    selectedWeightCount(category) {
      return ["male", "female", "mixed"].reduce(
        (total, gender) => total + this.filteredWeights(category, gender).length,
        0
      );
    },

    // 編輯既有賽事時，依儲存的量級還原勾選狀態（儲存時沒有的量級視為未選）
    initWeightSelectionFromSaved(savedCategories) {
      const selection = {};

      (savedCategories || []).forEach((saved) => {
        if (!saved || typeof saved !== "object" || !saved.code) return;

        const category = (this.categories_weights || []).find(
          (cw) => cw.code === saved.code
        );
        if (!category) return;

        const pickCodes = (gender) => {
          const savedCodes = (Array.isArray(saved[gender]) ? saved[gender] : [])
            .map((weight) =>
              weight && typeof weight === "object" ? weight.code : weight
            )
            .filter((code) => code !== undefined && code !== null)
            .map((code) => String(code));

          return this.weightListOf(category, gender)
            .filter((weight) => savedCodes.includes(String(weight.code)))
            .map((weight) => weight.code);
        };

        selection[saved.code] = {
          male: pickCodes("male"),
          female: pickCodes("female"),
          mixed: pickCodes("mixed"),
        };
      });

      this.cwWeightSelected = selection;
    },

    // 提取值從物件陣列
    extractValues(items, keyField) {
      if (!items || !Array.isArray(items)) return [];

      const values = [];
      items.forEach((item) => {
        if (typeof item === "object" && item !== null && item[keyField] !== undefined) {
          values.push(item[keyField]);
        } else if (typeof item === "string" || typeof item === "number") {
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

      if (Array.isArray(value)) return value;

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
        this.getDaysArray(this.competitionData.period[0], this.competitionData.period[1]);
        // 預設把期間內每一天都設為比賽日，使用者可再自行取消
        this.competitionData.match_dates = [...this.dateList];
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
      const isFormatInvalid = !this.uploadValidator.attachment.format.includes(
        file.type
      );

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
      // 檔案只在前端暫存，送出表單時才實際上傳
    },

    prepareFormData() {
      const formData = new FormData();

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
        const cwData = cwObjects.map((cw) => {
          const data = {
            code: cw.code,
            name: cw.name,
            male: this.filteredWeights(cw, "male"),
            description: cw.description,
            age_range: cw.age_range,
            female: this.filteredWeights(cw, "female"),
          };

          // 只有原本就有混合量級的組別才帶上 mixed，避免影響其他頁面
          if (this.weightListOf(cw, "mixed").length > 0) {
            data.mixed = this.filteredWeights(cw, "mixed");
          }

          return data;
        });
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

      // 裁判選項
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

      // 工作人員選項
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

      return formData;
    },

    async onFinish() {
      const emptyCategory = this.getObjectsByValues(
        this.competitionData.cwSelected,
        this.categories_weights,
        "code"
      ).find(
        (cw) => this.totalWeightCount(cw) > 0 && this.selectedWeightCount(cw) === 0
      );

      if (emptyCategory) {
        message.error(`「${emptyCategory.name}」至少需要選擇一個公斤級`);
        return;
      }

      this.loading = true;

      try {
        const formData = this.prepareFormData();

        if (this.mode === "CREATE") {
          await this.$inertia.post(route("manage.competitions.store"), formData, {
            onSuccess: () => {
              message.success("賽事創建成功");
            },
            onError: (errors) => {
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
                if (errors && Object.keys(errors).length > 0) {
                  message.error("表單有錯誤，請檢查");
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
.ja-section-title {
  margin: 0 0 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 15px;
  font-weight: 600;
  color: #334155;
}
.ja-option-box {
  display: block;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background-color: #fff;
  padding: 8px 10px;
  transition: all 0.15s ease-out;
}
.ja-option-box:hover {
  border-color: rgba(0, 129, 200, 0.45);
}
.ja-option-box-active {
  border-color: #0081c8;
  background-color: rgba(0, 129, 200, 0.06);
}
.ja-option-box :deep(.ant-checkbox-wrapper),
.ja-option-box :deep(.ant-radio-wrapper) {
  width: 100%;
  margin-inline-start: 0;
}
.ja-option-box-sm {
  padding: 6px 10px;
}
.ja-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  padding: 1px 10px;
  font-size: 12px;
  font-weight: 500;
  line-height: 20px;
  white-space: nowrap;
}
.ja-pill-blue {
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
}
.ja-score-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border-radius: 9999px;
  background-color: #fff;
  padding: 3px 12px;
  font-size: 12px;
  box-shadow: inset 0 0 0 1px #e2e8f0;
}
.ja-score-chip-label {
  color: #64748b;
}
.ja-score-chip-value {
  font-weight: 700;
  color: #0081c8;
}
.ja-mini-label {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
}
.ja-weight-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border-radius: 6px;
  background-color: #fff;
  padding: 1px 8px;
  font-size: 12px;
  color: #475569;
  box-shadow: inset 0 0 0 1px #e2e8f0;
}
.ja-weight-chip-toggle {
  border: 0;
  font-family: inherit;
  line-height: 18px;
  cursor: pointer;
  transition: all 0.15s ease-out;
}
.ja-weight-chip-toggle:hover {
  box-shadow: inset 0 0 0 1px rgba(0, 129, 200, 0.45);
}
.ja-weight-chip-toggle:not(.ja-weight-chip-off) {
  background-color: rgba(0, 129, 200, 0.08);
  color: #0081c8;
  font-weight: 600;
  box-shadow: inset 0 0 0 1px rgba(0, 129, 200, 0.35);
}
.ja-weight-chip-off {
  background-color: #f8fafc;
  color: #cbd5e1;
  text-decoration: line-through;
}
.ja-link {
  border: 0;
  background: transparent;
  padding: 0;
  font-family: inherit;
  font-size: 12px;
  font-weight: 500;
  line-height: 1;
  color: #0081c8;
  cursor: pointer;
}
.ja-link:hover {
  text-decoration: underline;
}
.ja-link-muted {
  color: #94a3b8;
}
.ja-upload-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 36px;
  border-radius: 8px;
  border-color: #e2e8f0;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
  box-shadow: none;
}
.ja-upload-btn:hover {
  color: #0081c8;
  border-color: rgba(0, 129, 200, 0.45);
}
</style>
