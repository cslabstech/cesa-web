<template>
  <div class="space-y-5">
    <!-- Breadcrumb & Top Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium mb-1">
          <span>Job Postings</span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-400" />
          <span class="text-slate-700 font-semibold">List</span>
        </div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">
          Lowongan Pekerjaan
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          Kelola seluruh daftar lowongan kerja aktif, status publikasi, dan pelamar.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <div class="relative w-72">
          <Search class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search lowongan atau lokasi..."
            class="w-full bg-white border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
          />
        </div>
      </div>
    </div>

    <!-- Alert / Toast Message -->
    <div
      v-if="toastMessage"
      :class="[
        'p-3.5 rounded-xl border flex items-center justify-between transition-all shadow-2xs',
        toastType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'
      ]"
    >
      <div class="flex items-center gap-2 text-xs font-semibold">
        <CheckCircle2 v-if="toastType === 'success'" class="w-4 h-4 text-emerald-600" />
        <AlertCircle v-else class="w-4 h-4 text-rose-600" />
        <span>{{ toastMessage }}</span>
      </div>
      <button @click="toastMessage = null" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
    </div>

    <!-- Loading State -->
    <div v-if="store.loading.postings" class="bg-white rounded-xl border border-slate-200 shadow-2xs">
      <LoadingState
        title="Sedang memuat data..."
        subtitle="Menyiapkan daftar lowongan kerja aktif..."
      />
    </div>

    <!-- Cards Grid (Clean Modern ATS Style) -->
    <div v-else-if="postings?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="job in postings"
        :key="job.id"
        class="bg-white border border-slate-200/90 hover:border-slate-300 rounded-xl p-5 flex flex-col justify-between transition-all shadow-2xs hover:shadow-sm group"
      >
        <div>
          <!-- Top Row: Status Badge & Location Tag -->
          <div class="flex items-center justify-between gap-2">
            <!-- Clickable Status Tag -->
            <button
              @click="togglePublish(job)"
              :class="[
                'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border transition-colors cursor-pointer inline-flex items-center gap-1.5',
                job.is_published
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                  : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'
              ]"
              :title="job.is_published ? 'Klik untuk ubah jadi Draft' : 'Klik untuk Publish lowongan ini'"
            >
              <span
                :class="[
                  'w-1.5 h-1.5 rounded-full',
                  job.is_published ? 'bg-emerald-500' : 'bg-slate-400'
                ]"
              ></span>
              <span>{{ job.is_published ? 'Published' : 'Draft' }}</span>
            </button>

            <!-- Location & Edit Action -->
            <div class="flex items-center gap-1.5">
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-slate-50 text-slate-600 border border-slate-200">
                <MapPin class="w-3 h-3 text-slate-400" />
                <span class="truncate max-w-[130px]">{{ job.location || 'Indonesia' }}</span>
              </span>

              <button
                @click="openEditModal(job)"
                class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded transition-colors cursor-pointer"
                title="Edit Lowongan"
              >
                <Edit3 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Job Title -->
          <h3
            class="text-base font-bold text-slate-900 mt-3 group-hover:text-blue-600 transition-colors cursor-pointer leading-snug"
            @click="openEditModal(job)"
          >
            {{ job.title }}
          </h3>

          <!-- Context Subtitle -->
          <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
            {{ job.context_description || `Lowongan #${job.id}` }}
          </p>

          <!-- Closing Date Row -->
          <div v-if="job.closing_date_formatted && job.closing_date_formatted !== '-'" class="mt-3 pt-2.5 border-t border-slate-100 flex items-center gap-1.5 text-[11px] text-slate-500">
            <Calendar class="w-3.5 h-3.5 text-slate-400" />
            <span>Batas Lamaran: <strong class="text-slate-700 font-semibold">{{ job.closing_date_formatted }}</strong></span>
          </div>
        </div>

        <!-- Card Footer (Applicants Count + Action Button) -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-700">
            <Users class="w-3.5 h-3.5 text-blue-600" />
            <span>{{ job.applications_count || 0 }} Pelamar</span>
          </div>

          <router-link
            :to="{ path: '/admin/job-applications', query: { job_id: job.id } }"
            class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline cursor-pointer"
          >
            <span>Lihat Pelamar</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </router-link>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!postings?.length" class="bg-white border border-slate-200 rounded-xl p-16 text-center text-xs text-slate-500 shadow-2xs">
      Belum ada lowongan pekerjaan yang terdaftar.
    </div>

    <!-- Edit Job Posting Modal -->
    <div
      v-if="editingJob"
      class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="editingJob = null"
    >
      <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-xl custom-scrollbar">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
          <div>
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Edit Lowongan Pekerjaan</div>
            <h2 class="text-base font-bold text-slate-900 mt-0.5">Lowongan #{{ editingJob.id }}</h2>
          </div>
          <button @click="editingJob = null" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="saveEditJob" class="p-6 space-y-4 text-xs">
          <!-- Title -->
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Judul Lowongan Pekerjaan *</label>
            <input
              v-model="editForm.title"
              type="text"
              required
              class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-900 focus:outline-none focus:border-blue-500 text-xs shadow-2xs"
            />
          </div>

          <!-- Location & Closing Date -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Lokasi Penempatan</label>
              <input
                v-model="editForm.location"
                type="text"
                class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-900 focus:outline-none focus:border-blue-500 text-xs shadow-2xs"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Tanggal Ditutup (Closing Date)</label>
              <input
                v-model="editForm.closing_date"
                type="date"
                class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-900 focus:outline-none focus:border-blue-500 text-xs shadow-2xs"
              />
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Deskripsi Pekerjaan (Job Description)</label>
            <textarea
              v-model="editForm.description"
              rows="4"
              class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-900 focus:outline-none focus:border-blue-500 text-xs leading-relaxed shadow-2xs"
            ></textarea>
          </div>

          <!-- Requirements -->
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Persyaratan & Kualifikasi (Requirements)</label>
            <textarea
              v-model="editForm.requirements"
              rows="4"
              class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-900 focus:outline-none focus:border-blue-500 text-xs leading-relaxed shadow-2xs"
            ></textarea>
          </div>

          <!-- Status Publish Toggle -->
          <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
            <div>
              <span class="font-bold text-slate-900 block">Status Publikasi</span>
              <span class="text-slate-500 text-[11px]">Jika Published, lowongan akan aktif dan dapat dilamar calon pelamar.</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                v-model="editForm.is_published"
                class="sr-only peer"
              />
              <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
            </label>
          </div>

          <div class="p-4 bg-slate-50 -mx-6 -mb-6 mt-6 border-t border-slate-100 flex items-center justify-end gap-2 rounded-b-2xl">
            <button
              type="button"
              @click="editingJob = null"
              class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold rounded-lg text-xs cursor-pointer transition-colors"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs cursor-pointer shadow-2xs transition-colors"
            >
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  ChevronRight, 
  Search, 
  Users, 
  ArrowRight, 
  CheckCircle2, 
  AlertCircle, 
  Edit3, 
  MapPin,
  Calendar
} from 'lucide-vue-next';
import { useRekrutmenStore } from '../stores/rekrutmen';
import LoadingState from '../components/LoadingState.vue';

