<template>
  <div class="space-y-6">
    <!-- Top Header Title & Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">
          {{ activeJobTitle ? `Pelamar: ${activeJobTitle}` : 'Data Pelamar Kerja' }}
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          Pantau seluruh data kandidat pelamar, kualifikasi, dan tahapan seleksi
        </p>
      </div>

      <!-- Controls & View Switcher -->
      <div class="flex items-center gap-2.5">
        <!-- Active Filter Indicator -->
        <button
          v-if="activeJobId"
          @click="resetJobFilter"
          class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold border border-blue-200 flex items-center gap-1.5 transition-colors cursor-pointer"
          title="Tampilkan Semua Pelamar"
        >
          <span>Filter: {{ activeJobTitle }}</span>
          <span class="text-blue-500 font-bold">&times;</span>
        </button>

        <!-- Evaluasi Kualifikasi Action Button -->
        <button
          @click="startRescreening"
          :disabled="isScreening"
          class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
          title="Jalankan evaluasi kualifikasi otomatis untuk pelamar"
        >
          <RefreshCw class="w-3.5 h-3.5 text-slate-500" :class="{ 'animate-spin': isScreening }" />
          <span>Evaluasi Kualifikasi</span>
        </button>

        <!-- View Switcher (Table / Kanban) -->
        <div class="inline-flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200">
          <button
            @click="viewMode = 'table'"
            :class="[
              'px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
              viewMode === 'table' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <ListFilter class="w-3.5 h-3.5" />
            <span>Tabel</span>
          </button>
          <button
            @click="viewMode = 'kanban'"
            :class="[
              'px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
              viewMode === 'kanban' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <Kanban class="w-3.5 h-3.5" />
            <span>Kanban</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Alert / Toast Message -->
    <div
      v-if="toastMessage"
      :class="[
        'p-3 rounded-lg border flex items-center justify-between text-xs font-medium transition-all shadow-2xs',
        toastType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'
      ]"
    >
      <div class="flex items-center gap-2">
        <CheckCircle2 v-if="toastType === 'success'" class="w-4 h-4 text-emerald-600" />
        <AlertCircle v-else class="w-4 h-4 text-rose-600" />
        <span>{{ toastMessage }}</span>
      </div>
      <button @click="toastMessage = null" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
    </div>

    <!-- Integrated Filter Tabs, Stage Filter Dropdown & Search Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 border-b border-slate-200 pb-4">
      <!-- Match Filter Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1 lg:pb-0">
        <button
          @click="matchFilter = 'all'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            matchFilter === 'all'
              ? 'bg-slate-900 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Semua Pelamar <span class="ml-1 opacity-70">({{ applications.length }})</span>
        </button>

        <button
          @click="matchFilter = 'recommended'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            matchFilter === 'recommended'
              ? 'bg-emerald-600 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Sangat Sesuai <span class="ml-1 opacity-70">({{ recommendedCount }})</span>
        </button>

        <button
          @click="matchFilter = 'considered'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            matchFilter === 'considered'
              ? 'bg-amber-600 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Dipertimbangkan <span class="ml-1 opacity-70">({{ consideredCount }})</span>
        </button>

        <button
          @click="matchFilter = 'not_suitable'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            matchFilter === 'not_suitable'
              ? 'bg-slate-700 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Kurang Sesuai <span class="ml-1 opacity-70">({{ notSuitableCount }})</span>
        </button>
      </div>

      <!-- Right Controls: Stage Filter Dropdown + Search Bar -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
        <!-- Stage Filter Dropdown -->
        <div class="relative min-w-[220px]">
          <ListFilter class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
          <select
            v-model="stageFilter"
            :class="[
              'w-full bg-white border rounded-lg pl-8.5 pr-8 py-1.5 text-xs transition-all shadow-2xs cursor-pointer appearance-none font-medium',
              stageFilter !== 'all'
                ? 'border-blue-500 text-blue-700 bg-blue-50/50 font-semibold ring-1 ring-blue-500/30'
                : 'border-slate-200 text-slate-700 hover:border-slate-300'
            ]"
            title="Filter pelamar berdasarkan tahapan seleksi"
          >
            <option value="all">Tahap: Semua Tahapan ({{ applications.length }})</option>
            <option v-for="stg in stages" :key="stg.id" :value="stg.id">
              Tahap: {{ stg.name }} ({{ getStageCandidateCount(stg.id) }})
            </option>
            <option value="rejected">Status: Ditolak ({{ rejectedCandidateCount }})</option>
          </select>
          <ChevronDown class="w-3.5 h-3.5 absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
        </div>

        <!-- Reset Button if Stage Filter is Active -->
        <button
          v-if="stageFilter !== 'all'"
          @click="stageFilter = 'all'"
          class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0 flex items-center gap-1"
          title="Reset filter tahapan"
        >
          <span>Reset</span>
          <span class="text-slate-400 font-bold">&times;</span>
        </button>

        <!-- Search Bar -->
        <div class="relative w-full sm:w-64">
          <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama, email, atau posisi..."
            class="w-full bg-white border border-slate-200 rounded-lg pl-9 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
          />
        </div>
      </div>
    </div>

    <!-- TABLE VIEW -->
    <div
      v-if="viewMode === 'table'"
      class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr v-if="!selectedAppIds.length" class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <th class="py-2.5 px-2.5 w-9 text-center align-middle">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-3.5 h-3.5"
                  title="Pilih Semua Pelamar"
                />
              </th>
              <th class="py-2.5 px-2.5 font-semibold text-[11px] text-left align-middle whitespace-nowrap">Kandidat Pelamar</th>
              <th class="py-2.5 px-2.5 font-semibold text-[11px] text-left align-middle whitespace-nowrap">Posisi Dilamar</th>
              <th class="py-2.5 px-2 font-semibold text-[11px] text-center align-middle whitespace-nowrap">Kualifikasi Match</th>
              <th class="py-2.5 px-2 font-semibold text-[11px] text-center align-middle whitespace-nowrap">Tahapan Seleksi</th>
              <th class="py-2.5 px-2 font-semibold text-[11px] text-center align-middle whitespace-nowrap">Status</th>
              <th class="py-2.5 px-2 font-semibold text-[11px] text-center align-middle whitespace-nowrap">Tgl Masuk</th>
              <th class="py-2.5 px-2 font-semibold text-[11px] text-center align-middle whitespace-nowrap w-24">Aksi</th>
            </tr>
            <tr v-else class="bg-blue-50/90 text-blue-900 border-b border-blue-200/80">
              <th class="py-2 px-2.5 w-9 text-center align-middle">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="rounded border-blue-400 text-blue-600 focus:ring-blue-500 cursor-pointer w-3.5 h-3.5"
                  title="Pilih / Batalkan Semua"
                />
              </th>
              <th colspan="7" class="py-2 px-2.5 font-medium align-middle">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-xs text-blue-950">{{ selectedAppIds.length }} pelamar dipilih</span>
                    <span class="text-blue-300">&bull;</span>
                    <button
                      type="button"
                      @click="selectedAppIds = []"
                      class="text-xs text-blue-700 hover:text-blue-950 underline cursor-pointer font-medium"
                    >
                      Batalkan pilihan
                    </button>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      @click="bulkRejectSelected"
                      class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-lg text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                      title="Tolak pelamar terpilih"
                    >
                      <UserX class="w-3.5 h-3.5" />
                      <span>Tolak</span>
                    </button>
                    <button
                      type="button"
                      @click="openBulkNotificationModal"
                      class="px-3 py-1 bg-[#0c2340] hover:bg-[#15325b] text-white font-medium rounded-lg text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                    >
                      <Send class="w-3.5 h-3.5" />
                      <span>Kirim Notifikasi Massal</span>
                    </button>
                  </div>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="app in filteredApplications"
              :key="app.id"
              :class="['hover:bg-slate-50 transition-colors group', isSelected(app.id) ? 'bg-blue-50/40' : '']"
            >
              <!-- Checkbox -->
              <td class="py-2.5 px-2.5 align-middle text-center" @click.stop>
                <input
                  type="checkbox"
                  :value="app.id"
                  v-model="selectedAppIds"
                  class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-3.5 h-3.5"
                />
              </td>

              <!-- Name & Contact -->
              <td class="py-2.5 px-2.5 align-middle text-left">
                <div class="flex items-center gap-2.5 min-w-0">
                  <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200">
                    <User class="w-3.5 h-3.5 text-slate-500" />
                  </div>
                  <div class="min-w-0">
                    <div class="font-bold text-xs text-slate-900 hover:text-blue-600 transition-colors cursor-pointer truncate" @click="openDetail(app)">
                      {{ app.full_name }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-[200px]" :title="`${app.email || '-'} • ${app.whatsapp_number || app.phone || '-'}`">
                      {{ app.email || '-' }} &bull; {{ app.whatsapp_number || app.phone || '-' }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- Job Title -->
              <td class="py-2.5 px-2.5 align-middle text-left text-slate-700 font-medium">
                <div class="truncate max-w-[170px]" :title="app.job_posting?.title">
                  {{ app.job_posting?.title || '-' }}
                </div>
              </td>

              <!-- Score / Match (Centered) -->
              <td class="py-2.5 px-2 align-middle text-center">
                <div v-if="app.ai_match_score !== null && app.ai_match_score !== undefined" class="flex items-center justify-center">
                  <button
                    type="button"
                    @click.stop="openAnalysisModal(app)"
                    :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-semibold border cursor-pointer hover:shadow-xs transition-all hover:scale-105 active:scale-95 whitespace-nowrap', getAiBadgeClasses(app.ai_match_score)]"
                    title="Klik untuk melihat hasil analisis kualifikasi"
                  >
                    {{ app.ai_match_score }}% &bull; {{ formatAiRecommendation(app.ai_recommendation) }}
                  </button>
                </div>
                <div v-else class="text-[10.5px] text-slate-400 italic text-center">Menunggu evaluasi</div>
              </td>

              <!-- Stage (Centered, Clean Dropdown, NO Blue Dot) -->
              <td class="py-2.5 px-2 align-middle text-center" @click.stop>
                <div class="flex items-center justify-center">
                  <select
                    :value="app.status === 'rejected' ? 'rejected' : (app.current_stage_id || app.stage?.id || 1)"
                    @change="handleStageChange(app, $event.target.value)"
                    :class="[
                      'text-xs font-medium rounded-lg px-2 py-1 border cursor-pointer transition-all shadow-2xs',
                      app.status === 'rejected'
                        ? 'bg-rose-50 border-rose-300 text-rose-700 font-semibold'
                        : 'bg-white border-slate-200 text-slate-700 hover:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-500'
                    ]"
                    title="Ubah tahapan kandidat"
                  >
                    <option v-for="stg in stages" :key="stg.id" :value="stg.id">
                      {{ stg.name }}
                    </option>
                    <option value="rejected" class="text-rose-600 font-semibold">
                      Ditolak
                    </option>
                  </select>
                </div>
              </td>

              <!-- Status (Centered, single line badge) -->
              <td class="py-2.5 px-2 align-middle text-center">
                <div class="flex items-center justify-center">
                  <span :class="['inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-semibold border whitespace-nowrap', getStatusBadge(app.status)]">
                    {{ formatStatus(app.status) }}
                  </span>
                </div>
              </td>

              <!-- Date (Centered) -->
              <td class="py-2.5 px-2 align-middle text-center text-slate-600 whitespace-nowrap text-[11px]">
                {{ app.created_at }}
              </td>

              <!-- Action (Centered, Clean No-Slop Icon Button Group) -->
              <td class="py-2 px-2 align-middle text-center whitespace-nowrap">
                <div class="inline-flex items-center justify-center p-0.5 bg-slate-100/80 border border-slate-200/90 rounded-lg shadow-2xs">
                  <button
                    type="button"
                    @click="openDetail(app)"
                    class="w-7 h-7 rounded-md flex items-center justify-center text-slate-500 hover:text-blue-600 hover:bg-white hover:shadow-2xs active:scale-95 transition-all cursor-pointer"
                    title="Detail Profil Kandidat"
                  >
                    <Eye class="w-3.5 h-3.5" />
                  </button>
                  <div class="w-px h-3.5 bg-slate-200/90 mx-0.5"></div>
                  <button
                    type="button"
                    @click.stop="openSendEmailModal(app)"
                    class="w-7 h-7 rounded-md flex items-center justify-center text-slate-500 hover:text-blue-600 hover:bg-white hover:shadow-2xs active:scale-95 transition-all cursor-pointer"
                    title="Kirim Notifikasi (Email / WhatsApp)"
                  >
                    <Send class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!filteredApplications.length">
              <td colspan="8" class="py-12 text-center text-xs text-slate-500">
                Tidak ada data kandidat pelamar yang sesuai kriteria filter.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- KANBAN BOARD VIEW -->
    <div v-else class="flex gap-4 overflow-x-auto pb-4 custom-scrollbar min-h-[calc(100vh-280px)] items-start select-none">
      <div
        v-for="stage in stages"
        :key="stage.id"
        class="w-80 shrink-0 bg-slate-100/70 border rounded-2xl flex flex-col max-h-[calc(100vh-280px)] transition-all shadow-2xs"
        :class="dragOverStageId === stage.id ? 'border-blue-500 bg-blue-50/50 ring-2 ring-blue-400/30' : 'border-slate-200'"
        @dragover.prevent="handleDragOver(stage.id)"
        @dragleave="handleDragLeave(stage.id)"
        @drop.prevent="handleDrop(stage.id, $event)"
      >
        <!-- Column Header -->
        <div class="p-3.5 border-b border-slate-200/80 bg-white/80 backdrop-blur-xs rounded-t-2xl flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: stage.color || '#64748b' }"></span>
            <span class="text-xs font-bold text-slate-800 tracking-tight">{{ stage.name }}</span>
          </div>
          <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
            {{ getStageApplications(stage.id).length }}
          </span>
        </div>

        <!-- Kanban Cards List -->
        <div class="p-3 space-y-3 overflow-y-auto flex-1 custom-scrollbar">
          <div
            v-for="app in getStageApplications(stage.id)"
            :key="app.id"
            class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs hover:shadow-xs transition-all cursor-grab active:cursor-grabbing hover:border-slate-300 group"
            draggable="true"
            @dragstart="handleDragStart(app, $event)"
            @dragend="handleDragEnd"
            @click="openDetail(app)"
          >
            <!-- Top: Candidate Name & Match Pill -->
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200">
                  <User class="w-3.5 h-3.5 text-slate-500" />
                </div>
                <div class="min-w-0">
                  <h4 class="font-bold text-xs text-slate-900 group-hover:text-blue-600 transition-colors truncate">
                    {{ app.full_name }}
                  </h4>
                  <p class="text-[11px] text-slate-400 truncate">{{ app.email }}</p>
                </div>
              </div>

              <button
                v-if="app.ai_match_score !== null && app.ai_match_score !== undefined"
                type="button"
                @click.stop="openAnalysisModal(app)"
                :class="['px-2 py-0.5 rounded-full text-[10px] font-semibold border shrink-0 hover:shadow-xs transition-transform cursor-pointer', getAiBadgeClasses(app.ai_match_score)]"
                title="Klik untuk melihat hasil analisis kualifikasi"
              >
                {{ app.ai_match_score }}%
              </button>
            </div>

            <!-- Role / Details Subtitle -->
            <div v-if="!activeJobId && app.job_posting?.title" class="text-[11px] font-medium text-slate-600 mt-2.5 pt-2 border-t border-slate-100 line-clamp-1">
              {{ app.job_posting.title }}
            </div>

            <!-- Card Footer -->
            <div class="flex items-center justify-between text-[10px] text-slate-400 mt-2.5 pt-2 border-t border-slate-100">
              <span>{{ app.created_at }}</span>
              <span class="px-1.5 py-0.5 rounded bg-slate-50 text-slate-600 border border-slate-100 font-medium">
                {{ app.source || 'Portal' }}
              </span>
            </div>
          </div>

          <!-- Empty State in Column -->
          <div
            v-if="!getStageApplications(stage.id).length"
            class="py-10 text-center text-xs text-slate-400 border border-dashed border-slate-200/80 rounded-xl flex flex-col items-center justify-center gap-1"
          >
            <span class="text-slate-300 text-sm">&empty;</span>
            <span>Belum ada kandidat</span>
          </div>
        </div>
      </div>

        <!-- Ditolak Kanban Column -->
        <div
          class="w-80 shrink-0 bg-rose-50/40 border rounded-2xl flex flex-col max-h-[calc(100vh-280px)] transition-all shadow-2xs"
          :class="dragOverStageId === 'rejected' ? 'border-rose-500 bg-rose-100/50 ring-2 ring-rose-400/30' : 'border-rose-200/80'"
          @dragover.prevent="handleDragOver('rejected')"
          @dragleave="handleDragLeave('rejected')"
          @drop.prevent="handleDrop('rejected', $event)"
        >
          <!-- Column Header -->
          <div class="p-3.5 border-b border-rose-200/80 bg-white/80 backdrop-blur-xs rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
              <span class="text-xs font-bold text-rose-900 tracking-tight">Ditolak</span>
            </div>
            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200">
              {{ rejectedCandidateCount }}
            </span>
          </div>

          <!-- Kanban Cards List for Rejected -->
          <div class="p-3 space-y-3 overflow-y-auto flex-1 custom-scrollbar">
            <div
              v-for="app in rejectedApplications"
              :key="app.id"
              class="bg-white p-4 rounded-xl border border-rose-200/90 shadow-2xs hover:shadow-xs transition-all cursor-grab active:cursor-grabbing hover:border-rose-300 group opacity-90"
              draggable="true"
              @dragstart="handleDragStart(app, $event)"
              @dragend="handleDragEnd"
              @click="openDetail(app)"
            >
              <!-- Top: Candidate Name & Match Pill -->
              <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                  <div class="w-7 h-7 rounded-full bg-rose-50 flex items-center justify-center shrink-0 border border-rose-200">
                    <User class="w-3.5 h-3.5 text-rose-500" />
                  </div>
                  <div class="min-w-0">
                    <h4 class="font-bold text-xs text-slate-900 group-hover:text-rose-600 transition-colors truncate">
                      {{ app.full_name }}
                    </h4>
                    <p class="text-[11px] text-slate-400 truncate">{{ app.email }}</p>
                  </div>
                </div>

                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border shrink-0 bg-rose-50 text-rose-700 border-rose-200">
                  Ditolak
                </span>
              </div>

              <!-- Role / Details Subtitle -->
              <div v-if="!activeJobId && app.job_posting?.title" class="text-[11px] font-medium text-slate-600 mt-2.5 pt-2 border-t border-slate-100 line-clamp-1">
                {{ app.job_posting.title }}
              </div>

              <!-- Card Footer -->
              <div class="flex items-center justify-between text-[10px] text-slate-400 mt-2.5 pt-2 border-t border-slate-100">
                <span>{{ app.created_at }}</span>
                <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-600 border border-rose-100 font-medium">
                  {{ app.source || 'Portal' }}
                </span>
              </div>
            </div>

            <!-- Empty State in Column -->
            <div
              v-if="!rejectedApplications.length"
              class="py-10 text-center text-xs text-slate-400 border border-dashed border-rose-200/80 rounded-xl flex flex-col items-center justify-center gap-1"
            >
              <span class="text-rose-300 text-sm">&empty;</span>
              <span>Tidak ada kandidat ditolak</span>
            </div>
          </div>
        </div>
    </div>

    <!-- CANDIDATE ATS DETAIL MODAL -->
    <div
      v-if="selectedApp"
      class="fixed inset-0 z-[100] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
      @click.self="selectedApp = null"
    >
      <div class="bg-white rounded-2xl border border-slate-200/80 w-full max-w-6xl h-[88vh] max-h-[900px] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Top Header Bar -->
        <div class="px-6 py-3.5 border-b border-slate-200/80 bg-white flex items-center justify-between shrink-0">
          <div class="flex items-center gap-3.5 min-w-0">
            <button
              @click="selectedApp = null"
              class="p-1.5 rounded-xl text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors cursor-pointer shrink-0"
              title="Kembali"
            >
              <ArrowLeft class="w-4 h-4" />
            </button>
            <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-100/80">
              {{ getInitials(selectedApp.full_name) }}
            </div>
            <div class="min-w-0">
              <h3 class="text-sm font-bold text-slate-900 truncate">{{ selectedApp.full_name }}</h3>
              <p class="text-[11px] text-slate-400 truncate">{{ selectedApp.email || '-' }} &bull; {{ selectedApp.whatsapp_number || selectedApp.phone || '-' }}</p>
            </div>
          </div>

          <!-- Top Right Actions -->
          <div class="flex items-center gap-2.5 shrink-0">
            <div class="flex items-center gap-1.5">
              <span class="text-xs font-medium text-slate-500">Tahap:</span>
              <div class="relative">
                <select
                  :value="selectedApp.status === 'rejected' ? 'rejected' : (selectedApp.current_stage_id || selectedApp.stage?.id || 1)"
                  @change="handleStageChange(selectedApp, $event.target.value)"
                  class="appearance-none bg-white border border-slate-200 hover:border-slate-300 text-xs font-medium rounded-lg pl-3 pr-7 py-1.5 text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer shadow-2xs"
                  :class="{ 'text-rose-600 font-semibold border-rose-300': selectedApp.status === 'rejected' }"
                >
                  <option v-for="stg in stages" :key="stg.id" :value="stg.id">
                    {{ stg.name }}
                  </option>
                  <option value="rejected" class="text-rose-600 font-semibold">
                    Ditolak
                  </option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                  <ChevronDown class="w-3.5 h-3.5" />
                </div>
              </div>
            </div>

            <button
              @click="openSendEmailModal(selectedApp)"
              class="px-3 py-1.5 rounded-lg bg-[#0c2340] hover:bg-[#15325b] text-white text-xs font-medium flex items-center gap-1.5 shadow-2xs transition-all cursor-pointer"
            >
              <Send class="w-3.5 h-3.5" />
              <span>Kirim Notifikasi</span>
            </button>

            <a
              v-if="selectedApp.resume_url"
              :href="selectedApp.resume_url"
              target="_blank"
              class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-medium text-slate-700 flex items-center gap-1.5 shadow-2xs transition-colors"
            >
              <span>Buka CV</span>
              <ExternalLink class="w-3.5 h-3.5 text-slate-400" />
            </a>

            <button
              @click="selectedApp = null"
              class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
              title="Tutup"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Main Body: 2 Columns Layout -->
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden min-h-0">
          
          <!-- LEFT COLUMN: Candidate Data & Evaluation (46% Width, Clean, Zero Scrollbar) -->
          <div
            class="w-full lg:w-[46%] p-6 overflow-y-auto no-scrollbar border-r border-slate-200/70 bg-white space-y-5"
            style="-ms-overflow-style: none; scrollbar-width: none;"
          >
            <!-- 0. Evaluasi Kualifikasi (Sleek Card, No Nested Boxes) -->
            <div class="rounded-xl bg-slate-50/80 border border-slate-200/80 p-4 space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Sparkles class="w-4 h-4 text-blue-600" />
                  <h3 class="text-xs font-bold text-slate-900 tracking-tight">Evaluasi Kualifikasi Pelamar</h3>
                </div>
                <span
                  v-if="selectedApp.ai_match_score !== null && selectedApp.ai_match_score !== undefined"
                  :class="['px-2.5 py-0.5 rounded-full text-[11px] font-semibold border', getAiBadgeClasses(selectedApp.ai_match_score)]"
                >
                  {{ selectedApp.ai_match_score }}% &bull; {{ formatAiRecommendation(selectedApp.ai_recommendation) }}
                </span>
                <span v-else class="text-[11px] text-slate-400 italic">Belum dievaluasi</span>
              </div>

              <!-- Summary Text with clean typography -->
              <p class="text-xs text-slate-700 leading-relaxed font-normal bg-white p-3 rounded-lg border border-slate-200/60 whitespace-pre-line shadow-2xs">
                {{ selectedApp.ai_summary || 'Evaluasi kualifikasi membandingkan kriteria posisi dengan berkas CV pelamar.' }}
              </p>

              <!-- Actions -->
              <div class="flex items-center justify-between pt-0.5 text-[11px] text-slate-400">
                <span v-if="selectedApp.ai_analyzed_at">Diperbarui: {{ selectedApp.ai_analyzed_at }}</span>
                <span v-else></span>
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    @click="rescreenSingleCandidate(selectedApp)"
                    :disabled="isScreening"
                    class="px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100 bg-white rounded-lg border border-slate-200 flex items-center gap-1.5 transition-colors cursor-pointer disabled:opacity-50 shadow-2xs"
                  >
                    <RefreshCw class="w-3 h-3 text-slate-400" :class="{ 'animate-spin': isScreening }" />
                    <span>Evaluasi Ulang</span>
                  </button>
                  <button
                    type="button"
                    @click="openAnalysisModal(selectedApp)"
                    class="px-2.5 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-50 bg-white rounded-lg border border-blue-200 transition-colors cursor-pointer shadow-2xs"
                  >
                    Detail Komparasi &rarr;
                  </button>
                </div>
              </div>
            </div>

            <!-- 1. Biodata Pelamar (Modern Grid Layout) -->
            <div class="space-y-3">
              <div class="flex items-center gap-2 pb-1.5 border-b border-slate-100">
                <User class="w-3.5 h-3.5 text-slate-400" />
                <h3 class="text-xs font-bold text-slate-800 tracking-tight">Biodata Pelamar</h3>
              </div>

              <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">Nama Lengkap</span>
                  <span class="font-semibold text-slate-900 mt-0.5 block">{{ selectedApp.full_name }}</span>
                </div>
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">Jenis Kelamin</span>
                  <span class="font-medium text-slate-800 mt-0.5 block">{{ selectedApp.gender || '-' }}</span>
                </div>
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">Email</span>
                  <a :href="`mailto:${selectedApp.email}`" class="font-medium text-blue-600 hover:underline mt-0.5 block truncate" :title="selectedApp.email">
                    {{ selectedApp.email || '-' }}
                  </a>
                </div>
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">Tanggal Lahir</span>
                  <span class="font-medium text-slate-800 mt-0.5 block">{{ selectedApp.birth_date || '-' }}</span>
                </div>
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">No. WhatsApp</span>
                  <a v-if="selectedApp.whatsapp_number || selectedApp.phone" :href="`https://wa.me/${(selectedApp.whatsapp_number || selectedApp.phone || '').replace(/[^0-9]/g, '')}`" target="_blank" class="font-medium text-emerald-600 hover:underline mt-0.5 inline-flex items-center gap-1">
                    <span>{{ selectedApp.whatsapp_number || selectedApp.phone }}</span>
                  </a>
                  <span v-else class="text-slate-400 mt-0.5 block">-</span>
                </div>
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">Status Pernikahan</span>
                  <span class="font-medium text-slate-800 mt-0.5 block">{{ selectedApp.marital_status || '-' }}</span>
                </div>
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">Sumber Pelamar</span>
                  <span class="font-medium text-slate-800 mt-0.5 block">{{ selectedApp.source || 'Website' }}</span>
                </div>
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">No. Telepon Aktif</span>
                  <span class="font-medium text-slate-800 mt-0.5 block">{{ selectedApp.active_phone || selectedApp.phone || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- 2. Alamat & Lokasi -->
            <div class="space-y-3 pt-1">
              <div class="flex items-center gap-2 pb-1.5 border-b border-slate-100">
                <MapPin class="w-3.5 h-3.5 text-slate-400" />
                <h3 class="text-xs font-bold text-slate-800 tracking-tight">Alamat &amp; Domisili</h3>
              </div>

              <div class="space-y-2.5 text-xs">
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">Alamat KTP</span>
                  <p class="font-normal text-slate-700 mt-0.5 leading-relaxed bg-slate-50/60 p-2.5 rounded-lg border border-slate-100">
                    {{ selectedApp.address_ktp || '-' }}
                  </p>
                </div>
                <div v-if="selectedApp.address_domicile && selectedApp.address_domicile !== selectedApp.address_ktp">
                  <span class="block text-[11px] font-medium text-slate-400">Alamat Domisili</span>
                  <p class="font-normal text-slate-700 mt-0.5 leading-relaxed bg-slate-50/60 p-2.5 rounded-lg border border-slate-100">
                    {{ selectedApp.address_domicile }}
                  </p>
                </div>
              </div>
            </div>

            <!-- 3. Kontak Darurat -->
            <div class="space-y-3 pt-1">
              <div class="flex items-center gap-2 pb-1.5 border-b border-slate-100">
                <Phone class="w-3.5 h-3.5 text-slate-400" />
                <h3 class="text-xs font-bold text-slate-800 tracking-tight">Kontak Darurat</h3>
              </div>

              <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100 grid grid-cols-3 gap-3 text-xs">
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">Nama</span>
                  <span class="font-semibold text-slate-800 mt-0.5 block">{{ selectedApp.emergency_contact_name || '-' }}</span>
                </div>
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">Hubungan</span>
                  <span class="font-medium text-slate-700 mt-0.5 block">{{ selectedApp.emergency_contact_relation || '-' }}</span>
                </div>
                <div>
                  <span class="block text-[11px] font-medium text-slate-400">No. Kontak</span>
                  <span class="font-medium text-slate-700 mt-0.5 block">{{ selectedApp.emergency_contact_phone || '-' }}</span>
                </div>
              </div>
            </div>

          </div>

          <!-- RIGHT COLUMN: CV / Document Viewer (54% Width) -->
          <div class="w-full lg:w-[54%] bg-slate-50/50 p-6 flex flex-col h-full overflow-hidden">
            
            <div class="flex items-center justify-between mb-3 shrink-0">
              <h3 class="text-xs font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <FileText class="w-3.5 h-3.5 text-slate-400" />
                <span>Pratinjau Dokumen CV Pelamar</span>
              </h3>
              <div class="flex items-center gap-2">
                <a
                  v-if="selectedApp.resume_url"
                  :href="selectedApp.resume_url"
                  target="_blank"
                  class="text-xs font-medium text-blue-600 hover:text-blue-800 inline-flex items-center gap-1 bg-white px-2.5 py-1 rounded-lg border border-slate-200 shadow-2xs transition-colors"
                >
                  <span>Unduh Berkas</span>
                  <ExternalLink class="w-3 h-3" />
                </a>
                <span v-else class="text-[11px] font-medium text-slate-400 flex items-center gap-1.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-300 inline-block"></span>
                  Portal Karir OceanSpace
                </span>
              </div>
            </div>

            <!-- Embedded PDF Document Container -->
            <div class="flex-1 rounded-xl border border-slate-200 bg-white overflow-hidden shadow-2xs flex flex-col relative">
              <iframe
                v-if="selectedApp.resume_url"
                :src="selectedApp.resume_url"
                class="w-full h-full border-0"
              ></iframe>
              <div v-else class="flex-1 flex flex-col items-center justify-center text-center p-8 bg-slate-50/40">
                <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center shadow-2xs mb-3 text-slate-400">
                  <FileText class="w-6 h-6 text-slate-400" />
                </div>
                <p class="text-xs font-bold text-slate-800">Menunggu Berkas CV dari OceanSpace</p>
                <p class="text-[11px] text-slate-400 mt-1 max-w-sm">
                  Berkas CV akan otomatis terlampir saat kandidat melamar melalui portal karir OceanSpace.
                </p>
              </div>
            </div>

          </div>

        </div>
      </div>
    </div>

    <!-- HASIL EVALUASI KUALIFIKASI & PERSYARATAN MODAL -->
    <div
      v-if="analysisModalApp"
      class="fixed inset-0 z-[110] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="analysisModalApp = null"
    >
      <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-100 flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4.5 border-b border-slate-200 flex items-center justify-between bg-white shrink-0">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-700 shrink-0">
              <CheckCircle2 class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900">Detail Komparasi Kualifikasi</h3>
              <p class="text-xs text-slate-500 mt-0.5">{{ analysisModalApp.full_name }} &bull; {{ analysisModalApp.job_posting?.title || 'Posisi Lowongan' }}</p>
            </div>
          </div>
          <button
            @click="analysisModalApp = null"
            class="text-slate-400 hover:text-slate-700 rounded-lg p-1 text-xl font-bold cursor-pointer leading-none"
          >
            &times;
          </button>
        </div>

        <!-- Modal Body: Clean Table Style & Analysis Report -->
        <div class="p-6 space-y-4.5 text-xs overflow-y-auto custom-scrollbar flex-1">
          <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
            <table class="w-full text-left">
              <tbody class="divide-y divide-slate-200">
                <tr>
                  <td class="py-2.5 px-4 text-slate-500 font-medium w-40 bg-slate-50/60">Nama Pelamar</td>
                  <td class="py-2.5 px-4 text-slate-900 font-bold uppercase">{{ analysisModalApp.full_name }}</td>
                </tr>
                <tr>
                  <td class="py-2.5 px-4 text-slate-500 font-medium bg-slate-50/60">Posisi yang Dilamar</td>
                  <td class="py-2.5 px-4 text-slate-900 font-semibold">{{ analysisModalApp.job_posting?.title || '-' }}</td>
                </tr>
                <tr>
                  <td class="py-2.5 px-4 text-slate-500 font-medium bg-slate-50/60">Kesesuaian Kualifikasi</td>
                  <td class="py-2.5 px-4">
                    <span :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border shadow-2xs', getAiBadgeClasses(analysisModalApp.ai_match_score)]">
                      {{ analysisModalApp.ai_match_score }}% Match &bull; {{ formatAiRecommendation(analysisModalApp.ai_recommendation) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Catatan / Rangkuman Evaluasi Komparatif -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <FileText class="w-3.5 h-3.5 text-slate-500" />
                <span>Rangkuman Evaluasi Komparasi CV vs Kualifikasi</span>
              </h4>
              <span class="text-[10px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100">
                AI &amp; ATS Screening
              </span>
            </div>
            
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/90 text-slate-700 leading-relaxed text-xs whitespace-pre-line shadow-2xs">
              {{ analysisModalApp.ai_summary || 'Kandidat memiliki kualifikasi yang relevan dengan persyaratan posisi lowongan ini.' }}
            </div>
            
            <div v-if="analysisModalApp.ai_analyzed_at" class="text-[11px] text-slate-400 text-right">
              Terakhir dievaluasi: {{ analysisModalApp.ai_analyzed_at }}
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
          <button
            @click="rescreenSingleCandidate(analysisModalApp)"
            :disabled="isScreening"
            class="px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 rounded-lg border border-slate-300 flex items-center gap-1.5 transition-colors cursor-pointer disabled:opacity-50 shadow-2xs"
          >
            <RefreshCw class="w-3 h-3 text-slate-500" :class="{ 'animate-spin': isScreening }" />
            <span>Evaluasi Ulang CV</span>
          </button>

          <div class="flex items-center gap-2">
            <button
              @click="analysisModalApp = null"
              class="px-3.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200 rounded-lg transition-colors cursor-pointer"
            >
              Tutup
            </button>
            <button
              @click="openDetail(analysisModalApp); analysisModalApp = null"
              class="px-4 py-1.5 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-lg shadow-xs transition-colors cursor-pointer"
            >
              Buka Detail Profil
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SEND NOTIFICATION MODAL (Email & WhatsApp, Single & Bulk) -->
    <div
      v-if="sendEmailModalApp"
      class="fixed inset-0 z-[120] bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
      @click.self="closeNotificationModal"
    >
      <div class="bg-white rounded-xl border border-slate-200 w-full max-w-4xl shadow-xl overflow-hidden flex flex-col my-6 max-h-[95vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-10">
          <div>
            <h3 class="text-sm font-bold text-slate-900">
              {{ isBulkMode ? 'Kirim Notifikasi Massal' : 'Kirim Undangan / Notifikasi' }}
            </h3>
            <p class="text-xs text-slate-500 mt-1">
              <template v-if="isBulkMode">
                Target: <strong class="text-slate-800">{{ selectedAppIds.length }} Pelamar</strong> &bull; Pesan disesuaikan per nama pelamar
              </template>
              <template v-else>
                Penerima: <strong class="text-slate-800">{{ sendEmailModalApp.full_name }}</strong>
                <span v-if="sendEmailModalApp.email" class="text-slate-500"> ({{ sendEmailModalApp.email }})</span>
                <span class="text-slate-300 mx-2">|</span>
                <span v-if="sendEmailModalApp.whatsapp_number || sendEmailModalApp.phone" class="text-emerald-600 font-medium">WA: {{ sendEmailModalApp.whatsapp_number || sendEmailModalApp.phone }}</span>
              </template>
            </p>
          </div>
          <button
            type="button"
            @click="closeNotificationModal"
            class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg p-1.5 transition-colors cursor-pointer"
            title="Tutup"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 space-y-4 text-xs overflow-y-auto flex-1 custom-scrollbar">
          <!-- Kanal Pengiriman -->
          <div class="space-y-1.5">
            <label class="block font-semibold text-xs text-slate-800">Kanal Pengiriman</label>
            <div class="border border-slate-200 rounded-lg bg-white flex flex-wrap sm:flex-nowrap items-center divide-y sm:divide-y-0 sm:divide-x divide-slate-200 text-xs">
              <!-- Email Checkbox -->
              <label class="flex-1 flex items-center gap-2.5 px-3.5 py-2.5 cursor-pointer hover:bg-slate-50/70 transition-colors select-none">
                <input
                  type="checkbox"
                  value="email"
                  v-model="selectedChannels"
                  class="rounded border-slate-300 text-blue-600 focus:ring-0 w-4 h-4 cursor-pointer"
                />
                <Mail class="w-4 h-4 text-slate-500" />
                <span class="font-normal text-slate-700">Email (Surat Resmi)</span>
              </label>

              <!-- WhatsApp Checkbox -->
              <label class="flex-1 flex items-center gap-2.5 px-3.5 py-2.5 cursor-pointer hover:bg-slate-50/70 transition-colors select-none">
                <input
                  type="checkbox"
                  value="whatsapp"
                  v-model="selectedChannels"
                  class="rounded border-slate-300 text-blue-600 focus:ring-0 w-4 h-4 cursor-pointer"
                />
                <svg class="w-4 h-4 text-[#25D366] shrink-0" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
                <span class="font-normal text-slate-700">WhatsApp</span>
              </label>

              <!-- Kirim: Langsung vs Jadwalkan -->
              <div class="px-4 py-2.5 flex items-center gap-3.5 bg-white shrink-0">
                <span class="font-normal text-slate-700 text-xs">Kirim:</span>
                <label class="inline-flex items-center gap-1.5 cursor-pointer text-slate-700 text-xs font-normal select-none">
                  <input
                    type="radio"
                    value="immediate"
                    v-model="sendType"
                    name="modal_send_type"
                    class="text-blue-600 focus:ring-0 w-3.5 h-3.5 cursor-pointer"
                  />
                  <span>Langsung</span>
                </label>
                <label class="inline-flex items-center gap-1.5 cursor-pointer text-slate-700 text-xs font-normal select-none">
                  <input
                    type="radio"
                    value="scheduled"
                    v-model="sendType"
                    name="modal_send_type"
                    class="text-blue-600 focus:ring-0 w-3.5 h-3.5 cursor-pointer"
                  />
                  <span>Jadwalkan</span>
                </label>
              </div>
            </div>
            <div v-if="!selectedChannels.length" class="text-rose-600 text-[11px] font-medium px-1">
              Pilih minimal salah satu kanal pengiriman (Email atau WhatsApp).
            </div>
          </div>

          <!-- Jadwalkan Pengiriman Inputs (Horizontal Integrated Bar) -->
          <div v-if="sendType === 'scheduled'" class="space-y-1.5">
            <label class="block font-semibold text-xs text-slate-800">Jadwalkan Pengiriman</label>
            <div class="border border-slate-200 rounded-lg bg-white overflow-hidden text-xs">
              <div class="flex flex-wrap sm:flex-nowrap items-center divide-y sm:divide-y-0 sm:divide-x divide-slate-200">
                <!-- Date Input -->
                <div class="flex items-center gap-2 px-3 py-2 flex-1 min-w-[200px]">
                  <span class="text-slate-500 whitespace-nowrap text-xs font-normal">Kirim otomatis pada</span>
                  <input
                    type="date"
                    v-model="scheduleDate"
                    :min="todayDateString"
                    class="bg-transparent text-xs text-slate-800 font-normal focus:outline-none cursor-pointer flex-1"
                  />
                </div>

                <!-- Time Input -->
                <div class="flex items-center gap-2 px-3 py-2 flex-1 min-w-[140px]">
                  <span class="text-slate-500 whitespace-nowrap text-xs font-normal">Pukul</span>
                  <input
                    type="time"
                    v-model="scheduleTime"
                    class="bg-transparent text-xs text-slate-800 font-normal focus:outline-none cursor-pointer flex-1"
                  />
                </div>

                <!-- Timezone Selector -->
                <div class="relative flex-1 px-3 py-2">
                  <select class="w-full bg-transparent text-xs text-slate-800 font-normal focus:outline-none cursor-pointer">
                    <option value="WIB">WIB (UTC+07:00)</option>
                    <option value="WITA">WITA (UTC+08:00)</option>
                    <option value="WIT">WIT (UTC+09:00)</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Template Notifikasi (Sesuai Pipeline - Flex Wrap, No Right Scroll) -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block font-semibold text-xs text-slate-800">Pilih Template Sesuai Tahapan Pipeline</label>
              <span class="text-[11px] text-slate-400 font-normal">Semua tahapan langsung terlihat (tanpa geser)</span>
            </div>
            <div class="flex flex-wrap items-center gap-1.5 p-2 bg-slate-50/80 rounded-lg border border-slate-200">
              <button
                v-for="tpl in pipelineTemplateTabs"
                :key="tpl.key"
                type="button"
                @click="applyEmailTemplate(tpl.key)"
                :class="[
                  'px-3 py-1.5 text-xs font-medium rounded-md transition-all cursor-pointer select-none flex items-center gap-1.5',
                  activeEmailTemplateKey === tpl.key
                    ? 'bg-blue-600 text-white shadow-xs font-semibold'
                    : 'bg-white text-slate-700 hover:bg-slate-100 hover:text-slate-900 border border-slate-200/80'
                ]"
              >
                <span :class="activeEmailTemplateKey === tpl.key ? 'text-blue-200' : 'text-slate-400 font-mono text-[10px]'">{{ tpl.num }}</span>
                <span>{{ tpl.label }}</span>
              </button>
            </div>
          </div>

          <!-- Subject Input -->
          <div>
            <label class="block font-semibold text-xs text-slate-800 mb-1.5">Subjek</label>
            <input
              type="text"
              v-model="emailForm.subject"
              class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500 shadow-2xs font-normal transition-all"
            />
          </div>

          <!-- Body Message Textarea (Full Text, Auto-Height, No Scrollbar) -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block font-semibold text-xs text-slate-800">Isi Pesan</label>
              <span class="text-[10px] text-slate-400 font-normal">Teks ditampilkan penuh tanpa scrollbar</span>
            </div>
            <textarea
              ref="bodyTextareaRef"
              v-model="emailForm.body_message"
              rows="7"
              @input="adjustTextareaHeight"
              class="w-full bg-white border border-slate-200 rounded-lg p-3 text-xs text-slate-800 focus:outline-none focus:border-blue-500 leading-relaxed font-sans shadow-2xs transition-all overflow-y-hidden"
              style="min-height: 140px;"
            ></textarea>
          </div>

          <!-- Detail Pelaksanaan (Table Form Style like Screenshot) -->
          <div class="space-y-3">
            <label class="block font-semibold text-xs text-slate-800">Detail Pelaksanaan</label>

            <div class="border border-slate-200 rounded-lg overflow-hidden text-xs">
              <!-- Table Header -->
              <div class="grid grid-cols-12 bg-slate-50 border-b border-slate-200 font-semibold text-slate-700 px-3 py-2">
                <div class="col-span-4">Item</div>
                <div class="col-span-8">Keterangan</div>
              </div>

              <!-- Row 1: Jadwal / Batas Waktu -->
              <div class="grid grid-cols-12 items-center px-3 py-2 border-b border-slate-100 gap-2">
                <div class="col-span-4 font-normal text-slate-700">Jadwal / Batas Waktu</div>
                <div class="col-span-8">
                  <input
                    type="text"
                    v-model="emailForm.schedule"
                    placeholder="Batas Pengerjaan: 3 hari kerja"
                    class="w-full bg-white border border-slate-200 rounded-md px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-blue-500"
                  />
                </div>
              </div>

              <!-- Row 2: Lokasi / Media -->
              <div class="grid grid-cols-12 items-center px-3 py-2 gap-2">
                <div class="col-span-4 font-normal text-slate-700">Lokasi / Media</div>
                <div class="col-span-8">
                  <input
                    type="text"
                    v-model="emailForm.venue_or_method"
                    placeholder="Online Assessment"
                    class="w-full bg-white border border-slate-200 rounded-md px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-blue-500"
                  />
                </div>
              </div>
            </div>

            <!-- Optional 2-col inputs: Tautan Akses & Catatan Tambahan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-xs text-slate-800 mb-1.5">Tautan Akses (Opsional)</label>
                <div class="relative">
                  <Link2 class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                  <input
                    type="url"
                    v-model="emailForm.action_url"
                    placeholder="https://..."
                    class="w-full bg-white border border-slate-200 rounded-lg pl-8.5 pr-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-blue-500 font-mono text-[11px]"
                  />
                </div>
              </div>

              <div>
                <label class="block font-semibold text-xs text-slate-800 mb-1.5">Catatan Tambahan (Opsional)</label>
                <div class="relative">
                  <FileText class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                  <input
                    type="text"
                    v-model="emailForm.special_note"
                    placeholder="Pastikan koneksi internet stabil dan gunakan browser terbaru."
                    class="w-full bg-white border border-slate-200 rounded-lg pl-8.5 pr-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-blue-500"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Offering Letter PDF Upload -->
          <div v-if="activeEmailTemplateKey === 'offering'" class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
            <div class="flex items-center justify-between">
              <label class="block font-semibold text-xs text-slate-800 flex items-center gap-1.5">
                <FileText class="w-3.5 h-3.5 text-blue-600" />
                <span>Dokumen Lampiran (PDF)</span>
              </label>
              <span class="text-[10.5px] text-slate-500">Maks. 15MB &bull; Khusus Email</span>
            </div>

            <div v-if="!emailForm.attachment" class="relative border border-dashed border-slate-300 hover:border-slate-400 bg-white rounded-lg p-3 text-center cursor-pointer transition-colors group">
              <input
                type="file"
                accept="application/pdf,.pdf"
                @change="handleAttachmentUpload"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              />
              <div class="flex items-center justify-center gap-2 text-slate-500 group-hover:text-slate-800">
                <Upload class="w-4 h-4 text-slate-400 group-hover:text-slate-600" />
                <span class="text-xs font-medium">Unggah file PDF Offering Letter</span>
              </div>
            </div>

            <div v-else class="flex items-center justify-between bg-white p-2.5 rounded-lg border border-slate-200 shadow-2xs">
              <div class="flex items-center gap-2 overflow-hidden">
                <div class="w-7 h-7 rounded bg-red-50 text-red-600 flex items-center justify-center font-bold text-[10px] shrink-0">
                  PDF
                </div>
                <div class="truncate">
                  <div class="text-xs font-medium text-slate-800 truncate">{{ emailForm.attachment_name }}</div>
                  <div class="text-[10px] text-slate-400">{{ formatFileSize(emailForm.attachment?.size) }}</div>
                </div>
              </div>
              <button
                type="button"
                @click="removeAttachment"
                class="text-slate-400 hover:text-red-600 p-1 rounded transition-colors cursor-pointer"
                title="Hapus Lampiran"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-slate-200 bg-white flex items-center justify-between sticky bottom-0 z-10">
          <button
            type="button"
            @click="closeNotificationModal"
            class="px-4 py-2 text-xs font-medium text-slate-700 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg transition-colors cursor-pointer"
          >
            Batal
          </button>

          <button
            type="button"
            @click="executeSendNotification"
            :disabled="isSendingEmail || !selectedChannels.length || (sendType === 'scheduled' && (!scheduleDate || !scheduleTime))"
            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs transition-colors cursor-pointer shadow-xs disabled:opacity-50 flex items-center gap-2"
          >
            <span v-if="isSendingEmail" class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <CalendarClock v-else-if="sendType === 'scheduled'" class="w-3.5 h-3.5" />
            <Send v-else class="w-3.5 h-3.5" />
            <span>
              {{ isSendingEmail ? 'Memproses...' : (sendType === 'scheduled' ? (isBulkMode ? `Jadwalkan untuk ${selectedAppIds.length} Pelamar` : 'Jadwalkan Notifikasi') : (isBulkMode ? `Kirim ke ${selectedAppIds.length} Pelamar` : 'Kirim Notifikasi')) }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useRekrutmenStore } from '../stores/rekrutmen';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import axios from 'axios';
import { 
  Search, ListFilter, Kanban, ArrowLeft, ExternalLink, Eye,
  CheckCircle2, AlertCircle, Mail, Phone, FileText, RefreshCw, User, Sparkles,
  Link2, Users, Bell, ChevronDown, Upload, MessageSquare, Send, CheckSquare,
  Calendar, MapPin, X, Clock, CalendarClock, UserX
} from 'lucide-vue-next';

const store = useRekrutmenStore();
const route = useRoute();
const router = useRouter();

const logoUrl = '/images/logo.png';
const oceanSpaceLogoUrl = '/images/oceanspace-logo.png';
const viewMode = ref('table');
const matchFilter = ref('all');
const searchQuery = ref('');
const toastMessage = ref(null);
const toastType = ref('success');
const selectedApp = ref(null);
const analysisModalApp = ref(null);
const dragOverStageId = ref(null);
const isScreening = ref(false);

// Bulk selection state
const selectedAppIds = ref([]);
const isBulkMode = ref(false);
const selectedChannels = ref(['email', 'whatsapp']);

const isAllSelected = computed(() => {
  if (!filteredApplications.value.length) return false;
  return filteredApplications.value.every(app => selectedAppIds.value.includes(app.id));
});

const isSelected = (id) => selectedAppIds.value.includes(id);

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedAppIds.value = [];
  } else {
    selectedAppIds.value = filteredApplications.value.map(app => app.id);
  }
};

const defaultStages = [
  { id: 1, name: 'Screening CV', color: '#2563eb' },
  { id: 2, name: 'Interview HR', color: '#d97706' },
  { id: 3, name: 'Psikotes', color: '#7c3aed' },
  { id: 4, name: 'Tes Kompetensi', color: '#4f46e5' },
  { id: 5, name: 'Interview User', color: '#0284c7' },
  { id: 6, name: 'Background Check', color: '#0d9488' },
  { id: 7, name: 'Offering Letter', color: '#ea580c' },
  { id: 8, name: 'Hired', color: '#059669' }
];

let heartbeatTimer = null;

const checkHeartbeat = async () => {
  try {
    const res = await axios.post('/rekrutmen/api/notifications/heartbeat');
    if (res.data?.processed > 0) {
      await store.fetchApplications('', false).catch(() => {});
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `${res.data.processed} Notifikasi terjadwal berhasil terkirim!`,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
      });
    }
  } catch (_) {}
};

