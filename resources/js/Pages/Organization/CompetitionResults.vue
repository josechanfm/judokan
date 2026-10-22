<template>
  <OrganizationLayout :title="$t('competition_result')" :breadcrumb="breadcrumb">
    <div class="container mx-auto pt-5">
      <!-- 賽事資訊 -->
      <div class="card-judo p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="min-w-0">
            <h2 class="m-0 text-xl font-bold text-gray-800">
              {{ competition.title_zh || competition.title_fn }}
            </h2>
            <div class="mt-2 flex flex-wrap items-center gap-2">
              <span class="member-pill member-pill-gray">
                <CalendarOutlined class="mr-1" />
                {{ competition.start_date }} ~ {{ competition.end_date }}
              </span>
              <span v-if="competition.score" class="member-pill member-pill-blue">
                <TrophyOutlined class="mr-1" />
                {{ competition.score.title }}
              </span>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <a
              :href="route('manage.competition.results.export', competition.id)"
              class="btn-judo-outline"
            >
              <FileExcelOutlined />
              {{ $t("export_results") }}
            </a>
            <button type="button" class="btn-judo-outline" @click="importVisible = true">
              <UploadOutlined />
              {{ $t("import_result_records") }}
            </button>
            <inertia-link
              :href="route('manage.competition.applications.index', competition.id)"
              class="btn-judo-outline"
            >
              {{ $t("applications") }}
            </inertia-link>
            <inertia-link
              :href="route('manage.competitions.index')"
              class="btn-judo-outline"
            >
              {{ $t("competitions") }}
            </inertia-link>
          </div>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <!-- 選擇組別／量級 -->
        <div class="card-judo p-6">
          <h3 class="m-0 mb-4 text-base font-semibold text-gray-700">
            {{ $t("category") }} / {{ $t("weight") }}
          </h3>

          <div
            v-if="!categories.length"
            class="py-8 text-center text-sm text-gray-400"
          >
            這場賽事沒有設定組別與量級
          </div>

          <div v-for="cw in categories" :key="cw.code" class="mb-4 last:mb-0">
            <p class="m-0 mb-2 flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-700">
              {{ cw.name }}
              <span v-if="cw.description" class="text-xs font-normal text-gray-400">
                {{ cw.description }}
              </span>
            </p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="w in cw.female || []"
                :key="'f-' + w.code"
                type="button"
                class="cw-chip"
                :class="{
                  'cw-chip-active':
                    selectedCategoryWeight === cw.code + '|F|' + w.code,
                }"
                @click="selectCategoryWeight(cw.code + '|F|' + w.code)"
              >
                <span class="cw-chip-gender">女</span>{{ w.name }}
              </button>
              <button
                v-for="w in cw.male || []"
                :key="'m-' + w.code"
                type="button"
                class="cw-chip"
                :class="{
                  'cw-chip-active':
                    selectedCategoryWeight === cw.code + '|M|' + w.code,
                }"
                @click="selectCategoryWeight(cw.code + '|M|' + w.code)"
              >
                <span class="cw-chip-gender">男</span>{{ w.name }}
              </button>
              <button
                v-for="w in cw.mixed || []"
                :key="'x-' + w.code"
                type="button"
                class="cw-chip"
                :class="{
                  'cw-chip-active':
                    selectedCategoryWeight === cw.code + '|X|' + w.code,
                }"
                @click="selectCategoryWeight(cw.code + '|X|' + w.code)"
              >
                <span class="cw-chip-gender">混合</span>{{ w.name }}
              </button>
            </div>
          </div>
        </div>

        <!-- 名次與積分 -->
        <div class="card-judo p-6">
          <h3 class="m-0 mb-4 text-base font-semibold text-gray-700">
            {{ $t("result_rank") }} / {{ $t("result_score") }}
          </h3>

          <div
            v-if="!selectedCategoryWeight"
            class="py-10 text-center text-sm text-gray-400"
          >
            請先在左邊選擇組別與量級
          </div>

          <template v-else>
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <span class="member-pill member-pill-blue">
                {{ selectedCategoryWeightLabel }}
              </span>
              <span class="text-xs text-gray-400">
                報名 {{ athletes.length }} 人
              </span>
            </div>

            <div
              v-for="option in rankOptions"
              :key="option.value"
              class="flex items-center gap-3 border-b border-slate-100 py-2 last:border-b-0"
            >
              <span class="w-20 shrink-0 text-sm font-medium text-gray-700">
                {{ option.label }}
              </span>
              <span class="w-14 shrink-0 text-xs text-[#0081C8]">
                {{ pointsOf(option.value) }} 分
              </span>
              <a-select
                v-model:value="option.application_id"
                :options="athletes"
                :fieldNames="{ value: 'id', label: 'display_name' }"
                :mode="isMulti(option.value) ? 'multiple' : undefined"
                allow-clear
                placeholder="選擇選手"
                class="min-w-0 flex-1"
                @change="onChangeResult"
              />
            </div>

            <div class="mt-4 flex justify-end gap-2">
              <button type="button" class="btn-judo-outline" @click="clearSelection">
                {{ $t("reset") }}
              </button>
              <button type="button" class="btn-judo-primary" @click="submitResult">
                <SaveOutlined />
                {{ $t("save") }}
              </button>
            </div>
          </template>
        </div>
      </div>

      <!-- 已記錄成績 -->
      <div class="card-judo mt-4">
        <div class="flex flex-wrap items-center justify-between gap-2 px-6 pt-5">
          <h3 class="m-0 text-base font-semibold text-gray-700">已記錄成績</h3>
          <span class="text-xs text-gray-400">
            共 {{ participateAthletes.length }} 筆
          </span>
        </div>
        <div class="mt-3 overflow-x-auto">
          <a-table
            :dataSource="participateAthletes"
            :columns="columns"
            :pagination="pagination"
            :scroll="{ x: 780 }"
            row-key="id"
          >
            <template #headerCell="{ column }">
              <template v-if="column.i18n">{{ $t(column.i18n) }}</template>
              <template v-else-if="typeof column.title === 'string'">
                {{ column.title }}
              </template>
            </template>
            <template #emptyText>
              <div class="py-10 text-center text-gray-400">
                <p class="m-0 text-3xl">🥋</p>
                <p class="m-0 mt-2 text-sm">這場賽事還沒有記錄成績</p>
              </div>
            </template>
            <template #bodyCell="{ column, record }">
              <template v-if="column.dataIndex == 'result_rank'">
                <span class="member-pill member-pill-blue">
                  {{ rankLabel(record.result_rank) }}
                </span>
              </template>
              <template v-else-if="column.dataIndex == 'result_score'">
                <span class="font-semibold text-[#0081C8]">
                  {{ record.result_score ?? "—" }}
                </span>
              </template>
              <template v-else-if="column.dataIndex == 'category'">
                {{ categoryLabel(record.category) }}
              </template>
              <template v-else-if="column.dataIndex == 'weight'">
                {{ weightLabel(record) }}
              </template>
              <template v-else-if="column.dataIndex == 'gender'">
                {{
                  record.gender === "F"
                    ? $t("female")
                    : record.gender === "M"
                    ? $t("male")
                    : record.gender === "X"
                    ? "混合"
                    : "—"
                }}
              </template>
              <template v-else>
                {{ record[column.dataIndex] ?? "—" }}
              </template>
            </template>
          </a-table>
        </div>
      </div>
    </div>

    <!-- 匯入賽果 -->
    <a-modal
      v-model:open="importVisible"
      :title="$t('import_result_records')"
      :width="660"
      @cancel="onImportClose"
    >
      <a-upload-dragger
        v-model:fileList="importFiles"
        name="file"
        :beforeUpload="() => false"
        :multiple="false"
        :maxCount="1"
        accept=".xlsx,.xls,.csv"
      >
        <p class="ant-upload-drag-icon">
          <FileExcelOutlined />
        </p>
        <p class="ant-upload-text">點擊或拖曳檔案到這裡匯入賽果</p>
        <p class="ant-upload-hint">
          支援 .xlsx / .xls / .csv，一次一個。欄位格式與「匯出賽果」相同；
          成績可填代號或中文（first / 第一名），積分留空會依積分表自動計算。
        </p>
      </a-upload-dragger>

      <div v-if="imported" class="mt-3">
        <p class="m-0 mb-2 font-semibold text-green-600">
          成功匯入 {{ importedCount }} 筆賽果
          <span v-if="createdCount" class="font-normal text-gray-500">
            （其中 {{ createdCount }} 筆自動新增報名）
          </span>
        </p>
        <div v-if="importErrors.length" class="text-yellow-600">
          <p class="m-0 mb-1 text-sm font-semibold">
            以下 {{ importErrors.length }} 筆未匯入
          </p>
          <p
            v-for="(error, index) in importErrors"
            :key="index"
            class="m-0 text-xs"
          >
            <WarningOutlined class="!text-yellow-500" />
            第 {{ error.row }} 行（{{ error.attribute }}）: {{ (error.errors || [])[0] }}
          </p>
        </div>
      </div>

      <template #footer>
        <div class="flex w-full flex-wrap items-center justify-between gap-2">
          <a href="/templates/result_list.xlsx" download>
            <a-button type="link">
              <template #icon>
                <DownloadOutlined />
              </template>
              下載成績範本（result_list.xlsx）
            </a-button>
          </a>
          <a-button
            v-if="imported"
            type="primary"
            class="bg-[#0081C8]"
            @click="onImportClose"
          >
            完成
          </a-button>
          <a-button
            v-else
            type="primary"
            class="bg-[#0081C8]"
            :disabled="importFiles.length === 0"
            :loading="importLoading"
            @click="submitImport"
          >
            匯入
          </a-button>
        </div>
      </template>
    </a-modal>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import {
  CalendarOutlined,
  DownloadOutlined,
  FileExcelOutlined,
  SaveOutlined,
  TrophyOutlined,
  UploadOutlined,
  WarningOutlined,
} from "@ant-design/icons-vue";