const store = useRekrutmenStore();
const postings = computed(() => store.postings);
const searchQuery = ref('');
const toastMessage = ref(null);
const toastType = ref('success');

onMounted(() => {
  store.fetchPostings(searchQuery.value, true);
});

const editingJob = ref(null);
const editForm = ref({
  id: null,
  title: '',
  location: '',
  closing_date: '',
  description: '',
  requirements: '',
  is_published: false,
});

let debounceTimer = null;
const handleSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    store.fetchPostings(searchQuery.value, true);
  }, 200);
};

const showToast = (msg, type = 'success') => {
  toastMessage.value = msg;
  toastType.value = type;
  setTimeout(() => {
    toastMessage.value = null;
  }, 4000);
};

const togglePublish = async (job) => {
  const actionText = job.is_published ? 'mengubah menjadi DRAFT' : 'MEM-PUBLISH (mengaktifkan)';
  if (!confirm(`Apakah Anda yakin ingin ${actionText} lowongan "${job.title}"?`)) {
    return;
  }
  try {
    const res = await store.togglePublishPosting(job.id);
    showToast(res.message, 'success');
  } catch (err) {
    showToast('Gagal mengubah status publish lowongan.', 'error');
  }
};

const openEditModal = (job) => {
  editingJob.value = job;
  editForm.value = {
    id: job.id,
    title: job.title,
    location: job.location || '',
    description: job.description || '',
    requirements: job.requirements || '',
    closing_date: job.closing_date || null,
    is_published: !!job.is_published,
  };
};

const saveEditJob = async () => {
  try {
    const res = await store.updateJobPosting(editingJob.value.id, editForm.value);
    showToast(res.message || 'Lowongan berhasil diperbarui!', 'success');
    editingJob.value = null;
  } catch (err) {
    showToast(err.response?.data?.message || 'Gagal menyimpan lowongan.', 'error');
  }
};

onMounted(() => {
  store.fetchPostings();
});
</script>