onMounted(() => {
  store.fetchApplications('', false).catch(() => {});
  checkHeartbeat();
  heartbeatTimer = setInterval(checkHeartbeat, 25000);
});

onUnmounted(() => {
  if (heartbeatTimer) clearInterval(heartbeatTimer);
});

const applications = computed(() => {
  if (route.query.job_id) {
    return store.applications.filter(a => String(a.job_posting_id) === String(route.query.job_id));
  }
  return store.applications || [];
});

const stages = computed(() => {
  if (store.stages && store.stages.length) return store.stages;
  if (store.configurations?.stages && store.configurations.stages.length) return store.configurations.stages;
  return defaultStages;
});

const activeJobId = computed(() => route.query.job_id || null);
const activeJobTitle = computed(() => {
  if (!activeJobId.value) return null;
  const job = store.postings.find(j => String(j.id) === String(activeJobId.value));
  return job ? job.title : null;
});

const stageFilter = ref('all');

const getStageCandidateCount = (stageId) => {
  return applications.value.filter(a => {
    if (a.status === 'rejected') return false;
    const cur = a.current_stage_id || a.stage?.id || 1;
    return String(cur) === String(stageId);
  }).length;
};

const rejectedCandidateCount = computed(() => {
  return applications.value.filter(a => a.status === 'rejected').length;
});