export default {
  components: {
    OrganizationLayout,
    CalendarOutlined,
    DownloadOutlined,
    FileExcelOutlined,
    SaveOutlined,
    TrophyOutlined,
    UploadOutlined,
    WarningOutlined,
  },
  props: ["competitionResults", "competition"],
  data() {
    return {
      selectedCategoryWeight: null,
      athletes: [],
      participateAthletes: [],
      // 匯入賽果
      importVisible: false,
      importFiles: [],
      importLoading: false,
      imported: false,
      importedCount: 0,
      createdCount: 0,
      importErrors: [],
      // 複製一份名次選項，避免直接修改 props
      rankOptions: (this.competitionResults || [])
        .filter((option) => option.value)
        .map((option) => ({
          value: option.value,
          label: option.label || option.label_zh || option.value,
          application_id: ["advance", "participate"].includes(option.value)
            ? []
            : null,
        })),
      columns: [
        { title: "display_name", i18n: "display_name", dataIndex: "display_name" },
        { title: "gender", i18n: "gender", dataIndex: "gender", width: 90 },
        { title: "category", i18n: "category", dataIndex: "category", width: 140 },
        { title: "weight", i18n: "weight", dataIndex: "weight", width: 120 },
        { title: "result_rank", i18n: "result_rank", dataIndex: "result_rank", width: 110 },
        { title: "result_score", i18n: "result_score", dataIndex: "result_score", width: 100 },
      ],
      pagination: {
        defaultPageSize: 20,
        showSizeChanger: true,
        pageSizeOptions: ["10", "20", "50", "100"],
      },
    };
  },
  computed: {
    breadcrumb() {
      return [
        { label: this.$t("competitions"), url: route("manage.competitions.index") },
        { label: this.$t("competition_result"), url: null },
      ];
    },
    categories() {
      return this.competition.categories_weights || [];
    },
    selectedCategoryWeightLabel() {
      if (!this.selectedCategoryWeight) {
        return "";
      }
      const [category, gender, weight] = this.selectedCategoryWeight.split("|");
      const cw = this.categories.find((item) => item.code === category);
      const list =
        gender === "F" ? cw?.female : gender === "X" ? cw?.mixed : cw?.male;
      const w = (list || []).find((item) => item.code === weight);
      const genderLabel =
        gender === "F" ? this.$t("female") : gender === "X" ? "混合" : this.$t("male");

      return `${cw?.name || category} · ${genderLabel} · ${w?.name || weight}`;
    },
  },
  created() {
    this.resetParams();
  },
  watch: {
    // 成績儲存或匯入後 props 會更新，這裡同步刷新「已記錄成績」
    competition() {
      this.refreshRecorded();
    },
  },
  methods: {
    refreshRecorded() {
      this.participateAthletes = (this.competition.applications || []).filter(
        (application) => application.result_rank
      );
    },
    isMulti(value) {
      return ["advance", "participate"].includes(value);
    },
    // 該名次在這場比賽可得的積分
    pointsOf(value) {
      const scores = this.competition.score || this.competition.result_scores || {};

      return scores[value] ?? 0;
    },
    rankLabel(value) {
      const option = (this.competitionResults || []).find((item) => item.value === value);

      return option?.label || value || "—";
    },
    categoryLabel(code) {
      const category = this.categories.find((item) => item.code == code);

      return category?.name || code || "—";
    },
    weightLabel(record) {
      if (!record.weight) {
        return "—";
      }
      const category = this.categories.find((item) => item.code == record.category);
      const list =
        record.gender === "F"
          ? category?.female
          : record.gender === "X"
          ? category?.mixed
          : category?.male;
      const weight = (list || []).find((item) => item.code == record.weight);

      return weight?.name || record.weight;
    },
    clearSelection() {
      this.rankOptions.forEach((option) => {
        option.application_id = this.isMulti(option.value) ? [] : null;
      });
      this.onChangeResult();
    },
    resetParams() {
      this.participateAthletes = (this.competition.applications || []).filter(
        (application) => application.result_rank
      );
      this.selectedCategoryWeight = null;
      this.athletes = [];
      this.clearSelection();
    },
    selectCategoryWeight(value) {
      this.selectedCategoryWeight = value;
      this.onChangeCW();
    },
    onChangeCW() {
      const [category, gender, weight] = (this.selectedCategoryWeight || "").split("|");

      this.athletes = (this.competition.applications || []).filter(
        (application) =>
          application.category === category &&
          application.gender === gender &&
          application.weight === weight
      );

      // 已記錄的名次帶回表單
      this.rankOptions.forEach((option) => {
        option.application_id = this.isMulti(option.value) ? [] : null;
        this.athletes.forEach((athlete) => {
          if (athlete.result_rank === option.value) {
            if (this.isMulti(option.value)) {
              option.application_id.push(athlete.id);
            } else {
              option.application_id = athlete.id;
            }
          }
        });
      });
      this.onChangeResult();
    },
    // 已被其他名次選走的人不能再選
    onChangeResult() {
      const selected = new Set();
      this.rankOptions.forEach((option) => {
        const value = option.application_id;
        if (Array.isArray(value)) {
          value.forEach((id) => selected.add(id));
        } else if (value) {
          selected.add(value);
        }
      });
      this.athletes.forEach((athlete) => {
        athlete.disabled = selected.has(athlete.id);
      });
    },
    submitResult() {
      if (!this.selectedCategoryWeight) {
        return;
      }

      this.$inertia.post(
        route("manage.competition.results.store", { competition: this.competition.id }),
        {
          cgw: this.selectedCategoryWeight.split("|"),
          results: this.rankOptions.map((option) => ({
            value: option.value,
            application_id: option.application_id,
          })),
        },
        {
          onSuccess: () => {
            this.refreshRecorded();
            this.$message.success("成績已儲存");
          },
          onError: () => {
            this.$message.error("儲存失敗");
          },
        }
      );
    },
    // 匯入賽果
    submitImport() {
      if (!this.importFiles.length) {
        this.$message.warning("請先選擇檔案");
        return;
      }

      this.importLoading = true;
      const formData = new FormData();
      formData.append("file", this.importFiles[0].originFileObj);

      window.axios
        .post(
          route("manage.competition.results.import", this.competition.id),
          formData,
          { headers: { "Content-Type": "multipart/form-data" } }
        )
        .then(({ data }) => {
          this.importLoading = false;
          this.importFiles = [];
          this.importedCount = data.imported || 0;
          this.createdCount = data.created || 0;
          this.importErrors = data.errors || [];
          this.imported = true;
          this.$message.success(`成功匯入 ${this.importedCount} 筆賽果`);
        })
        .catch((error) => {
          this.importLoading = false;
          this.$message.error(
            error?.response?.data?.message || error?.response?.data?.errors?.file?.[0] || "匯入失敗"
          );
        });
    },
    // 關閉匯入視窗時才重新載入，避免匯入結果提示被蓋掉
    onImportClose() {
      this.importVisible = false;
      if (this.imported) {
        this.imported = false;
        this.importErrors = [];
        this.$inertia.reload({ preserveScroll: true });
      }
    },
  },
};
</script>

<style scoped>
.cw-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border-radius: 9999px;
  border: 1px solid #e2e8f0;
  background-color: #fff;
  padding: 3px 12px;
  font-size: 13px;
  color: #475569;
  transition: all 0.15s ease-out;
}
.cw-chip:hover {
  border-color: rgba(0, 129, 200, 0.45);
  color: #0081c8;
}
.cw-chip-active {
  background-color: #0081c8;
  border-color: #0081c8;
  color: #fff;
}
.cw-chip-gender {
  font-size: 10px;
  opacity: 0.7;
}
.member-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  padding: 1px 10px;
  font-size: 12px;
  font-weight: 500;
  line-height: 20px;
  white-space: nowrap;
}
.member-pill-blue {
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
}
.member-pill-gray {
  background-color: #f1f5f9;
  color: #475569;
}
</style>
