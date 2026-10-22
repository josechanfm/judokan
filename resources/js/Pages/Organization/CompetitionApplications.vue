<template>
  <OrganizationLayout :title="$t('applications')" :breadcrumb="breadcrumb">
    <div class="container mx-auto pt-5">
      <!-- 賽事資訊 -->
      <div class="card-judo p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <h2 class="m-0 text-xl font-bold text-gray-800">
              {{ competition.title_zh || competition.title_fn }}
            </h2>
            <div class="mt-2 flex flex-wrap items-center gap-2">
              <span class="ja-pill ja-pill-gray">
                <CalendarOutlined class="mr-1" />
                {{ competition.start_date }} ~ {{ competition.end_date }}
              </span>
              <span v-if="competition.score" class="ja-pill ja-pill-blue">
                <TrophyOutlined class="mr-1" />
                {{ competition.score.title }}
              </span>
              <span
                class="ja-pill"
                :class="competition.published ? 'ja-pill-green' : 'ja-pill-gray'"
              >
                {{ competition.published ? $t("published") : $t("draft") }}
              </span>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <a
              :href="receiptsUrl"
              target="_blank"
              class="btn-judo-outline"
              :class="{ 'pointer-events-none opacity-50': !selectedRowKeyIds.length }"
            >
              <PrinterOutlined />
              {{ $t("print_receipt") }}
              <span v-if="selectedRowKeyIds.length" class="ja-count">
                {{ selectedRowKeyIds.length }}
              </span>
            </a>
            <a
              :href="route('manage.competition.applications.export', competition.id)"
              class="btn-judo-outline"
            >
              <FileExcelOutlined />
              {{ $t("export_excel") }}
            </a>
            <button type="button" class="btn-judo-primary" @click="openAdd">
              <PlusOutlined />
              {{ $t("add_athlete") }}
            </button>
            <button type="button" class="btn-judo-outline" @click="visible = true">
              <UploadOutlined />
              {{ $t("import_athletes") }}
            </button>
            <inertia-link
              :href="route('manage.competition.results.index', competition.id)"
              class="btn-judo-outline"
            >
              <TrophyOutlined />
              {{ $t("competition_result") }}
            </inertia-link>
          </div>
        </div>
      </div>

      <!-- 統計 -->
      <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card-judo flex items-center gap-3 px-5 py-4">
          <span class="ja-stat-icon"><TeamOutlined /></span>
          <div>
            <p class="m-0 whitespace-nowrap text-xs text-gray-500">
              {{ $t("total") }}
            </p>
            <p class="m-0 text-lg font-semibold text-gray-800">
              {{ applications.length }}
            </p>
          </div>
        </div>
        <div class="card-judo flex items-center gap-3 px-5 py-4">
          <span class="ja-stat-icon ja-stat-green"><CheckCircleOutlined /></span>
          <div>
            <p class="m-0 whitespace-nowrap text-xs text-gray-500">
              {{ $t("competition_accepted") }}
            </p>
            <p class="m-0 text-lg font-semibold text-gray-800">{{ acceptedCount }}</p>
          </div>
        </div>
        <div class="card-judo flex items-center gap-3 px-5 py-4">
          <span class="ja-stat-icon ja-stat-amber"><ClockCircleOutlined /></span>
          <div>
            <p class="m-0 whitespace-nowrap text-xs text-gray-500">
              {{ $t("competition_unaccepted") }}
            </p>
            <p class="m-0 text-lg font-semibold text-gray-800">
              {{ applications.length - acceptedCount }}
            </p>
          </div>
        </div>
        <div class="card-judo flex items-center gap-3 px-5 py-4">
          <span class="ja-stat-icon"><UserOutlined /></span>
          <div>
            <p class="m-0 whitespace-nowrap text-xs text-gray-500">
              {{ $t("athlete") }}
            </p>
            <p class="m-0 text-lg font-semibold text-gray-800">{{ athleteCount }}</p>
          </div>
        </div>
      </div>

      <!-- 篩選 -->
      <div class="card-judo mt-4 p-5">
        <div class="flex flex-wrap items-end gap-3">
          <div>
            <label class="mb-1 block text-xs text-gray-500">{{ $t("keyword") }}</label>
            <a-input
              v-model:value="filters.keyword"
              :placeholder="$t('name_zh') + ' / ' + $t('name_fn')"
              allow-clear
              style="width: 220px"
              @press-enter="currentPage = 1"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-500">{{ $t("role") }}</label>
            <a-select
              v-model:value="filters.role"
              :options="roleOptions"
              allow-clear
              :placeholder="$t('all')"
              style="width: 140px"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-500">{{ $t("category") }}</label>
            <a-select
              v-model:value="filters.category"
              :options="categoryOptions"
              allow-clear
              :placeholder="$t('all')"
              style="width: 170px"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-500">
              {{ $t("competition_accepted") }}
            </label>
            <a-select
              v-model:value="filters.accepted"
              :options="acceptedOptions"
              allow-clear
              :placeholder="$t('all')"
              style="width: 130px"
            />
          </div>
          <button type="button" class="btn-judo-outline" @click="resetFilters">
            {{ $t("reset") }}
          </button>
        </div>
      </div>

      <!-- 報名列表 -->
      <div class="card-judo mt-4 p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 px-1">
          <p class="m-0 text-sm text-gray-500">
            共
            <span class="mx-1 font-semibold text-[#0081C8]">
              {{ filteredApplications.length }}
            </span>
            筆報名
          </p>
          <p v-if="selectedRowKeyIds.length" class="m-0 text-sm text-[#0081C8]">
            {{ $t("selected") }} {{ selectedRowKeyIds.length }}
          </p>
        </div>
        <div class="overflow-x-auto">
          <a-table
            :dataSource="filteredApplications"
            :columns="columns"
            :pagination="pagination"
            :rowSelection="rowSelection"
            :scroll="{ x: 1210 }"
            rowKey="id"
          >
            <template #headerCell="{ column }">
              <span class="whitespace-nowrap">
                <template v-if="column.i18n">{{ $t(column.i18n) }}</template>
                <template v-else-if="typeof column.title === 'string'">
                  {{ column.title }}
                </template>
              </span>
            </template>
            <template #emptyText>
              <div class="py-12 text-center text-gray-400">
                <p class="m-0 text-3xl">📋</p>
                <p class="m-0 mt-3 text-sm">{{ $t("no_application_records") }}</p>
              </div>
            </template>
            <template #bodyCell="{ column, record }">
              <template v-if="column.dataIndex == 'athlete'">
                <div class="flex items-center gap-3">
                  <a-avatar
                    :size="36"
                    :src="record.avatar_url || undefined"
                    class="ja-avatar shrink-0"
                  >
                    {{ (record.name_zh || record.name_fn || "?").slice(0, 1) }}
                  </a-avatar>
                  <div class="min-w-0">
                    <p class="m-0 truncate font-medium text-gray-800">
                      {{ record.name_zh || record.name_fn || "—" }}
                    </p>
                    <p class="m-0 truncate text-xs text-gray-400">
                      {{ record.name_fn }}
                      <template v-if="record.dob">
                        · {{ calculateAge(record.dob) }} 歲
                      </template>
                    </p>
                  </div>
                </div>
              </template>
              <template v-else-if="column.dataIndex == 'gender'">
                <span
                  class="ja-pill"
                  :class="
                    record.gender == 'F'
                      ? 'ja-pill-pink'
                      : record.gender == 'X'
                      ? 'ja-pill-gray'
                      : 'ja-pill-blue'
                  "
                >
                  {{
                    record.gender == "F"
                      ? $t("female")
                      : record.gender == "X"
                      ? "混合"
                      : $t("male")
                  }}
                </span>
              </template>
              <template v-else-if="column.dataIndex == 'organization'">
                <span class="text-gray-700">{{ organizationName(record) }}</span>
              </template>
              <template v-else-if="column.dataIndex == 'role'">
                <span
                  class="ja-pill"
                  :class="record.role == 'athlete' ? 'ja-pill-blue' : 'ja-pill-gray'"
                >
                  {{ roleName(record.role) }}
                </span>
              </template>
              <template v-else-if="column.dataIndex == 'category'">
                <div v-if="record.role == 'athlete'" class="flex flex-wrap gap-1">
                  <span class="ja-pill ja-pill-gray">{{ categoryName(record.category) }}</span>
                  <span v-if="record.weight" class="ja-pill ja-pill-blue">
                    {{ weightName(record) }}
                  </span>
                </div>
                <span v-else class="text-gray-300">—</span>
              </template>
              <template v-else-if="column.dataIndex == 'accepted'">
                <a-switch
                  :checked="record.accepted"
                  size="small"
                  @change="(checked) => acceptedConfirmed(record, checked)"
                />
              </template>
              <template v-else-if="column.dataIndex == 'competition_result'">
                <div class="flex items-center gap-1">
                  <a-select
                    v-if="scores"
                    :value="record.result_rank"
                    :options="rankOptions"
                    size="small"
                    allow-clear
                    placeholder="—"
                    style="width: 104px"
                    @change="(value) => onChangeResult(record, value)"
                  />
                  <span v-else class="text-gray-300">—</span>
                  <span v-if="record.result_score" class="ja-pill ja-pill-green">
                    {{ record.result_score }} {{ $t("score") }}
                  </span>
                </div>
              </template>
              <template v-else-if="column.dataIndex == 'operation'">
                <div class="ja-actions flex flex-wrap items-center gap-1">
                  <a-button
                    type="link"
                    size="small"
                    :href="route('manage.competition.application.success', record.id)"
                  >
                    {{ $t("view") }}
                  </a-button>
                  <a-button type="link" size="small" @click="sendMail(record)">
                    {{ $t("send_email") }}
                  </a-button>
                  <a-button type="link" size="small" @click="editRecord(record)">
                    {{ $t("edit") }}
                  </a-button>
                  <a-popconfirm
                    :title="$t('confirm_delete_record')"
                    :ok-text="$t('confirm')"
                    :cancel-text="$t('cancel')"
                    @confirm="deleteRecord(record)"
                  >
                    <a-button type="link" size="small" danger>
                      {{ $t("delete") }}
                    </a-button>
                  </a-popconfirm>
                </div>
              </template>
              <template v-else>
                {{ record[column.dataIndex] }}
              </template>
            </template>
          </a-table>
        </div>
      </div>
    </div>

    <!-- 新增運動員 -->
    <a-modal
      v-model:open="addModal.isOpen"
      :title="$t('add_athlete')"
      :width="760"
      :footer="null"
    >
      <div class="mb-4 flex gap-2">
        <button
          type="button"
          class="ja-tab"
          :class="{ 'ja-tab-active': addModal.source === 'member' }"
          @click="addModal.source = 'member'"
        >
          <TeamOutlined class="mr-1" />
          {{ $t("from_member") }}
        </button>
        <button
          type="button"
          class="ja-tab"
          :class="{ 'ja-tab-active': addModal.source === 'new' }"
          @click="switchToNew"
        >
          <UserOutlined class="mr-1" />
          {{ $t("new_athlete") }}
        </button>
      </div>

      <div v-if="addModal.source === 'member'" class="mb-4">
        <label class="mb-1 block text-xs text-gray-500">
          {{ $t("select_member") }}
        </label>
        <a-select
          v-model:value="addModal.member_id"
          :options="memberOptions"
          show-search
          allow-clear
          :filter-option="filterMember"
          :placeholder="$t('select_member')"
          style="width: 100%"
          @change="onPickMember"
        />
        <p class="m-0 mt-1 text-xs text-gray-400">
          選好會員後會自動帶入姓名、性別、出生日期與聯絡資料，只需再選組別與量級。
        </p>
      </div>

      <div class="grid grid-cols-1 gap-x-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("name_zh") }}</label>
          <a-input v-model:value="addModal.data.name_zh" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("name_fn") }}</label>
          <a-input v-model:value="addModal.data.name_fn" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("display_name") }}</label>
          <a-input v-model:value="addModal.data.display_name" placeholder="留空會用中文名／外文名" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("gender") }}</label>
          <a-radio-group v-model:value="addModal.data.gender" @change="onAddGenderChange">
            <a-radio value="M">{{ $t("male") }}</a-radio>
            <a-radio value="F">{{ $t("female") }}</a-radio>
          </a-radio-group>
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("dob") }}</label>
          <a-date-picker
            v-model:value="addModal.data.dob"
            :format="dateFormat"
            :valueFormat="dateFormat"
            style="width: 100%"
          />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("id_num") }}</label>
          <a-input v-model:value="addModal.data.id_num" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("mobile_number") }}</label>
          <a-input v-model:value="addModal.data.mobile" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("email") }}</label>
          <a-input v-model:value="addModal.data.email" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("role") }}</label>
          <a-select
            v-model:value="addModal.data.role"
            :options="competition.roles"
            style="width: 100%"
            @change="onAddRoleChange"
          />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">
            {{ $t("competition_accepted") }}
          </label>
          <div class="pt-1">
            <a-switch v-model:checked="addModal.data.accepted" />
          </div>
        </div>
        <div v-if="addModal.data.role === 'athlete'">
          <label class="mb-1 block text-xs text-gray-500">{{ $t("category") }}</label>
          <a-select
            v-model:value="addModal.data.category"
            :options="competition.categories_weights"
            :fieldNames="{ value: 'code', label: 'name' }"
            style="width: 100%"
            @change="onAddCategoryChange"
          />
        </div>
        <div v-if="addModal.data.role === 'athlete'">
          <label class="mb-1 block text-xs text-gray-500">{{ $t("weight") }}</label>
          <a-select
            v-model:value="addModal.data.weight"
            :options="addModal.weightList"
            :fieldNames="{ value: 'code', label: 'name' }"
            style="width: 100%"
          />
        </div>
      </div>

      <div class="mt-5 flex justify-end gap-2">
        <button type="button" class="btn-judo-outline" @click="addModal.isOpen = false">
          {{ $t("cancel") }}
        </button>
        <button
          type="button"
          class="btn-judo-primary"
          :disabled="addSaving"
          @click="submitAdd"
        >
          <SaveOutlined />
          {{ $t("save") }}
        </button>
      </div>
    </a-modal>

    <!-- Modal Start-->
    <a-modal v-model:open="modal.isOpen" :title="modal.title" width="60%">
      <a-form
        ref="modalRef"
        :model="modal.data"
        name="Teacher"
        :label-col="{ span: 4 }"
        autocomplete="off"
        :rules="rules"
        :validate-messages="validateMessages"
      >
        <a-form-item :label="$t('organization')" name="organization">
          <a-select
            :options="organizations.map((x) => ({ value: x.id, label: x.title }))"
            v-model:value="modal.data.organization_id"
          />
        </a-form-item>
        <a-form-item :label="$t('name_zh')" name="name_zh">
          <a-input
            @input="
              () => {
                this.modal.data.name_zh = this.modal.data.name_zh.toUpperCase();
              }
            "
            v-model:value="modal.data.name_zh"
          />
        </a-form-item>
        <a-form-item :label="$t('name_fn')" name="name_fn">
          <a-input
            @input="
              () => {
                this.modal.data.name_fn = this.modal.data.name_fn.toUpperCase();
              }
            "
            v-model:value="modal.data.name_fn"
          />
        </a-form-item>
        <a-row :span="24">
          <a-col :span="12">
            <a-form-item
              :label="$t('display_name')"
              name="display_name"
              :label-col="{ span: 8 }"
            >
              <a-input v-model:value="modal.data.display_name" />
            </a-form-item>
            <a-form-item :label="$t('email')" name="email" :label-col="{ span: 8 }">
              <a-input v-model:value="modal.data.email" />
            </a-form-item>
            <a-form-item :label="$t('gender')" name="gender" :label-col="{ span: 8 }">
              <a-radio-group
                v-model:value="modal.data.gender"
                button-style="solid"
                @change="onChangeGender"
              >
                <a-radio-button value="M">{{ $t("male") }}</a-radio-button>
                <a-radio-button value="F">{{ $t("female") }}</a-radio-button>
              </a-radio-group>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item :label="$t('id_num')" name="id_num" :label-col="{ span: 8 }">
              <a-input v-model:value="modal.data.id_num" />
            </a-form-item>
            <a-form-item
              :label="$t('mobile_number')"
              name="mobile"
              :label-col="{ span: 8 }"
            >
              <a-input v-model:value="modal.data.mobile" />
            </a-form-item>
            <a-form-item :label="$t('dob')" name="dob" :label-col="{ span: 8 }">
              <a-date-picker
                v-model:value="modal.data.dob"
                :format="dateFormat"
                :valueFormat="dateFormat"
              />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :span="24">
          <a-col :span="12">
            <a-form-item :label="$t('role')" name="role" :label-col="{ span: 8 }">
              <a-select v-model:value="modal.data.role" :options="competition.roles" />
            </a-form-item>
            <template v-if="modal.data.role == 'athlete'">
              <a-form-item
                :label="$t('category')"
                :label-col="{ span: 8 }"
                name="category"
              >
                <a-select
                  v-model:value="modal.data.category"
                  :options="competition.categories_weights"
                  :fieldNames="{ value: 'code', label: 'name' }"
                  @change="onChangeCategory"
                />
              </a-form-item>
              <a-form-item :label="$t('weight')" :label-col="{ span: 8 }" name="weight">
                <a-select
                  v-model:value="modal.data.weight"
                  :options="modal.data.weight_list"
                  :fieldNames="{ value: 'code', label: 'name' }"
                />
              </a-form-item>
            </template>
            <a-form-item
              :label="$t('competition_accepted')"
              name="accepted"
              :label-col="{ span: 8 }"
            >
              <a-switch
                v-model:checked="modal.data.accepted"
                :checked-children="$t('competition_accepted')"
                :un-checked-children="$t('competition_unaccepted')"
              />
            </a-form-item>
            <a-form-item
              :label="$t('competition_result')"
              name="resule"
              :label-col="{ span: 8 }"
            >
              <div class="flex items-center gap-2">
                <a-select
                  v-model:value="modal.data.result_rank"
                  :options="rankOptions"
                  allow-clear
                  placeholder="—"
                  style="width: 150px"
                />
                <span
                  v-if="modal.data.result_rank && scores"
                  class="ja-pill ja-pill-green"
                >
                  {{ scores[modal.data.result_rank] ?? 0 }} {{ $t("score") }}
                </span>
              </div>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <img
              v-if="modal.data.avatar_url"
              :src="modal.data.avatar_url"
              class="rounded-lg ring-1 ring-gray-200"
              width="200"
            />
            <div
              v-else
              class="flex h-32 w-40 items-center justify-center rounded-lg bg-slate-50 text-sm text-gray-300"
            >
              {{ $t("avatar") }}
            </div>
          </a-col>
        </a-row>
      </a-form>
      <template #footer>
        <a-button
          v-if="modal.mode == 'EDIT'"
          key="Update"
          type="primary"
          @click="updateRecord()"
          >{{ $t("update") }}</a-button
        >
        <a-button
          v-if="modal.mode == 'CREATE'"
          key="Store"
          type="primary"
          @click="storeRecord()"
          >{{ $t("add") }}</a-button
        >
      </template>
    </a-modal>
    <a-modal
      :title="$t('import_application')"
      v-model:open="visible"
      :width="640"
      @cancel="onImportClose"
    >
      <a-upload-dragger
        v-model:fileList="files"
        name="file"
        :beforeUpload="() => false"
        :multiple="false"
        :maxCount="1"
        accept=".xlsx,.xls"
      >
        <p class="ant-upload-drag-icon">
          <file-excel-outlined />
        </p>
        <p class="ant-upload-text">點擊或拖曳檔案到這裡導入報名資料</p>
        <p class="ant-upload-hint">
          支援 .xlsx / .xls 檔案，一次一個。請先下載下方的選手名單模板填寫；
          屬會名稱需與系統一致，組別、重量與角色必須是這場比賽有開放的項目。
        </p>
      </a-upload-dragger>

      <div class="mt-3" v-if="imported">
        <div class="font-bold my-3 text-green-600" v-if="errors.length === 0">
          成功導入 {{ importedCount }} 筆報名資料
        </div>
        <div class="font-bold my-3 text-yellow-600" v-else>
          成功導入 {{ importedCount }} 筆，以下 {{ errors.length }} 筆未導入
        </div>
        <p v-for="(error, index) in errors" :key="index" class="font-mono m-0">
          <warning-outlined class="!text-yellow-500" />
          第 {{ error.row }} 行（{{ error.attribute }}）: {{ error.errors[0] }}
        </p>
      </div>
      <template #footer>
        <div class="flex w-full justify-between">
          <a href="/templates/athlete_list.xlsx" download>
            <a-button type="link">
              <template #icon>
                <DownloadOutlined />
              </template>
              下載選手名單模板（athlete_list.xlsx）
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
            @click="handleImport"
            :disabled="files.length === 0"
            :loading="importLoading"
          >
            導入
          </a-button>
        </div>
      </template>
    </a-modal>
    <!-- Modal End-->
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import dayjs from "dayjs";
import { Modal } from "ant-design-vue";
import { message } from "ant-design-vue";
import { createVNode } from "vue";
import {
  CalendarOutlined,
  CheckCircleOutlined,
  ClockCircleOutlined,
  DownloadOutlined,
  ExclamationCircleOutlined,
  FileExcelOutlined,
  PlusOutlined,
  PrinterOutlined,
  SaveOutlined,
  TeamOutlined,
  TrophyOutlined,
  UploadOutlined,
  UserOutlined,
  WarningOutlined,
} from "@ant-design/icons-vue";