const recommendedCount = computed(() => applications.value.filter(a => a.ai_match_score >= 75).length);
const consideredCount = computed(() => applications.value.filter(a => a.ai_match_score >= 50 && a.ai_match_score < 75).length);
const notSuitableCount = computed(() => applications.value.filter(a => a.ai_match_score !== null && a.ai_match_score < 50).length);

const filteredApplications = computed(() => {
  let list = applications.value;

  // Filter by Stage
  if (stageFilter.value === 'rejected') {
    list = list.filter(a => a.status === 'rejected');
  } else if (stageFilter.value !== 'all') {
    list = list.filter(a => {
      if (a.status === 'rejected') return false;
      const currentStage = a.current_stage_id || a.stage?.id || 1;
      return String(currentStage) === String(stageFilter.value);
    });
  }

  // Filter by Match Score
  if (matchFilter.value === 'recommended') {
    list = list.filter(a => a.ai_match_score >= 75);
  } else if (matchFilter.value === 'considered') {
    list = list.filter(a => a.ai_match_score >= 50 && a.ai_match_score < 75);
  } else if (matchFilter.value === 'not_suitable') {
    list = list.filter(a => a.ai_match_score !== null && a.ai_match_score < 50);
  }

  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(a => 
    (a.full_name && a.full_name.toLowerCase().includes(q)) ||
    (a.email && a.email.toLowerCase().includes(q)) ||
    (a.phone && a.phone.includes(q)) ||
    (a.whatsapp_number && a.whatsapp_number.includes(q)) ||
    (a.job_posting && a.job_posting.title && a.job_posting.title.toLowerCase().includes(q))
  );
});

