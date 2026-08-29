<template>
  <div class="space-y-5">
    <!-- Breadcrumb & Top Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium mb-1">
          <span>Job Applications</span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-400" />
          <span class="text-slate-700 font-semibold">{{ activeJobTitle ? activeJobTitle : 'Semua Pelamar' }}</span>
        </div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
          <span>{{ activeJobTitle ? `Pelamar: ${activeJobTitle}` : 'Daftar Pelamar Kerja' }}</span>
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          Ringkasan seluruh kandidat pelamar dan hasil screening kualifikasi otomatis.
        </p>
      </div>

      <!-- Controls & View Switcher -->
      <div class="flex items-center gap-2.5">
        <!-- Active Filter with Reset button -->
        <button
          v-if="activeJobId"
          @click="resetJobFilter"
          class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 flex items-center gap-1.5 transition-colors cursor-pointer"
          title="Tampilkan Semua Pelamar"
        >
          <span>Tampilkan Semua Lowongan</span>
          <span class="text-slate-400 font-bold">&times;</span>
        </button>

        <!-- View Toggle (Table / Kanban) -->
        <div class="flex items-center bg-slate-100 border border-slate-200 rounded-lg p-0.5">
          <button
            @click="viewMode = 'table'"
            :class="[
              'px-3 py-1 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
              viewMode === 'table'
                ? 'bg-white text-blue-600 shadow-2xs font-bold'
                : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            <ListFilter class="w-3.5 h-3.5" />
            <span>Tabel</span>
          </button>
          <button
            @click="viewMode = 'kanban'"
            :class="[
              'px-3 py-1 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
              viewMode === 'kanban'
                ? 'bg-white text-blue-600 shadow-2xs font-bold'
                : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            <Kanban class="w-3.5 h-3.5" />
            <span>Kanban</span>
          </button>
        </div>

        <!-- Search Input -->
        <div class="relative w-64">
          <Search class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search kandidat..."
            class="w-full bg-white border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
          />
        </div>
      </div>
    </div>

    <!-- METRIC / KPI STAT CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
      <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Pelamar</div>
        <div class="text-2xl font-bold text-slate-900 mt-1.5">{{ applications.length }}</div>
        <div class="text-[11px] text-slate-400 mt-0.5 font-medium">Kandidat terdaftar</div>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200/90 border-t-2 border-t-emerald-500 shadow-2xs">
        <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Direkomendasikan</div>
        <div class="text-2xl font-bold text-emerald-600 mt-1.5">{{ recommendedCount }}</div>
        <div class="text-[11px] text-emerald-600 mt-0.5 font-medium">&ge; 80% Match Score</div>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200/90 border-t-2 border-t-amber-500 shadow-2xs">
        <div class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Dipertimbangkan</div>
        <div class="text-2xl font-bold text-amber-600 mt-1.5">{{ consideredCount }}</div>
        <div class="text-[11px] text-amber-600 mt-0.5 font-medium">60% - 79% Match Score</div>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200/90 border-t-2 border-t-rose-500 shadow-2xs">
        <div class="text-[11px] font-bold text-rose-700 uppercase tracking-wider">Kurang Sesuai</div>
        <div class="text-2xl font-bold text-rose-600 mt-1.5">{{ notSuitableCount }}</div>
        <div class="text-[11px] text-rose-500 mt-0.5 font-medium">&lt; 60% Match Score</div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="store.loading.applications" class="bg-white rounded-xl border border-slate-200/90 shadow-2xs">
      <LoadingState
        title="Sedang memuat data pelamar..."
        subtitle="Menyiapkan daftar kandidat dan hasil analisis screening..."
      />
    </div>

    <!-- TABLE LIST VIEW -->
    <div
      v-else-if="viewMode === 'table'"
      class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden"
    >
      <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
        <div class="text-xs text-slate-500 font-medium">
          Menampilkan <strong class="text-slate-900">{{ applications.length }}</strong> kandidat pelamar
        </div>
        <div class="flex items-center gap-2">
          <button class="flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
            <Filter class="w-3.5 h-3.5 text-slate-500" />
            <span class="text-[11px] font-semibold text-slate-700">Filter</span>
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-slate-50/70 text-slate-500 border-b border-slate-200/80">
              <th class="py-2.5 px-4 w-10">
                <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5" />
              </th>
              <th class="py-2.5 px-4 text-[11px] font-bold uppercase tracking-wider">Nama Pelamar</th>
              <th class="py-2.5 px-4 text-[11px] font-bold uppercase tracking-wider">Lowongan Dilamar</th>
              <th class="py-2.5 px-4 text-[11px] font-bold uppercase tracking-wider">Hasil Screening AI</th>
              <th class="py-2.5 px-4 text-[11px] font-bold uppercase tracking-wider">Tahapan Seleksi</th>
              <th class="py-2.5 px-4 text-[11px] font-bold uppercase tracking-wider">Status</th>
              <th class="py-2.5 px-4 text-[11px] font-bold uppercase tracking-wider">Tanggal Masuk</th>
              <th class="py-2.5 px-4 text-right w-10"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="app in applications" :key="app.id" class="hover:bg-slate-50/60 transition-colors group">
              <td class="py-3 px-4 align-top"><input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5 mt-0.5" /></td>
              <td class="py-3 px-4 align-top">
                <div class="font-semibold text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors cursor-pointer" @click="openDetail(app)">{{ app.full_name }}</div>
                <div class="text-[11px] text-slate-400 mt-0.5">{{ app.email }} &bull; {{ app.phone }}</div>
              </td>
              <td class="py-3 px-4 align-top text-slate-700 text-xs font-medium">{{ app.job_posting?.title || '-' }}</td>
              <td class="py-3 px-4 align-top">
                <div v-if="app.ai_match_score !== null && app.ai_match_score !== undefined" class="space-y-0.5">
                  <span :class="['inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border cursor-pointer', getAiBadgeClasses(app.ai_match_score, app.ai_recommendation)]" @click="openDetail(app)">
                    {{ app.ai_match_score }}% {{ formatAiRecommendation(app.ai_recommendation) }}
                  </span>
                  <div v-if="app.ai_summary" class="text-[11px] text-slate-500 leading-tight line-clamp-1 max-w-xs">{{ app.ai_summary }}</div>
                </div>
                <div v-else class="text-[11px] text-slate-400 italic">Sedang dievaluasi...</div>
              </td>
              <td class="py-3 px-4 align-top"><span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">{{ app.stage?.name || 'Screening CV' }}</span></td>
              <td class="py-3 px-4 align-top"><span :class="['inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border', getStatusBadge(app.status)]">{{ app.status }}</span></td>
              <td class="py-3 px-4 align-top text-slate-600 text-xs whitespace-nowrap">{{ app.created_at }}</td>
              <td class="py-3 px-4 align-top text-right whitespace-nowrap">
                <button @click="openDetail(app)" class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded transition-colors cursor-pointer"><MoreVertical class="w-4 h-4" /></button>
              </td>
            </tr>
            <tr v-if="!applications?.length"><td colspan="8" class="py-16 text-center text-xs text-slate-500">Tidak ada data pelamar kerja untuk lowongan ini.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- KANBAN BOARD -->
    <div v-else class="flex gap-4 overflow-x-auto pb-4 custom-scrollbar min-h-[calc(100vh-230px)] items-start select-none">
      <div v-for="stage in stages" :key="stage.id" class="w-80 shrink-0 bg-slate-100/70 border rounded-2xl flex flex-col max-h-[calc(100vh-230px)] transition-all duration-150" :class="dragOverStageId === stage.id ? 'border-blue-500 bg-blue-50/50 ring-2 ring-blue-400/40' : 'border-slate-200/80'" @dragover.prevent="handleDragOver(stage.id)" @dragleave="handleDragLeave(stage.id)" @drop="handleDrop(stage.id)">
        <div class="p-3.5 border-b border-slate-200/80 bg-white/70 rounded-t-2xl flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: stage.color || '#3b82f6' }"></span>
            <span class="text-xs font-bold text-slate-800 tracking-wide uppercase">{{ stage.name }}</span>
          </div>
          <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">{{ getStageApplications(stage.id).length }}</span>
        </div>
        <div class="p-3 space-y-2.5 overflow-y-auto flex-1 custom-scrollbar">
          <div v-for="app in getStageApplications(stage.id)" :key="app.id" class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs hover:shadow-xs transition-all cursor-grab active:cursor-grabbing hover:border-blue-400" draggable="true" @dragstart="handleDragStart(app, $event)" @dragend="handleDragEnd" @click="openDetail(app)">
            <div class="flex items-start justify-between gap-2">
              <span class="font-bold text-xs text-slate-900 hover:text-blue-600">{{ app.full_name }}</span>
              <span v-if="app.ai_match_score !== null && app.ai_match_score !== undefined" :class="['px-1.5 py-0.5 rounded text-[10px] font-bold border shrink-0', getAiBadgeClasses(app.ai_match_score, app.ai_recommendation)]">{{ app.ai_match_score }}%</span>
            </div>
            <div class="text-[11px] text-slate-500 mt-1 line-clamp-1">{{ app.job_posting?.title || '-' }}</div>
            <div class="flex items-center justify-between text-[10px] text-slate-400 pt-2.5 mt-2.5 border-t border-slate-100">
              <span>{{ app.created_at }}</span>
              <span class="text-blue-600 font-semibold">{{ app.source || 'Website' }}</span>
            </div>
          </div>
          <div v-if="!getStageApplications(stage.id).length" class="py-10 text-center text-[11px] text-slate-400 border border-dashed border-slate-200 rounded-xl">Geser kandidat ke kolom ini</div>
        </div>
      </div>
    </div>

    <!-- Exact ATS Candidate Detail Modal matching user screenshot -->
    <div
      v-if="selectedApp"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
      @click.self="selectedApp = null"
    >
      <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-6xl max-h-[94vh] flex flex-col shadow-2xl overflow-hidden">
        <!-- 1. Top Navbar -->
        <div class="px-6 py-3.5 border-b border-slate-100 flex items-center justify-between bg-white shrink-0">
          <button
            @click="selectedApp = null"
            class="flex items-center gap-2 text-sm font-bold text-slate-800 hover:text-blue-600 transition-colors cursor-pointer"
          >
            <ArrowLeft class="w-4 h-4 text-slate-700" />
            <span>Kandidat Detail</span>
          </button>

          <div class="flex items-center gap-2">
            <a
              v-if="selectedApp.resume_url"
              :href="selectedApp.resume_url"
              target="_blank"
              class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 flex items-center gap-1.5 shadow-2xs transition-colors"
            >
              <span>Buka CV di Tab Baru</span>
              <ExternalLink class="w-3.5 h-3.5 text-slate-500" />
            </a>

            <button
              @click="selectedApp = null"
              class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl text-xl font-bold cursor-pointer transition-colors"
            >
              &times;
            </button>
          </div>
        </div>

        <!-- 2. Candidate Hero Info Banner -->
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white shrink-0">
          <div class="flex items-center gap-4">
            <!-- Circular Avatar Initials -->
            <div class="w-13 h-13 rounded-full bg-blue-50 text-blue-700 font-extrabold text-base flex items-center justify-center border border-blue-100 shadow-2xs">
              {{ getInitials(selectedApp.full_name) }}
            </div>

            <div>
              <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">{{ selectedApp.full_name }}</h2>
              <div class="text-xs font-bold text-blue-600 mt-0.5">{{ selectedApp.job_posting?.title || '-' }}</div>
              <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-1 font-medium">
                <Calendar class="w-3.5 h-3.5 text-slate-400" />
                <span>{{ selectedApp.created_at }}</span>
                <span>|</span>
                <span>Tahapan: <strong class="text-blue-600 font-semibold">{{ selectedApp.stage?.name || 'Screening CV' }}</strong></span>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <!-- Match Score Box -->
            <div class="bg-emerald-50/90 border border-emerald-200 rounded-xl px-4 py-2 text-center min-w-[95px] shadow-2xs">
              <div class="text-lg font-extrabold text-emerald-600 leading-tight">
                {{ selectedApp.ai_match_score !== null && selectedApp.ai_match_score !== undefined ? selectedApp.ai_match_score : 80 }}%
              </div>
              <div class="text-[9px] font-extrabold text-emerald-700 tracking-wider uppercase mt-0.5">MATCH SCORE</div>
            </div>

            <!-- Status Box -->
            <div class="bg-amber-50/90 border border-amber-300 rounded-xl px-3.5 py-2 text-xs font-bold text-amber-700 flex items-center gap-1.5 shadow-2xs uppercase">
              <Clock class="w-3.5 h-3.5 text-amber-600" />
              <span>{{ selectedApp.status ? selectedApp.status.replace('_', ' ') : 'IN PROGRESS' }}</span>
            </div>
          </div>
        </div>

        <!-- 3. Two-Column Split Body (Scrollable) -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-6 bg-slate-50/50">
          <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
            <!-- LEFT COLUMN (Cards: Hasil Screening, Informasi Kandidat, Informasi Lamaran) -->
            <div class="md:col-span-4 space-y-4">
              <!-- Card 1: Hasil Screening -->
              <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs space-y-3">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Hasil Screening</div>

                <div class="flex items-center gap-3.5">
                  <!-- Circular Gauge Indicator -->
                  <div class="relative w-18 h-18 shrink-0 flex items-center justify-center">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                      <path
                        class="text-slate-100 stroke-current"
                        stroke-width="3.5"
                        fill="none"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      />
                      <path
                        class="text-emerald-500 stroke-current"
                        stroke-width="3.5"
                        :stroke-dasharray="`${selectedApp.ai_match_score || 80}, 100`"
                        stroke-linecap="round"
                        fill="none"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                      />
                    </svg>
                    <div class="absolute flex flex-col items-center justify-center">
                      <span class="text-xs font-bold text-slate-900">{{ selectedApp.ai_match_score || 80 }}%</span>
                      <span class="text-[7px] text-slate-400 font-semibold uppercase">Match Score</span>
                    </div>
                  </div>

                  <!-- Narrative -->
                  <p class="text-[11px] text-slate-700 leading-relaxed font-normal">
                    {{ selectedApp.ai_summary || 'Kandidat telah memenuhi sebagian besar persyaratan posisi yang dilamar.' }}
                  </p>
                </div>

                <!-- Bottom Gauge Bar -->
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full bg-emerald-500 rounded-full" :style="{ width: (selectedApp.ai_match_score || 80) + '%' }"></div>
                </div>
              </div>

              <!-- Card 2: Informasi Kandidat -->
              <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs space-y-3">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Informasi Kandidat</div>

                <div class="space-y-2.5 text-xs">
                  <!-- Email -->
                  <div class="flex items-start gap-2.5">
                    <Mail class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                    <div class="flex-1">
                      <div class="text-[11px] text-slate-400 font-medium">Email</div>
                      <div class="font-semibold text-slate-900 select-all">{{ selectedApp.email }}</div>
                    </div>
                  </div>

                  <!-- No. Telepon -->
                  <div class="flex items-start gap-2.5">
                    <Phone class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                    <div class="flex-1">
                      <div class="text-[11px] text-slate-400 font-medium">No. Telepon / WA</div>
                      <div class="font-semibold text-slate-900 select-all">{{ selectedApp.phone || '-' }}</div>
                    </div>
                  </div>

                  <!-- Alamat Domisili -->
                  <div class="flex items-start gap-2.5">
                    <MapPin class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                    <div class="flex-1">
                      <div class="text-[11px] text-slate-400 font-medium">Alamat Domisili</div>
                      <div class="text-slate-800 leading-snug font-medium">{{ selectedApp.address_domicile || selectedApp.address || '-' }}</div>
                    </div>
                  </div>

                  <!-- Jenis Kelamin -->
                  <div class="flex items-start gap-2.5">
                    <User class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                    <div class="flex-1">
                      <div class="text-[11px] text-slate-400 font-medium">Jenis Kelamin</div>
                      <div class="font-semibold text-slate-900">{{ selectedApp.gender || '-' }}</div>
                    </div>
                  </div>

                  <!-- Tanggal Lahir -->
                  <div class="flex items-start gap-2.5">
                    <Calendar class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                    <div class="flex-1">
                      <div class="text-[11px] text-slate-400 font-medium">Tanggal Lahir</div>
                      <div class="font-semibold text-slate-900">{{ selectedApp.birth_date || '-' }}</div>
                    </div>
                  </div>

                  <!-- Status Pernikahan -->
                  <div class="flex items-start gap-2.5">
                    <Heart class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                    <div class="flex-1">
                      <div class="text-[11px] text-slate-400 font-medium">Status Pernikahan</div>
                      <div class="font-semibold text-slate-900">{{ selectedApp.marital_status || '-' }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 3: Informasi Lamaran -->
              <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs space-y-3">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Informasi Lamaran</div>

                <div class="space-y-2.5 text-xs">
                  <!-- Tanggal Melamar -->
                  <div class="flex items-start gap-2.5">
                    <Calendar class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                    <div class="flex-1">
                      <div class="text-[11px] text-slate-400 font-medium">Tanggal Melamar</div>
                      <div class="font-semibold text-slate-900">{{ selectedApp.created_at }}</div>
                    </div>
                  </div>

                  <!-- Sumber Lamaran -->
                  <div class="flex items-start gap-2.5">
                    <Globe class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                    <div class="flex-1">
                      <div class="text-[11px] text-slate-400 font-medium">Sumber Lamaran</div>
                      <div class="font-semibold text-slate-900">{{ selectedApp.source || 'oceanspace.co.id' }}</div>
                    </div>
                  </div>

                  <!-- Posisi Dilamar -->
                  <div class="flex items-start gap-2.5">
                    <Briefcase class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                    <div class="flex-1">
                      <div class="text-[11px] text-slate-400 font-medium">Posisi Dilamar</div>
                      <div class="font-semibold text-blue-600">{{ selectedApp.job_posting?.title || '-' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- RIGHT COLUMN (DOKUMEN CV & PROFIL KANDIDAT Card) -->
            <div class="md:col-span-8 bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs flex flex-col min-h-[680px]">
              <!-- Tabs Header -->
              <div class="flex items-center justify-between pb-3 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-1 bg-slate-100/90 p-1 rounded-xl">
                  <button
                    @click="activeRightTab = 'cv'"
                    :class="[
                      'px-3.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
                      activeRightTab === 'cv' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800'
                    ]"
                  >
                    <FileText class="w-3.5 h-3.5 text-slate-500" />
                    <span>Berkas CV Asli</span>
                  </button>

                  <button
                    @click="activeRightTab = 'profile'"
                    :class="[
                      'px-3.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
                      activeRightTab === 'profile' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800'
                    ]"
                  >
                    <User class="w-3.5 h-3.5 text-slate-500" />
                    <span>Profil Lengkap Pelamar</span>
                  </button>
                </div>

                <div v-if="activeRightTab === 'cv'" class="flex items-center gap-2">
                  <a
                    v-if="selectedApp.resume_url"
                    :href="selectedApp.resume_url"
                    target="_blank"
                    class="text-slate-600 hover:text-slate-900 font-semibold text-xs flex items-center gap-1 cursor-pointer"
                  >
                    <span>Buka Layar Penuh</span>
                    <ExternalLink class="w-3 h-3 text-slate-400" />
                  </a>
                </div>
              </div>

              <!-- TAB 1: CV Viewer (Full Height, Fit to Width Canvas) -->
              <div v-show="activeRightTab === 'cv'" class="w-full mt-3 flex-1 flex flex-col">
                <div class="w-full bg-white rounded-xl overflow-hidden border border-slate-200 shadow-2xs" style="height: 740px; min-height: 740px;">
                  <iframe
                    v-if="selectedApp.resume_url"
                    :src="selectedApp.resume_url + '#view=FitH&toolbar=0&navpanes=0'"
                    style="width: 100%; height: 740px; min-height: 740px; border: none; display: block;"
                    title="Curriculum Vitae Viewer"
                  ></iframe>
                  <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 p-8 bg-slate-50">
                    <FileText class="w-12 h-12 stroke-1 text-slate-300 mb-2" />
                    <p class="text-xs">Dokumen CV belum dilampirkan oleh kandidat ini.</p>
                  </div>
                </div>
              </div>

              <!-- TAB 2: Profil Lengkap Pelamar -->
              <div v-show="activeRightTab === 'profile'" class="flex-1 py-4 space-y-4 overflow-y-auto custom-scrollbar">
                <!-- Identitas & Data Diri -->
                <div class="border border-slate-200 rounded-xl p-4 space-y-3 bg-white">
                  <h3 class="font-bold text-xs uppercase tracking-wider text-slate-700 border-b pb-2 flex items-center gap-1.5">
                    <User class="w-3.5 h-3.5 text-slate-500" />
                    <span>Identitas & Data Pribadi</span>
                  </h3>
                  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Nama Lengkap</span>
                      <div class="font-bold text-slate-900 mt-0.5">{{ selectedApp.full_name }}</div>
                    </div>
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Jenis Kelamin</span>
                      <div class="font-semibold text-slate-800 mt-0.5">{{ selectedApp.gender || '-' }}</div>
                    </div>
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Tanggal Lahir</span>
                      <div class="font-semibold text-slate-800 mt-0.5">{{ selectedApp.birth_date || '-' }}</div>
                    </div>
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Status Pernikahan</span>
                      <div class="font-semibold text-slate-800 mt-0.5">{{ selectedApp.marital_status || '-' }}</div>
                    </div>
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Email</span>
                      <div class="font-semibold text-slate-900 mt-0.5 select-all">{{ selectedApp.email }}</div>
                    </div>
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Nomor Telepon / WhatsApp</span>
                      <div class="font-semibold text-slate-900 mt-0.5 select-all">{{ selectedApp.phone || '-' }}</div>
                    </div>
                  </div>
                </div>

                <!-- Alamat & Domisili -->
                <div class="border border-slate-200 rounded-xl p-4 space-y-3 bg-white">
                  <h3 class="font-bold text-xs uppercase tracking-wider text-slate-700 border-b pb-2 flex items-center gap-1.5">
                    <MapPin class="w-3.5 h-3.5 text-slate-500" />
                    <span>Alamat & Domisili Pelamar</span>
                  </h3>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Alamat Domisili Sekarang</span>
                      <div class="text-slate-800 leading-snug font-medium mt-0.5">{{ selectedApp.address_domicile || selectedApp.address || '-' }}</div>
                    </div>
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Alamat Sesuai KTP</span>
                      <div class="text-slate-800 leading-snug font-medium mt-0.5">{{ selectedApp.address_ktp || '-' }}</div>
                    </div>
                  </div>
                </div>

                <!-- Kontak Darurat -->
                <div class="border border-slate-200 rounded-xl p-4 space-y-3 bg-white">
                  <h3 class="font-bold text-xs uppercase tracking-wider text-slate-700 border-b pb-2 flex items-center gap-1.5">
                    <Phone class="w-3.5 h-3.5 text-slate-500" />
                    <span>Kontak Darurat</span>
                  </h3>
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Nama Kontak Darurat</span>
                      <div class="font-semibold text-slate-900 mt-0.5">{{ selectedApp.emergency_contact_name || '-' }}</div>
                    </div>
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Hubungan / Relasi</span>
                      <div class="font-semibold text-slate-900 mt-0.5">{{ selectedApp.emergency_contact_relation || '-' }}</div>
                    </div>
                    <div>
                      <span class="text-slate-400 font-medium block text-[11px]">Nomor Telepon Darurat</span>
                      <div class="font-semibold text-slate-900 mt-0.5">{{ selectedApp.emergency_contact_phone || '-' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Bottom Action Footer Bar -->
        <div class="px-6 py-3.5 bg-white border-t border-slate-200 flex items-center justify-between shrink-0">
          <div class="flex items-center gap-2.5">
            <!-- Reject Button -->
            <button
              @click="handleStatusAction(selectedApp, 'rejected')"
              class="px-5 py-2.5 rounded-xl border border-rose-300 text-rose-600 bg-white hover:bg-rose-50 font-bold text-xs flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer"
            >
              <X class="w-3.5 h-3.5" />
              <span>Reject</span>
            </button>

            <!-- Shortlist Button -->
            <button
              @click="handleStatusAction(selectedApp, 'shortlisted')"
              class="px-5 py-2.5 rounded-xl border border-amber-300 text-amber-600 bg-white hover:bg-amber-50 font-bold text-xs flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer"
            >
              <Star class="w-3.5 h-3.5" />
              <span>Shortlist</span>
            </button>
          </div>

          <!-- Lanjut ke Tahap Berikutnya Button -->
          <button
            @click="handleAdvanceStage(selectedApp)"
            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl flex items-center gap-2 shadow-xs transition-colors cursor-pointer"
          >
            <span>Lanjut ke Tahap Berikutnya</span>
            <ArrowRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  ChevronRight, 
  Search, 
  Filter, 
  Kanban, 
  ListFilter, 
  MoreVertical, 
  Sparkles, 
  FileText, 
  ExternalLink, 
  User, 
  Phone, 
  MapPin, 
  Users, 
  Clock,
  ArrowLeft,
  ArrowRight,
  Mail,
  Heart,
  Globe,
  Briefcase,
  X,
  Star,
  Calendar
} from 'lucide-vue-next';
import { useRekrutmenStore } from '../stores/rekrutmen';
import LoadingState from '../components/LoadingState.vue';

const route = useRoute();
const router = useRouter();
const store = useRekrutmenStore();
const viewMode = ref('table');
const searchQuery = ref('');
const selectedApp = ref(null);
const activeRightTab = ref('cv');
const isAiScanning = ref(false);
const draggedApp = ref(null);
const dragOverStageId = ref(null);

const applications = computed(() => store.applications);
const stages = computed(() => store.stages);
const activeJobId = computed(() => route.query.job_id);

const getInitials = (name) => {
  if (!name) return 'U';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const activeJobTitle = computed(() => {
  if (route.query.job_id) {
    if (store.activeJob?.title) return store.activeJob.title;
    if (applications.value.length > 0 && applications.value[0]?.job_posting?.title) return applications.value[0].job_posting.title;
    return `Lowongan #${route.query.job_id}`;
  }
  return null;
});

const recommendedCount = computed(() => applications.value.filter(a => a.ai_match_score >= 80 || (a.ai_recommendation || '').includes('RECOMMENDED')).length);
const consideredCount = computed(() => applications.value.filter(a => (a.ai_match_score >= 60 && a.ai_match_score < 80) || (a.ai_recommendation || '').includes('CONSIDERED')).length);
const notSuitableCount = computed(() => applications.value.filter(a => (a.ai_match_score !== null && a.ai_match_score < 60) || (a.ai_recommendation || '').includes('NOT_SUITABLE')).length);

let debounceTimer = null;
const handleSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { loadData(); }, 250);
};

const loadData = () => { store.fetchApplications({ search: searchQuery.value, job_id: route.query.job_id || '' }, true); };
const resetJobFilter = () => { router.push({ path: '/admin/job-applications' }); };

onMounted(() => {
  if (route.query.search) {
    searchQuery.value = route.query.search;
  }
  loadData();
});

watch(() => route.query.job_id, () => { loadData(); });
watch(() => route.query.search, (newSearch) => {
  if (newSearch !== undefined && newSearch !== searchQuery.value) {
    searchQuery.value = newSearch || '';
    loadData();
  }
});

const getStageApplications = (stageId) => applications.value.filter(app => Number(app.current_stage_id) === Number(stageId));

const handleSingleAiScan = async (app) => {
  isAiScanning.value = true;
  try {
    const res = await store.analyzeCandidateWithAi(app.id);
    if (selectedApp.value && selectedApp.value.id === app.id) selectedApp.value = { ...selectedApp.value, ...res.application };
  } catch (err) { console.error(err); } finally { isAiScanning.value = false; }
};

const handleStatusAction = async (app, newStatus) => {
  if (!app) return;
  app.status = newStatus;
  await store.updateApplicationStatus(app.id, newStatus);
  selectedApp.value = null;
};

const handleAdvanceStage = async (app) => {
  if (!app) return;
  const currentStageId = Number(app.current_stage_id);
  const currentIdx = stages.value.findIndex(s => Number(s.id) === currentStageId);
  if (currentIdx !== -1 && currentIdx < stages.value.length - 1) {
    const nextStage = stages.value[currentIdx + 1];
    app.current_stage_id = nextStage.id;
    app.stage = nextStage;
    await store.updateApplicationStage(app.id, nextStage.id);
  }
  selectedApp.value = null;
};

const getAiBadgeClasses = (score, recommendation) => {
  const rec = (recommendation || '').toUpperCase();
  if (score >= 80 || rec.includes('RECOMMENDED')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if (score >= 60 || rec.includes('CONSIDERED')) return 'bg-amber-50 text-amber-700 border-amber-200';
  return 'bg-rose-50 text-rose-700 border-rose-200';
};

const formatAiRecommendation = (rec) => {
  const r = (rec || '').toUpperCase();
  if (r.includes('RECOMMENDED')) return 'Rekomendasi';
  if (r.includes('CONSIDERED')) return 'Dipertimbangkan';
  if (r.includes('NOT_SUITABLE')) return 'Kurang Sesuai';
  return 'Selesai';
};

const handleDragStart = (app, event) => {
  draggedApp.value = app;
  if (event.dataTransfer) { event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', String(app.id)); }
};
const handleDragOver = (stageId) => { dragOverStageId.value = stageId; };
const handleDragLeave = (stageId) => { if (dragOverStageId.value === stageId) dragOverStageId.value = null; };
const handleDragEnd = () => { draggedApp.value = null; dragOverStageId.value = null; };
const handleDrop = async (stageId) => {
  if (!draggedApp.value) return;
  const app = draggedApp.value;
  const prevId = app.current_stage_id;
  if (Number(prevId) === Number(stageId)) { handleDragEnd(); return; }
  app.current_stage_id = stageId;
  handleDragEnd();
  try { await store.updateApplicationStage(app.id, stageId); } catch (err) { app.current_stage_id = prevId; }
};

const getStatusBadge = (status) => {
  const s = status?.toLowerCase() || '';
  if (s.includes('hired') || s.includes('diterima')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if (s.includes('rejected') || s.includes('ditolak')) return 'bg-rose-50 text-rose-700 border-rose-200';
  return 'bg-slate-50 text-slate-600 border-slate-200';
};

const openDetail = (app) => { selectedApp.value = app; };

onMounted(() => { loadData(); });
</script>