export default {
  components: {
    OrganizationLayout,
    CalendarOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    DownloadOutlined,
    ExclamationCircleOutlined,
    FileExcelOutlined,
    PlusOutlined,
    PrinterOutlined,
    SaveOutlined,
    TeamOutlined,
    TrophyOutlined,
    UploadOutlined,
    UserOutlined,
    WarningOutlined,
  },
  props: ["competitionResults", "competition", "organizations", "members"],
  data() {
    return {
      dateFormat: "YYYY-MM-DD",
      filters: {
        keyword: "",
        role: null,
        category: null,
        accepted: null,
      },
      currentPage: 1,
      pageSize: 20,
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      acceptDisabled: false,
      columns: [
        { title: "athlete", i18n: "athlete", dataIndex: "athlete", width: 230 },
        { title: "gender", i18n: "gender", dataIndex: "gender", width: 80 },
        {
          title: "organization",
          i18n: "organization",
          dataIndex: "organization",
          width: 150,
        },
        { title: "role", i18n: "role", dataIndex: "role", width: 110 },
        { title: "category", i18n: "category", dataIndex: "category", width: 200 },
        {
          title: "accepted",
          i18n: "competition_accepted",
          dataIndex: "accepted",
          width: 90,
        },
        {
          title: "result",
          i18n: "competition_result",
          dataIndex: "competition_result",
          width: 190,
        },
        {
          title: "operation",
          i18n: "operation",
          dataIndex: "operation",
          width: 260,
        },
      ],
      rules: {
        organization_id: { required: true },
        name_fn: { required: true },
        display_name: { required: true },
        id_num: { required: true },
        dob: { required: true },
        role: { required: true },
        email: { required: true, type: "email" },
        mobile: { required: true },
        state: { required: true },
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
      labelCol: {
        style: {
          width: "150px",
        },
      },
      selectedRowKeyIds: [],
      files: [],
      visible: false,
      // 新增運動員
      addSaving: false,
      addModal: {
        isOpen: false,
        source: "member",
        member_id: null,
        weightList: [],
        data: {
          member_id: null,
          name_zh: "",
          name_fn: "",
          display_name: "",
          gender: "M",
          dob: null,
          id_num: "",
          mobile: "",
          email: "",
          role: "athlete",
          category: null,
          weight: null,
          accepted: true,
        },
      },
      errors: [],
      imported: false,
      importedCount: 0,
      importLoading: false,
    };
  },
  computed: {
    breadcrumb() {
      return [
        { label: this.$t("competitions"), url: route("manage.competitions.index") },
        { label: this.$t("applications"), url: null },
      ];
    },
    applications() {
      return this.competition.applications || [];
    },
    memberOptions() {
      return (this.members || []).map((member) => ({
        value: member.id,
        label:
          [member.name_zh, member.name_fn].filter(Boolean).join(" ") +
          (member.member_number ? `（${member.member_number}）` : ""),
      }));
    },
    categories() {
      return this.competition.categories_weights || [];
    },
    // 積分表：優先用 competition_score 關聯，其次用報名時的快照
    scores() {
      const raw = this.competition.score || this.competition.result_scores;
      if (!raw) {
        return null;
      }
      if (typeof raw === "string") {
        try {
          return JSON.parse(raw);
        } catch (e) {
          return null;
        }
      }

      return raw;
    },
    rankOptions() {
      return (this.competitionResults || [])
        .filter((option) => option.value)
        .map((option) => ({ value: option.value, label: option.label }));
    },
    acceptedCount() {
      return this.applications.filter((item) => item.accepted).length;
    },
    athleteCount() {
      return this.applications.filter((item) => item.role === "athlete").length;
    },
    roleOptions() {
      return (this.competition.roles || []).map((role) => ({
        value: role.value,
        label: role.label,
      }));
    },
    categoryOptions() {
      return this.categories.map((category) => ({
        value: category.code,
        label: category.name,
      }));
    },
    acceptedOptions() {
      return [
        { value: "yes", label: this.$t("competition_accepted") },
        { value: "no", label: this.$t("competition_unaccepted") },
      ];
    },
    filteredApplications() {
      const keyword = (this.filters.keyword || "").trim().toLowerCase();

      return this.applications.filter((item) => {
        if (this.filters.role && item.role !== this.filters.role) {
          return false;
        }
        if (this.filters.category && item.category !== this.filters.category) {
          return false;
        }
        if (this.filters.accepted === "yes" && !item.accepted) {
          return false;
        }
        if (this.filters.accepted === "no" && item.accepted) {
          return false;
        }
        if (keyword) {
          const haystack = [item.name_zh, item.name_fn, item.id_num, item.display_name]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();
          if (!haystack.includes(keyword)) {
            return false;
          }
        }

        return true;
      });
    },
    pagination() {
      return {
        current: this.currentPage,
        pageSize: this.pageSize,
        total: this.filteredApplications.length,
        showSizeChanger: true,
        pageSizeOptions: ["10", "20", "50", "100"],
        showQuickJumper: true,
        showTotal: (total) => `共 ${total} 筆`,
        onChange: (page, pageSize) => {
          this.currentPage = page;
          if (pageSize) {
            this.pageSize = pageSize;
          }
        },
      };
    },
    rowSelection() {
      return {
        selectedRowKeys: this.selectedRowKeyIds,
        preserveSelectedRowKeys: true,
        onChange: (selectedRowKeys) => {
          this.selectedRowKeyIds = selectedRowKeys;
        },
      };
    },
    receiptsUrl() {
      return route("manage.competition.applications.receipts", {
        competition: this.competition.id,
        applicationIds: this.selectedRowKeyIds.join(","),
      });
    },
  },
  watch: {
    "filters.keyword"() {
      this.currentPage = 1;
    },
    "filters.role"() {
      this.currentPage = 1;
    },
    "filters.category"() {
      this.currentPage = 1;
    },
    "filters.accepted"() {
      this.currentPage = 1;
    },
  },
  methods: {
    // ===== 新增運動員 =====
    emptyAddData() {
      return {
        member_id: null,
        name_zh: "",
        name_fn: "",
        display_name: "",
        gender: "M",
        dob: null,
        id_num: "",
        mobile: "",
        email: "",
        role: "athlete",
        category: null,
        weight: null,
        accepted: true,
      };
    },
    openAdd() {
      this.addModal = {
        isOpen: true,
        source: "member",
        member_id: null,
        weightList: [],
        data: this.emptyAddData(),
      };
    },
    switchToNew() {
      this.addModal.source = "new";
      this.addModal.member_id = null;
      this.addModal.weightList = [];
      this.addModal.data = this.emptyAddData();
    },
    filterMember(input, option) {
      return (option.label || "").toLowerCase().includes((input || "").toLowerCase());
    },
    // 選了會員就把資料帶入表單
    onPickMember(memberId) {
      const member = (this.members || []).find((item) => item.id === memberId);

      if (!member) {
        return;
      }

      const dob = member.dob && !String(member.dob).startsWith("0000") ? member.dob : null;

      this.addModal.data = {
        ...this.addModal.data,
        member_id: member.id,
        name_zh: member.name_zh || "",
        name_fn: member.name_fn || "",
        display_name:
          member.display_name || member.name_zh || member.name_fn || "",
        gender: member.gender || "M",
        dob,
        mobile: member.mobile || "",
        email: member.email || "",
      };

      this.addGenWeightList();
    },
    onAddGenderChange() {
      this.addModal.data.weight = null;
      this.addGenWeightList();
    },
    onAddCategoryChange() {
      this.addModal.data.weight = null;
      this.addGenWeightList();
    },
    onAddRoleChange() {
      if (this.addModal.data.role !== "athlete") {
        this.addModal.data.category = null;
        this.addModal.data.weight = null;
      }
    },
    addGenWeightList() {
      const category = this.categories.find(
        (item) => item.code == this.addModal.data.category
      );
      const list = this.addModal.data.gender === "F" ? category?.female : category?.male;

      this.addModal.weightList = list || [];
    },
    submitAdd() {
      const data = this.addModal.data;

      if (!data.name_zh && !data.name_fn) {
        this.$message.warning("請輸入姓名（中文或外文至少一個）");
        return;
      }

      if (!data.role) {
        this.$message.warning("請選擇角色");
        return;
      }

      if (data.role === "athlete" && (!data.category || !data.weight)) {
        this.$message.warning("運動員必須選擇組別與量級");
        return;
      }

      this.addSaving = true;

      this.$inertia.post(
        route("manage.competition.applications.store", this.competition.id),
        data,
        {
          preserveScroll: true,
          onSuccess: () => {
            this.addSaving = false;
            this.addModal.isOpen = false;
            this.$message.success("已新增運動員");
          },
          onError: (errors) => {
            this.addSaving = false;
            this.$message.error(Object.values(errors || {})[0] || "新增失敗");
          },
        }
      );
    },
    onChangeGender(gender) {
      this.modal.data.category = null;
      this.modal.data.weight = null;
    },
    onChangeCategory(category) {
      this.modal.data.weight = null;
      this.genWeightList();
    },
    editRecord(record) {
      this.modal.data = { ...record };
      this.modal.mode = "EDIT";
      this.modal.title = this.$t("edit") + " · " + this.$t("applications");
      this.modal.isOpen = true;
      if (this.modal.data.category) {
        this.genWeightList();
      }
    },
    sendMail(record) {
      Modal.confirm({
        title: "是否確定",
        icon: createVNode(ExclamationCircleOutlined),
        content: "是否發送報名表郵件?",
        okText: "確定",
        cancelText: "取消",
        onOk: () => {
          this.$inertia.post(
            route("manage.competition.application.mail", record.id),
            {},
            {
              onSuccess: () => {
                message.success("發送成功");
              },
              onError: () => {
                message.error("發送失敗");
              },
            }
          );
        },
      });
    },
    deleteRecord(record) {
      this.$inertia.delete(
        route("manage.competition.applications.destroy", {
          competition: this.competition.id,
          application: record.id,
        }),
        {
          preserveScroll: true,
          onSuccess: () => {
            message.success("刪除成功");
          },
          onError: () => {
            message.error("刪除失敗");
          },
        }
      );
    },
    genWeightList() {
      const category = this.categories.find(
        (item) => item.code == this.modal.data.category
      );
      const list = this.modal.data.gender == "F" ? category?.female : category?.male;
      this.modal.data.weight_list = list || [];
    },
    updateRecord() {
      this.$refs.modalRef
        .validateFields()
        .then(() => {
          this.$inertia.put(
            route("manage.competition.applications.update", {
              competition: this.competition.id,
              application: this.modal.data.id,
            }),
            this.modal.data,
            {
              onSuccess: () => {
                this.modal.data = {};
                this.modal.isOpen = false;
                this.$message.success("報名資料已更新");
              },
              onError: () => {
                this.$message.error("更新失敗");
              },
            }
          );
        })
        .catch((err) => {
          console.log("error", err);
        });
    },
    calculateAge(birthDate) {
      if (!birthDate) return;
      const currentDate = new Date();
      if (new Date(birthDate) > currentDate) {
        var birthDate = null;
        var years = null;
        var months = null;
        var days = null;
        return "false";
      }

      const diffTime = currentDate - new Date(birthDate);
      const totalDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
      years = Math.floor(totalDays / 365.25);
      months = Math.floor((totalDays % 365.25) / 30.4375);
      days = Math.floor((totalDays % 365.25) % 30.4375);
      return years;
    },
    disabledDate(current) {
      return current > dayjs(new Date()).subtract(3, "year");
    },
    onCheckChange(selectedRowKeys, selectedRows) {},
    resetFilters() {
      this.filters = { keyword: "", role: null, category: null, accepted: null };
      this.currentPage = 1;
    },
    categoryName(code) {
      if (!code) {
        return "—";
      }
      const category = this.categories.find((item) => item.code == code);

      return category?.name || code;
    },
    weightName(record) {
      if (!record.weight) {
        return "";
      }
      const category = this.categories.find((item) => item.code == record.category);
      const list = record.gender == "F" ? category?.female : category?.male;
      const weight = (list || []).find((item) => item.code == record.weight);

      return weight?.name || record.weight;
    },
    roleName(code) {
      const role = (this.competition.roles || []).find((item) => item.value == code);

      return role?.label || code || "—";
    },
    organizationName(record) {
      const organization = (this.organizations || []).find(
        (item) => item.id == record.organization_id
      );

      return organization?.title || "—";
    },
    // 統一的更新入口：儲存後保留滾動位置並重新載入資料
    saveRecord(record, data, successMessage) {
      this.$inertia.put(
        route("manage.competition.applications.update", {
          competition: this.competition.id,
          application: record.id,
        }),
        { ...record, ...data },
        {
          preserveScroll: true,
          onSuccess: () => {
            this.$message.success(successMessage);
          },
          onError: () => {
            this.$message.error("更新失敗");
          },
        }
      );
    },
    handleImport() {
      if (this.files.length === 0) {
        this.$message.warning("請先選擇檔案");
        return;
      }

      this.importLoading = true;
      const formData = new FormData();
      formData.append("file", this.files[0].originFileObj);

      window.axios
        .post(
          route("manage.competition.applications.import", this.competition.id),
          formData,
          {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          }
        )
        .then(({ data }) => {
          this.importLoading = false;
          this.files = [];
          this.importedCount = data.imported || 0;
          this.errors = data.errors || [];
          this.$message.success(`成功導入 ${this.importedCount} 筆報名資料`);
          this.imported = true;
        })
        .catch((error) => {
          this.importLoading = false;
          console.error(error);
          this.$message.error("導入失敗");
        });
    },
    // 關閉匯入視窗時才重新載入，避免匯入結果提示被蓋掉
    onImportClose() {
      this.visible = false;
      if (this.imported) {
        this.imported = false;
        this.errors = [];
        this.$inertia.reload({ preserveScroll: true });
      }
    },
    acceptedConfirmed(record, checked) {
      record.accepted = checked;
      this.saveRecord(
        record,
        { accepted: checked },
        checked ? "已設為錄取" : "已設為不錄取"
      );
    },
    onChangeResult(record, value) {
      record.result_rank = value || null;
      this.saveRecord(record, { result_rank: record.result_rank }, "賽果已更新");
    },
  },
};
</script>

<style scoped>
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
.ja-pill-gray {
  background-color: #f1f5f9;
  color: #475569;
}
.ja-pill-green {
  background-color: rgba(34, 164, 93, 0.12);
  color: #17713f;
}
.ja-pill-pink {
  background-color: rgba(219, 39, 119, 0.1);
  color: #be185d;
}
.ja-count {
  display: inline-flex;
  min-width: 18px;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  background-color: #0081c8;
  padding: 0 5px;
  font-size: 11px;
  color: #fff;
  margin-left: 2px;
}
.ja-stat-icon {
  display: inline-flex;
  height: 38px;
  width: 38px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
  font-size: 18px;
}
.ja-stat-icon.ja-stat-green {
  background-color: rgba(34, 164, 93, 0.12);
  color: #17713f;
}
.ja-stat-icon.ja-stat-amber {
  background-color: rgba(245, 158, 11, 0.14);
  color: #b45309;
}
.ja-avatar {
  background-color: rgba(0, 129, 200, 0.12);
  color: #0081c8;
  font-weight: 600;
}
.ja-actions :deep(.ant-btn) {
  height: 26px;
  padding: 0 8px;
  font-size: 13px;
  font-weight: 500;
  box-shadow: none;
}
.ja-tab {
  display: inline-flex;
  align-items: center;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background-color: #fff;
  padding: 7px 14px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  transition: all 0.15s ease-out;
}
.ja-tab:hover {
  border-color: rgba(0, 129, 200, 0.45);
  color: #0081c8;
}
.ja-tab-active {
  background-color: #0081c8;
  border-color: #0081c8;
  color: #fff;
}
:deep(.ant-table-thead > tr > th) {
  background-color: #f8fafc;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
}
:deep(.ant-table-tbody > tr:hover > td) {
  background-color: rgba(0, 129, 200, 0.04);
}
</style>