const getStageApplications = (stageId) => {
  return filteredApplications.value.filter(a => {
    if (a.status === 'rejected') return false;
    const currentStage = a.current_stage_id || a.stage?.id || 1;
    return String(currentStage) === String(stageId);
  });
};

const rejectedApplications = computed(() => {
  return filteredApplications.value.filter(a => a.status === 'rejected');
});

const getInitials = (name) => {
  if (!name) return 'PL';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const getAiBadgeClasses = (score) => {
  if (score === null || score === undefined) return 'bg-slate-50 text-slate-500 border-slate-200';
  if (score >= 75) return 'bg-emerald-50 text-emerald-700 border-emerald-300';
  if (score >= 50) return 'bg-amber-50 text-amber-700 border-amber-300';
  return 'bg-rose-50 text-rose-700 border-rose-300';
};

const formatAiRecommendation = (rec) => {
  if (!rec) return 'Direkomendasikan';
  const lower = String(rec).toLowerCase();
  if (lower.includes('rekomend') || (lower.includes('recommend') && !lower.includes('not') && !lower.includes('un'))) return 'Direkomendasikan';
  if (lower.includes('timbang') || lower.includes('consider')) return 'Dipertimbangkan';
  if (lower.includes('kurang') || lower.includes('tidak') || lower.includes('not') || lower.includes('reject')) return 'Kurang Sesuai';
  return rec;
};

const formatStatus = (status) => {
  if (!status) return 'Dalam Proses';
  const s = String(status).toLowerCase().replace(/_/g, ' ');
  if (s.includes('in progress')) return 'Dalam Proses';
  if (s.includes('shortlist')) return 'Shortlisted';
  if (s.includes('reject')) return 'Ditolak';
  if (s.includes('hire')) return 'Diterima';
  return s.charAt(0).toUpperCase() + s.slice(1);
};

const getStatusBadge = (status) => {
  const s = String(status).toLowerCase();
  if (s.includes('hire') || s.includes('shortlist')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if (s.includes('reject')) return 'bg-rose-50 text-rose-700 border-rose-200';
  return 'bg-slate-50 text-slate-700 border-slate-200';
};

const resetJobFilter = () => {
  router.push({ path: '/admin/job-applications' });
};

const isUploadingCv = ref(false);

const openDetail = (app) => {
  selectedApp.value = app;
};

const handleCvUploadForApplicant = async (event) => {
  const file = event.target.files?.[0];
  if (!file || !selectedApp.value) return;

  if (file.size > 20 * 1024 * 1024) {
    Swal.fire({
      icon: 'warning',
      title: 'Ukuran File Terlalu Besar',
      text: 'Maksimal ukuran file CV adalah 20MB.',
      confirmButtonColor: '#0c2340',
    });
    return;
  }

  const formData = new FormData();
  formData.append('cv', file);

  isUploadingCv.value = true;
  Swal.fire({
    title: 'Mengunggah CV...',
    html: '<div class="text-xs text-slate-500 mt-2">Sedang menyimpan dokumen dan melakukan evaluasi kualifikasi...</div>',
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    const res = await axios.post(`/rekrutmen/api/applications/${selectedApp.value.id}/upload-cv`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    isUploadingCv.value = false;
    if (res.data?.success) {
      selectedApp.value.resume_path = res.data.resume_path;
      selectedApp.value.resume_url = res.data.resume_url;
      if (res.data.ai_match_score !== undefined) {
        selectedApp.value.ai_match_score = res.data.ai_match_score;
        selectedApp.value.ai_recommendation = res.data.ai_recommendation;
        selectedApp.value.ai_summary = res.data.ai_summary;
        selectedApp.value.ai_analyzed_at = res.data.ai_analyzed_at;
      }

      const found = store.applications.find(a => a.id === selectedApp.value.id);
      if (found) {
        found.resume_path = res.data.resume_path;
        found.resume_url = res.data.resume_url;
        found.has_resume = true;
        found.ai_match_score = res.data.ai_match_score;
        found.ai_recommendation = res.data.ai_recommendation;
        found.ai_summary = res.data.ai_summary;
      }

      Swal.fire({
        icon: 'success',
        title: 'CV Berhasil Diunggah!',
        text: 'Dokumen CV asli pelamar telah tersimpan dan ditampilkan di pratinjau.',
        timer: 2200,
        showConfirmButton: false,
        iconColor: '#10b981',
        customClass: {
          popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
          title: 'text-sm font-bold text-slate-900',
        }
      });
    }
  } catch (err) {
    isUploadingCv.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Gagal Mengunggah CV',
      text: err.response?.data?.message || 'Terjadi kesalahan saat mengunggah file CV.',
      confirmButtonColor: '#e11d48',
    });
  }
};

const openAnalysisModal = (app) => {
  if (!app) return;
  analysisModalApp.value = app;
};

const startRescreening = async () => {
  isScreening.value = true;
  Swal.fire({
    title: 'Evaluasi Kualifikasi',
    html: '<div class="text-xs text-slate-500 mt-2 leading-relaxed">Sedang menganalisis dan mencocokkan data berkas seluruh pelamar...</div>',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    customClass: {
      popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
      title: 'text-sm font-bold text-slate-900',
    },
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    const res = await store.batchAnalyzeWithAi(activeJobId.value);
    isScreening.value = false;
    Swal.fire({
      icon: 'success',
      title: 'Evaluasi Selesai',
      html: `<div class="text-xs text-slate-600 mt-1">${res.message || 'Evaluasi kualifikasi berhasil diperbarui.'}</div>`,
      timer: 2000,
      showConfirmButton: false,
      iconColor: '#10b981',
      customClass: {
        popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
        title: 'text-sm font-bold text-slate-900',
      }
    });
  } catch (e) {
    isScreening.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      html: '<div class="text-xs text-slate-600 mt-1">Terjadi kesalahan saat memproses evaluasi kualifikasi.</div>',
      confirmButtonColor: '#739ec5',
      customClass: {
        popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
        title: 'text-sm font-bold text-slate-900',
        confirmButton: 'px-4 py-2 rounded-xl text-xs font-bold'
      }
    });
  }
};

const rescreenSingleCandidate = async (app) => {
  if (!app) return;
  isScreening.value = true;
  Swal.fire({
    title: 'Mengevaluasi Pelamar',
    html: `<div class="text-xs text-slate-500 mt-2 leading-relaxed">Menganalisis data kualifikasi <b>${app.full_name}</b>...</div>`,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    customClass: {
      popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
      title: 'text-sm font-bold text-slate-900',
    },
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    const res = await store.analyzeCandidateWithAi(app.id);
    isScreening.value = false;
    if (res.application && selectedApp.value && selectedApp.value.id === app.id) {
      selectedApp.value = { ...selectedApp.value, ...res.application };
    }
    Swal.fire({
      icon: 'success',
      title: 'Evaluasi Berhasil',
      html: `<div class="text-xs text-slate-600 mt-1">${res.message || `Evaluasi untuk "${app.full_name}" berhasil diperbarui.`}</div>`,
      timer: 2000,
      showConfirmButton: false,
      iconColor: '#10b981',
      customClass: {
        popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
        title: 'text-sm font-bold text-slate-900',
      }
    });
  } catch (e) {
    isScreening.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      html: '<div class="text-xs text-slate-600 mt-1">Gagal mengevaluasi data pelamar.</div>',
      confirmButtonColor: '#739ec5',
      customClass: {
        popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
        title: 'text-sm font-bold text-slate-900',
        confirmButton: 'px-4 py-2 rounded-xl text-xs font-bold'
      }
    });
  }
};

const handleDragStart = (app, event) => {
  if (event && event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(app.id));
  }
};

const handleDragEnd = () => {
  dragOverStageId.value = null;
};

const handleDragOver = (stageId) => {
  dragOverStageId.value = stageId;
};

const handleDragLeave = (stageId) => {
  if (dragOverStageId.value === stageId) {
    dragOverStageId.value = null;
  }
};

const handleDrop = async (stageId, event) => {
  dragOverStageId.value = null;
  const appId = event?.dataTransfer?.getData('text/plain');
  if (!appId) return;

  const app = store.applications.find(a => String(a.id) === String(appId));
  if (!app) return;

  if (String(stageId) === 'rejected') {
    if (app.status !== 'rejected') {
      await rejectCandidate(app);
    }
    return;
  }

  const currentStageId = app.current_stage_id || app.stage?.id || 1;
  if (app.status === 'rejected' || String(currentStageId) !== String(stageId)) {
    await moveCandidateStage(app, stageId);
  }
};

const handleStageChange = async (app, stageId) => {
  if (String(stageId) === 'rejected') {
    await rejectCandidate(app);
  } else {
    await moveCandidateStage(app, stageId);
  }
};

const rejectCandidate = async (app) => {
  try {
    const res = await store.updateApplicationStage(app.id, 'rejected');
    if (res && res.success) {
      app.status = 'rejected';
      if (selectedApp.value && String(selectedApp.value.id) === String(app.id)) {
        selectedApp.value.status = 'rejected';
      }
      toastType.value = 'success';
      toastMessage.value = `Kandidat "${app.full_name}" telah ditolak.`;
      setTimeout(() => { toastMessage.value = null; }, 3000);
    }
  } catch (e) {
    toastType.value = 'error';
    toastMessage.value = 'Gagal mengubah status kandidat menjadi Ditolak.';
  }
};

const bulkRejectSelected = async () => {
  if (!selectedAppIds.value.length) return;

  const count = selectedAppIds.value.length;

  const confirm = await Swal.fire({
    title: `Tolak ${count} Pelamar?`,
    text: `Apakah Anda yakin ingin menolak ${count} pelamar yang dipilih?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#64748b',
    confirmButtonText: `Ya, Tolak (${count})`,
    cancelButtonText: 'Batal',
    reverseButtons: true,
  });

  if (!confirm.isConfirmed) return;

  try {
    const res = await store.batchRejectApplications(selectedAppIds.value);
    const rejectedCount = res.count || count;

    selectedAppIds.value = [];

    // Immediately refresh data
    await store.fetchApplications('', false).catch(() => {});

    toastType.value = 'success';
    toastMessage.value = `${rejectedCount} pelamar berhasil ditolak.`;
    setTimeout(() => { toastMessage.value = null; }, 3000);

    Swal.fire({
      icon: 'success',
      title: 'Pelamar Berhasil Ditolak',
      text: `${rejectedCount} pelamar telah diubah statusnya menjadi Ditolak.`,
      timer: 2000,
      showConfirmButton: false,
    });
  } catch (err) {
    toastType.value = 'error';
    toastMessage.value = 'Gagal memproses penolakan pelamar.';
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Terjadi kesalahan saat memproses penolakan pelamar.',
      confirmButtonColor: '#e11d48',
    });
  }
};

const moveCandidateStage = async (app, stageId) => {
  try {
    const res = await store.moveStage(app.id, stageId);
    if (res && res.success) {
      app.current_stage_id = parseInt(stageId);
      if (app.status === 'rejected') {
        app.status = 'in_progress';
      }
      const targetStage = stages.value.find(s => String(s.id) === String(stageId));
      if (targetStage) {
        app.stage = { id: targetStage.id, name: targetStage.name, color: targetStage.color };
      }
      if (selectedApp.value && String(selectedApp.value.id) === String(app.id)) {
        selectedApp.value.current_stage_id = parseInt(stageId);
        if (selectedApp.value.status === 'rejected') {
          selectedApp.value.status = 'in_progress';
        }
        if (targetStage) {
          selectedApp.value.stage = { id: targetStage.id, name: targetStage.name, color: targetStage.color };
        }
      }
      toastType.value = 'success';
      toastMessage.value = `Kandidat "${app.full_name}" dipindahkan ke tahap ${targetStage ? targetStage.name : 'baru'}.`;
      setTimeout(() => { toastMessage.value = null; }, 3000);
    }
  } catch (e) {
    toastType.value = 'error';
    toastMessage.value = 'Gagal memindahkan tahapan kandidat.';
  }
};

// Send Email Logic
const sendEmailModalApp = ref(null);
const emailTemplatesList = ref({});
const activeEmailTemplateKey = ref('psikotes');
const isSendingEmail = ref(false);

// Opsi Waktu Pengiriman (Kirim Langsung vs Jadwalkan)
const sendType = ref('immediate'); // 'immediate' | 'scheduled'
const scheduleDate = ref('');
const scheduleTime = ref('');

const todayDateString = computed(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
});

const schedulePreviewText = computed(() => {
  if (!scheduleDate.value || !scheduleTime.value) return '';
  try {
    const [year, month, day] = scheduleDate.value.split('-').map(Number);
    const [hour, minute] = scheduleTime.value.split(':').map(Number);
    const dateObj = new Date(year, month - 1, day, hour, minute);
    return dateObj.toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }) + `, pukul ${scheduleTime.value} WIB`;
  } catch {
    return `${scheduleDate.value} ${scheduleTime.value} WIB`;
  }
});

const emailForm = ref({
  subject: '',
  body_message: '',
  badge_text: 'Notifikasi Rekrutmen',
  info_box_title: 'Detail Informasi',
  action_url: '',
  action_label: '',
  schedule: '',
  venue_or_method: '',
  special_note: '',
  attachment: null,
  attachment_name: '',
});

const handleAttachmentUpload = (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  if (file.size > 15 * 1024 * 1024) {
    Swal.fire({
      icon: 'warning',
      title: 'Ukuran File Terlalu Besar',
      text: 'Maksimal ukuran file dokumen adalah 15MB.',
      confirmButtonColor: '#0c2340',
    });
    return;
  }

  emailForm.value.attachment = file;
  emailForm.value.attachment_name = file.name;
};

const removeAttachment = () => {
  emailForm.value.attachment = null;
  emailForm.value.attachment_name = '';
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const fetchEmailTemplates = async () => {
  try {
    const res = await axios.get('/rekrutmen/api/settings/mail-templates');
    if (res.data?.templates) {
      emailTemplatesList.value = res.data.templates;
    }
  } catch (err) {
    console.error('Failed to fetch email templates', err);
  }
};

const pipelineTemplateTabs = [
  { key: 'screening', num: '1', label: 'Screening CV' },
  { key: 'interview_hr', num: '2', label: 'Interview HR' },
  { key: 'psikotes', num: '3', label: 'Psikotes' },
  { key: 'kompetensi', num: '4', label: 'Tes Kompetensi' },
  { key: 'interview_user', num: '5', label: 'Interview User' },
  { key: 'background_check', num: '6', label: 'Background Check' },
  { key: 'offering', num: '7', label: 'Offering Letter' },
  { key: 'hired', num: '8', label: 'Hired' },
  { key: 'rejection', num: '✕', label: 'Penolakan' },
];

const openSendEmailModal = async (app) => {
  if (!app) return;
  isBulkMode.value = false;
  sendEmailModalApp.value = app;
  sendType.value = 'immediate';
  scheduleDate.value = todayDateString.value;
  const nextHour = new Date();
  nextHour.setHours(nextHour.getHours() + 1);
  scheduleTime.value = `${String(nextHour.getHours()).padStart(2, '0')}:00`;

  if (!selectedChannels.value.length) {
    selectedChannels.value = ['email', 'whatsapp'];
  }
  emailForm.value.attachment = null;
  emailForm.value.attachment_name = '';
  
  if (!Object.keys(emailTemplatesList.value).length) {
    await fetchEmailTemplates();
  }
  
  const stgName = (app.stage?.name || '').toLowerCase();
  let defaultKey = 'interview_hr';
  if (stgName.includes('screen')) defaultKey = 'interview_hr';
  else if (stgName.includes('interview hr') || stgName === 'interview') defaultKey = 'psikotes';
  else if (stgName.includes('psiko')) defaultKey = 'kompetensi';
  else if (stgName.includes('kompetensi')) defaultKey = 'interview_user';
  else if (stgName.includes('user')) defaultKey = 'background_check';
  else if (stgName.includes('backgro') || stgName.includes('check')) defaultKey = 'offering';
  else if (stgName.includes('offer')) defaultKey = 'hired';
  else if (stgName.includes('hire')) defaultKey = 'hired';
  else if (stgName.includes('reject') || stgName.includes('tolak')) defaultKey = 'rejection';
  
  applyEmailTemplate(defaultKey);
};

const openBulkNotificationModal = async () => {
  if (!selectedAppIds.value.length) return;
  isBulkMode.value = true;
  sendType.value = 'immediate';
  scheduleDate.value = todayDateString.value;
  const nextHour = new Date();
  nextHour.setHours(nextHour.getHours() + 1);
  scheduleTime.value = `${String(nextHour.getHours()).padStart(2, '0')}:00`;
  
  // Use first selected app for template variable preview
  const firstApp = applications.value.find(a => a.id === selectedAppIds.value[0]) || applications.value[0];
  sendEmailModalApp.value = firstApp || { full_name: 'Pelamar Terpilih', email: 'multi@pelamar' };
  
  if (!selectedChannels.value.length) {
    selectedChannels.value = ['email', 'whatsapp'];
  }
  emailForm.value.attachment = null;
  emailForm.value.attachment_name = '';

  if (!Object.keys(emailTemplatesList.value).length) {
    await fetchEmailTemplates();
  }

  applyEmailTemplate('interview_hr');
};

const closeNotificationModal = () => {
  sendEmailModalApp.value = null;
  isBulkMode.value = false;
  sendType.value = 'immediate';
  scheduleDate.value = '';
  scheduleTime.value = '';
};

const applyEmailTemplate = (key) => {
  activeEmailTemplateKey.value = key;
  const tpl = emailTemplatesList.value[key];
  if (!tpl || !sendEmailModalApp.value) return;

  const app = sendEmailModalApp.value;
  const name = isBulkMode.value ? '{nama_pelamar}' : (app.full_name || 'Pelamar');
  const pos = app.job_posting?.title || 'Posisi Lowongan';
  const comp = 'OCEAN SPACE';
  const loc = app.job_posting?.location || 'Indonesia';

  const replaceTags = (text) => {
    if (!text) return '';
    if (isBulkMode.value) {
      return text
        .replaceAll('{posisi}', pos)
        .replaceAll('{perusahaan}', comp)
        .replaceAll('{lokasi}', loc);
    }
    return text
      .replaceAll('{nama_pelamar}', name)
      .replaceAll('{posisi}', pos)
      .replaceAll('{perusahaan}', comp)
      .replaceAll('{lokasi}', loc);
  };

  emailForm.value.subject = replaceTags(tpl.subject);
  emailForm.value.body_message = replaceTags(tpl.body);
  emailForm.value.badge_text = tpl.badge || 'Notifikasi Rekrutmen';
  emailForm.value.info_box_title = tpl.info_title || 'Detail Informasi';
  emailForm.value.action_label = tpl.action_label || '';
  emailForm.value.special_note = tpl.default_note || '';

  if (key === 'interview_hr' || key === 'interview_user' || key === 'interview') {
    emailForm.value.venue_or_method = 'Online (Google Meet)';
    emailForm.value.schedule = '';
    emailForm.value.action_url = '';
  } else if (key === 'psikotes') {
    emailForm.value.venue_or_method = 'Online Assessment Platform';
    emailForm.value.schedule = 'Batas Pengerjaan: 3 hari kerja';
    emailForm.value.action_url = '';
  } else if (key === 'kompetensi') {
    emailForm.value.venue_or_method = 'Online Assignment / Submission';
    emailForm.value.schedule = 'Batas Pengumpulan: 3 hari kerja';
    emailForm.value.action_url = '';
  } else if (key === 'background_check') {
    emailForm.value.venue_or_method = 'Online Form / Verifikasi HR';
    emailForm.value.schedule = 'Batas Pengisian: 2 hari kerja';
    emailForm.value.action_url = '';
  } else if (key === 'offering') {
    emailForm.value.venue_or_method = loc;
    emailForm.value.schedule = 'Batas Konfirmasi: 3 hari kerja';
    emailForm.value.action_url = '';
  } else if (key === 'hired') {
    emailForm.value.venue_or_method = `Kantor ${comp} (${loc})`;
    emailForm.value.schedule = 'Hari Pertama Masuk Kerja: 08:30 WIB';
    emailForm.value.action_url = '';
  } else if (key === 'screening') {
    emailForm.value.venue_or_method = 'Tahap Peninjauan Berkas';
    emailForm.value.schedule = '';
    emailForm.value.action_url = '';
  } else {
    emailForm.value.action_url = '';
    emailForm.value.schedule = '';
    emailForm.value.venue_or_method = '';
  }

  adjustTextareaHeight();
};

const bodyTextareaRef = ref(null);

const adjustTextareaHeight = () => {
  nextTick(() => {
    if (bodyTextareaRef.value) {
      bodyTextareaRef.value.style.height = 'auto';
      bodyTextareaRef.value.style.height = `${Math.max(140, bodyTextareaRef.value.scrollHeight + 8)}px`;
    }
  });
};

const insertTag = (tag) => {
  if (!emailForm.value.body_message) {
    emailForm.value.body_message = tag;
  } else {
    emailForm.value.body_message += ' ' + tag;
  }
  adjustTextareaHeight();
};

const executeSendNotification = async () => {
  if (!sendEmailModalApp.value) return;

  if (!selectedChannels.value.length) {
    Swal.fire({
      icon: 'warning',
      title: 'Pilih Kanal Notifikasi',
      text: 'Harap centang minimal salah satu kanal: Email atau WhatsApp.',
      confirmButtonColor: '#0c2340',
    });
    return;
  }

  if (sendType.value === 'scheduled') {
    if (!scheduleDate.value || !scheduleTime.value) {
      Swal.fire({
        icon: 'warning',
        title: 'Tentukan Jadwal Pengiriman',
        text: 'Harap lengkapi tanggal dan jam pengiriman notifikasi.',
        confirmButtonColor: '#0c2340',
      });
      return;
    }

    const scheduledDateObj = new Date(`${scheduleDate.value}T${scheduleTime.value}:00`);
    if (scheduledDateObj <= new Date()) {
      Swal.fire({
        icon: 'warning',
        title: 'Waktu Tidak Valid',
        text: 'Waktu pengiriman terjadwal harus berada di waktu masa depan (setelah waktu saat ini).',
        confirmButtonColor: '#0c2340',
      });
      return;
    }
  }

  isSendingEmail.value = true;

  try {
    const formData = new FormData();
    formData.append('subject', emailForm.value.subject || '');
    formData.append('body_message', emailForm.value.body_message || '');
    formData.append('schedule', emailForm.value.schedule || '');
    formData.append('venue_or_method', emailForm.value.venue_or_method || '');
    formData.append('action_url', emailForm.value.action_url || '');
    formData.append('action_label', emailForm.value.action_label || '');
    formData.append('special_note', emailForm.value.special_note || '');
    formData.append('badge_text', emailForm.value.badge_text || '');
    formData.append('info_box_title', emailForm.value.info_box_title || '');

    formData.append('send_type', sendType.value);
    if (sendType.value === 'scheduled') {
      formData.append('scheduled_at', `${scheduleDate.value} ${scheduleTime.value}:00`);
    }

    formData.append('template_key', activeEmailTemplateKey.value);

    selectedChannels.value.forEach((ch) => {
      formData.append('channels[]', ch);
    });

    if (emailForm.value.attachment) {
      formData.append('attachment', emailForm.value.attachment);
    }

    if (isBulkMode.value) {
      selectedAppIds.value.forEach((id) => {
        formData.append('application_ids[]', id);
      });

      const res = await axios.post('/rekrutmen/api/applications/bulk-send-notification', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      const deliveryTimeStr = res.data.formatted_scheduled_at || schedulePreviewText.value;
      isSendingEmail.value = false;
      const count = selectedAppIds.value.length;
      selectedAppIds.value = [];
      closeNotificationModal();

      // Refresh applications immediately so stage changes reflect in Table & Kanban
      await store.fetchApplications('', false).catch(() => {});

      if (res.data.scheduled) {
        Swal.fire({
          icon: 'success',
          title: 'Notifikasi Massal Berhasil Dijadwalkan',
          html: `
            <div class="text-xs text-slate-600 mt-2 space-y-3">
              <p class="text-slate-700">Notifikasi untuk <strong>${count} pelamar terpilih</strong> akan dikirim secara otomatis pada:</p>
              <div class="inline-flex items-center gap-2 px-3.5 py-2 bg-blue-50 text-blue-700 font-semibold rounded-lg border border-blue-100 text-xs shadow-2xs">
                <span>📅</span>
                <span>${deliveryTimeStr}</span>
              </div>
            </div>
          `,
          confirmButtonText: 'Selesai',
          confirmButtonColor: '#2563eb',
          customClass: {
            popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
            title: 'text-base font-bold text-slate-900',
            confirmButton: 'px-6 py-2 rounded-lg text-xs font-semibold'
          }
        });
      } else {
        const stats = res.data.stats || {};
        let recapHtml = `
          <div class="text-xs text-left space-y-1.5 mt-2 bg-slate-50 p-3 rounded-lg border border-slate-200">
            <div><strong>Total Sasaran:</strong> ${stats.total || count} pelamar</div>
        `;

        if (selectedChannels.value.includes('email')) {
          recapHtml += `
            <div class="text-blue-700">📧 <strong>Email:</strong> ${stats.email_success || 0} berhasil terkirim (Gagal: ${stats.email_failed || 0}, Email Kosong: ${stats.skipped_no_email || 0})</div>
          `;
        }

        if (selectedChannels.value.includes('whatsapp')) {
          recapHtml += `
            <div class="text-emerald-700">💬 <strong>WhatsApp:</strong> ${stats.whatsapp_success || 0} berhasil terkirim (Gagal: ${stats.whatsapp_failed || 0})</div>
          `;
        }

        recapHtml += `</div>`;

        Swal.fire({
          icon: 'success',
          title: 'Notifikasi Massal Berhasil Dikirim!',
          html: recapHtml,
          confirmButtonColor: '#2563eb',
          customClass: {
            popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
            title: 'text-sm font-bold text-slate-900',
            confirmButton: 'px-6 py-2 rounded-lg text-xs font-semibold'
          }
        });
      }
    } else {
      // Single candidate notification
      const app = sendEmailModalApp.value;

      const res = await axios.post(`/rekrutmen/api/applications/${app.id}/send-notification`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      const deliveryTimeStr = res.data.formatted_scheduled_at || schedulePreviewText.value;
      isSendingEmail.value = false;
      closeNotificationModal();

      // Update local state and refresh applications list so stage change is immediate
      if (res.data?.new_stage) {
        app.current_stage_id = res.data.new_stage.id;
        const targetStage = stages.value.find(s => String(s.id) === String(res.data.new_stage.id));
        if (targetStage) {
          app.stage = { id: targetStage.id, name: targetStage.name, color: targetStage.color };
        }
        if (selectedApp.value && String(selectedApp.value.id) === String(app.id)) {
          selectedApp.value.current_stage_id = res.data.new_stage.id;
          if (targetStage) {
            selectedApp.value.stage = { id: targetStage.id, name: targetStage.name, color: targetStage.color };
          }
        }
      }
      await store.fetchApplications('', false).catch(() => {});

      if (res.data.scheduled) {
        Swal.fire({
          icon: 'success',
          title: 'Notifikasi Berhasil Dijadwalkan',
          html: `
            <div class="text-xs text-slate-600 mt-2 space-y-3">
              <p class="text-slate-700">Notifikasi untuk <strong>${app.full_name}</strong> akan dikirim secara otomatis pada:</p>
              <div class="inline-flex items-center gap-2 px-3.5 py-2 bg-blue-50 text-blue-700 font-semibold rounded-lg border border-blue-100 text-xs shadow-2xs">
                <span>📅</span>
                <span>${deliveryTimeStr}</span>
              </div>
            </div>
          `,
          confirmButtonText: 'Selesai',
          confirmButtonColor: '#2563eb',
          customClass: {
            popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
            title: 'text-base font-bold text-slate-900',
            confirmButton: 'px-6 py-2 rounded-lg text-xs font-semibold'
          }
        });
      } else {
        Swal.fire({
          icon: 'success',
          title: 'Notifikasi Berhasil Terkirim!',
          html: `<div class="text-xs text-slate-600 mt-1">${res.data.message || 'Notifikasi berhasil dikirimkan ke kandidat.'}</div>`,
          timer: 3000,
          showConfirmButton: false,
          iconColor: '#10b981',
          customClass: {
            popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
            title: 'text-sm font-bold text-slate-900',
          }
        });
      }
    }
  } catch (err) {
    isSendingEmail.value = false;
    Swal.fire({
      icon: 'error',
      title: sendType.value === 'scheduled' ? 'Gagal Menjadwalkan Notifikasi' : 'Gagal Mengirim Notifikasi',
      text: err.response?.data?.message || 'Terjadi kesalahan saat memproses notifikasi.',
      confirmButtonColor: '#e11d48',
      customClass: {
        popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
        title: 'text-sm font-bold text-slate-900',
        confirmButton: 'px-4 py-2 rounded-xl text-xs font-bold'
      }
    });
  }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
