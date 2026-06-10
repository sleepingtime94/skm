/* ============================================================
   Dashboard Controller — /assets/js/dashboard.js
   ============================================================ */

/* ── State ───────────────────────────────────────────────── */
let empAll = [], ratingAll = [], ratingStats = [];
let empPage = 1, ratPage = 1;
const PER_PAGE = 10;

/* ── Init ────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  // Sidebar nav
  document.querySelectorAll('.dash-sidebar .nav-item[data-page]').forEach(el => {
    el.addEventListener('click', e => {
      e.preventDefault();
      switchPage(el.dataset.page);
    });
  });

  // Mobile sidebar toggle
  const toggle = document.getElementById('sidebar-toggle');
  const sidebar = document.getElementById('dash-sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  if (toggle) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
    });
  }
  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    });
  }

  // Load initial page
  switchPage('overview');
});

/* ── Page Router ─────────────────────────────────────────── */
function switchPage(page) {
  // Update sidebar active
  document.querySelectorAll('.dash-sidebar .nav-item').forEach(n => n.classList.remove('active'));
  const active = document.querySelector(`.dash-sidebar .nav-item[data-page="${page}"]`);
  if (active) active.classList.add('active');

  // Update page title
  const titles = {
    overview: 'Dashboard',
    employees: 'Kelola Pegawai',
    ratings: 'Penilaian Pegawai',
    skm: 'Survei Kepuasan Masyarakat',
    zi: 'Survei Zona Integritas',
  };
  document.getElementById('page-title').textContent = titles[page] || 'Dashboard';

  // Show/hide pages
  document.querySelectorAll('.dash-page').forEach(p => p.classList.remove('active'));
  const target = document.getElementById(`page-${page}`);
  if (target) target.classList.add('active');

  // Load data
  if (page === 'overview') loadOverview();
  if (page === 'employees') loadEmployees();
  if (page === 'ratings') loadRatings();
  if (page === 'skm') loadSKM();
  if (page === 'zi') loadZI();

  // Close mobile sidebar
  document.getElementById('dash-sidebar')?.classList.remove('show');
  document.getElementById('sidebar-overlay')?.classList.remove('show');
}

/* ═══════════════════════════════════════════════════════════
   OVERVIEW PAGE
   ═══════════════════════════════════════════════════════════ */
async function loadOverview() {
  try {
    const [empRes, ratRes, statsRes] = await Promise.all([
      fetch('/api/employee/list'), fetch('/api/rating/list'), fetch('/api/rating/stats'),
    ]);
    const employees = await empRes.json();
    const ratings   = await ratRes.json();
    const stats     = await statsRes.json();

    document.getElementById('ov-total-emp').textContent = employees.length;
    document.getElementById('ov-total-rat').textContent = ratings.length;
    const bagus = ratings.filter(r => String(r.rate_value) === '5').length;
    const buruk = ratings.filter(r => String(r.rate_value) === '1').length;
    document.getElementById('ov-bagus').textContent = bagus;
    document.getElementById('ov-buruk').textContent = buruk;

    // Top employees
    const topEl = document.getElementById('ov-top-employees');
    const sorted = stats.filter(s => parseInt(s.total_ratings) > 0).sort((a,b) => parseFloat(b.avg_value) - parseFloat(a.avg_value)).slice(0, 5);
    if (!sorted.length) {
      topEl.innerHTML = '<div class="text-muted small text-center py-3">Belum ada penilaian.</div>';
    } else {
      topEl.innerHTML = sorted.map((e, i) => {
        const avg = parseFloat(e.avg_value);
        const color = avg >= 4 ? 'text-success' : avg >= 2.5 ? 'text-primary' : 'text-danger';
        return `<div class="d-flex align-items-center justify-content-between py-2 ${i < sorted.length - 1 ? 'border-bottom' : ''}">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark fw-bold" style="width:28px;">${i + 1}</span>
            <div>
              <div class="fw-semibold text-dark small">${e.employee_name}</div>
              <div class="text-muted" style="font-size:.7rem;">${e.employee_job ?? ''} &bull; ${e.total_ratings} penilaian</div>
            </div>
          </div>
          <span class="fw-bold ${color}">${avg.toFixed(1)} <i class="bi bi-star-fill" style="font-size:.75rem;"></i></span>
        </div>`;
      }).join('');
    }

    // Recent ratings
    const recentEl = document.getElementById('ov-recent-ratings');
    const recent = ratings.slice(0, 6);
    if (!recent.length) {
      recentEl.innerHTML = '<div class="text-muted small text-center py-3">Belum ada data.</div>';
    } else {
      recentEl.innerHTML = recent.map(r => `<div class="d-flex align-items-center justify-content-between py-2 border-bottom">
        <div>
          <div class="fw-semibold text-dark small">${r.employee_name ?? 'Tidak diketahui'}</div>
          <div class="text-muted" style="font-size:.7rem;">${fmtDate(r.rate_created)}</div>
        </div>
        ${valueBadge(r.rate_value)}
      </div>`).join('');
    }
  } catch (e) {
    console.error('loadOverview error', e);
  }
}

/* ═══════════════════════════════════════════════════════════
   EMPLOYEES PAGE
   ═══════════════════════════════════════════════════════════ */
async function loadEmployees() {
  try {
    const res = await fetch('/api/employee/list');
    empAll = await res.json();
    empPage = 1;
    renderEmployees();
  } catch (e) { console.error(e); }
}

function renderEmployees() {
  const q = (document.getElementById('emp-search')?.value ?? '').toLowerCase();
  const jf = document.getElementById('emp-filter-job')?.value ?? '';
  const filtered = empAll.filter(e => {
    const nameMatch = !q || (e.employee_name ?? '').toLowerCase().includes(q);
    const jobMatch = !jf || e.employee_job === jf;
    return nameMatch && jobMatch;
  });

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PER_PAGE));
  empPage = Math.min(empPage, pages);
  const slice = filtered.slice((empPage - 1) * PER_PAGE, empPage * PER_PAGE);

  const tbody = document.getElementById('emp-tbody');
  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data.</td></tr>';
  } else {
    tbody.innerHTML = slice.map(e => `<tr>
      <td class="ps-3 fw-semibold">${e.employee_name ?? '—'}</td>
      <td class="text-muted" style="font-size:.78rem;">${e.employee_nip ?? '—'}</td>
      <td>${e.employee_position ?? '—'}</td>
      <td class="text-center">${jobBadge(e.employee_job)}</td>
      <td class="text-muted" style="font-size:.78rem;">${e.employee_about ?? '—'}</td>
      <td class="text-center pe-3">
        <div class="d-inline-flex gap-1">
          <button class="btn btn-sm btn-outline-primary px-2 py-1" title="Edit" onclick="editEmployee(${e.employee_id})">
            <i class="bi bi-pencil-square"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger px-2 py-1" title="Hapus" onclick="deleteEmployee(${e.employee_id},'${(e.employee_name??'').replace(/'/g,"\\'")}')">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </td>
    </tr>`).join('');
  }

  document.getElementById('emp-page-info').textContent =
    total ? `${(empPage-1)*PER_PAGE+1}–${Math.min(empPage*PER_PAGE,total)} dari ${total}` : '';
  const ul = document.getElementById('emp-pagination');
  ul.innerHTML = '';
  for (let p = 1; p <= pages; p++) {
    ul.insertAdjacentHTML('beforeend', `<li class="page-item ${p===empPage?'active':''}">
      <button class="page-link" onclick="empPage=${p};renderEmployees()">${p}</button></li>`);
  }
}

// Add / Edit Employee Modal
function showAddEmployee() {
  document.getElementById('emp-modal-title').textContent = 'Tambah Pegawai';
  document.getElementById('emp-form').reset();
  document.getElementById('emp-form-id').value = '';
  new bootstrap.Modal(document.getElementById('empModal')).show();
}

function editEmployee(id) {
  const emp = empAll.find(e => e.employee_id == id);
  if (!emp) return;
  document.getElementById('emp-modal-title').textContent = 'Edit Pegawai';
  document.getElementById('emp-form-id').value = emp.employee_id;
  document.getElementById('emp-f-name').value = emp.employee_name ?? '';
  document.getElementById('emp-f-nip').value = emp.employee_nip ?? '';
  document.getElementById('emp-f-nik').value = emp.employee_nik ?? '';
  document.getElementById('emp-f-position').value = emp.employee_position ?? '';
  document.getElementById('emp-f-job').value = emp.employee_job ?? '';
  document.getElementById('emp-f-about').value = emp.employee_about ?? '';
  document.getElementById('emp-f-ttl').value = emp.employee_ttl ?? '';
  new bootstrap.Modal(document.getElementById('empModal')).show();
}

async function saveEmployee() {
  const id = document.getElementById('emp-form-id').value;
  const data = {
    employee_name:     document.getElementById('emp-f-name').value,
    employee_nip:      document.getElementById('emp-f-nip').value || null,
    employee_nik:      document.getElementById('emp-f-nik').value || null,
    employee_position: document.getElementById('emp-f-position').value || null,
    employee_job:      document.getElementById('emp-f-job').value || null,
    employee_about:    document.getElementById('emp-f-about').value || null,
    employee_ttl:      document.getElementById('emp-f-ttl').value || null,
  };

  if (!data.employee_name) {
    Swal.fire({ icon:'warning', title:'Nama pegawai wajib diisi.' });
    return;
  }

  try {
    const url = id ? `/api/employee/update/${id}` : '/api/employee/create';
    const method = id ? 'PATCH' : 'POST';
    const res = await fetch(url, { method, headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const json = await res.json();
    bootstrap.Modal.getInstance(document.getElementById('empModal'))?.hide();
    if (json.status === 'success') {
      Swal.fire({ icon:'success', title:'Berhasil!', text: json.message, timer:1800, showConfirmButton:false });
      loadEmployees();
    } else {
      Swal.fire({ icon:'error', title:'Gagal', text: json.message });
    }
  } catch (e) {
    Swal.fire({ icon:'error', title:'Error', text:'Terjadi kesalahan jaringan.' });
  }
}

async function deleteEmployee(id, name) {
  const { isConfirmed } = await Swal.fire({
    title:`Hapus ${name}?`, text:'Data pegawai dan ratingnya akan terhapus.',
    icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus!', cancelButtonText:'Batal', confirmButtonColor:'#ef4444',
  });
  if (!isConfirmed) return;
  try {
    const res = await fetch(`/api/employee/delete/${id}`, { method:'DELETE' });
    const json = await res.json();
    if (json.status === 'success') {
      Swal.fire({ icon:'success', title:'Terhapus!', text: json.message, timer:1800, showConfirmButton:false });
      loadEmployees();
    } else {
      Swal.fire({ icon:'error', title:'Gagal', text: json.message });
    }
  } catch(e) {
    Swal.fire({ icon:'error', title:'Error', text:'Terjadi kesalahan jaringan.' });
  }
}

/* ═══════════════════════════════════════════════════════════
   RATINGS PAGE
   ═══════════════════════════════════════════════════════════ */
async function loadRatings() {
  try {
    const [rRes, sRes] = await Promise.all([
      fetch('/api/rating/list'), fetch('/api/rating/stats'),
    ]);
    ratingAll = await rRes.json();
    ratingStats = await sRes.json();
    ratPage = 1;
    renderRatingSummary();
    renderRatingEmployeeStats();
    renderRatingTable();
  } catch(e) { console.error(e); }
}

function renderRatingSummary() {
  const total   = ratingAll.length;
  const bagus   = ratingAll.filter(r => String(r.rate_value) === '5').length;
  const lumayan = ratingAll.filter(r => String(r.rate_value) === '3').length;
  const buruk   = ratingAll.filter(r => String(r.rate_value) === '1').length;
  document.getElementById('rat-total').textContent   = total;
  document.getElementById('rat-bagus').textContent   = bagus;
  document.getElementById('rat-lumayan').textContent = lumayan;
  document.getElementById('rat-buruk').textContent   = buruk;
}

function renderRatingEmployeeStats() {
  const el = document.getElementById('rat-employee-stats');
  if (!ratingStats.length) {
    el.innerHTML = '<div class="text-muted small text-center py-3">Belum ada data.</div>';
    return;
  }
  el.innerHTML = ratingStats.map(emp => {
    const total   = parseInt(emp.total_ratings) || 0;
    const bagus   = parseInt(emp.bagus) || 0;
    const lumayan = parseInt(emp.lumayan) || 0;
    const buruk   = parseInt(emp.buruk) || 0;
    const avg     = parseFloat(emp.avg_value) || 0;
    const pB = total ? Math.round(bagus/total*100) : 0;
    const pL = total ? Math.round(lumayan/total*100) : 0;
    const pR = total ? Math.round(buruk/total*100) : 0;
    const sc = avg >= 4 ? 'text-success' : avg >= 2.5 ? 'text-primary' : 'text-danger';
    return `<div class="border rounded-3 p-3 mb-2">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div><div class="fw-semibold small">${emp.employee_name??'—'}</div>
          <div class="text-muted" style="font-size:.7rem;">${emp.employee_job??''} &bull; ${total} penilaian</div></div>
        <span class="fw-bold ${sc}">${avg.toFixed(1)} <i class="bi bi-star-fill" style="font-size:.75rem;"></i></span>
      </div>
      <div class="rating-bar"><div style="width:${pB}%;background:#10b981;"></div><div style="width:${pL}%;background:#3b82f6;"></div><div style="width:${pR}%;background:#ef4444;"></div></div>
      <div class="d-flex gap-3 mt-1" style="font-size:.68rem;">
        <span class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:.4rem;"></i>${bagus} Bagus</span>
        <span class="text-primary"><i class="bi bi-circle-fill me-1" style="font-size:.4rem;"></i>${lumayan} Lumayan</span>
        <span class="text-danger"><i class="bi bi-circle-fill me-1" style="font-size:.4rem;"></i>${buruk} Buruk</span>
      </div>
    </div>`;
  }).join('');
}

function renderRatingTable() {
  const q  = (document.getElementById('rat-search')?.value ?? '').toLowerCase();
  const fv = document.getElementById('rat-filter-val')?.value ?? '';
  const filtered = ratingAll.filter(r => {
    const nameMatch = !q || (r.employee_name ?? '').toLowerCase().includes(q);
    const valMatch  = !fv || String(r.rate_value) === fv;
    return nameMatch && valMatch;
  });
  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PER_PAGE));
  ratPage = Math.min(ratPage, pages);
  const slice = filtered.slice((ratPage-1)*PER_PAGE, ratPage*PER_PAGE);
  const tbody = document.getElementById('rat-tbody');
  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data.</td></tr>';
  } else {
    tbody.innerHTML = slice.map(r => `<tr>
      <td class="ps-3 text-muted" style="white-space:nowrap;font-size:.78rem;">${fmtDate(r.rate_created)}</td>
      <td class="fw-semibold">${r.employee_name??'<em class="text-danger">—</em>'}</td>
      <td class="text-muted">${r.employee_job??'—'}</td>
      <td class="text-center">${valueBadge(r.rate_value)}</td>
      <td class="text-center pe-3">
        <button class="btn btn-sm btn-outline-danger px-2 py-1" title="Hapus" onclick="ratDelete(${r.rate_id})"><i class="bi bi-trash"></i></button>
      </td>
    </tr>`).join('');
  }
  document.getElementById('rat-page-info').textContent =
    total ? `${(ratPage-1)*PER_PAGE+1}–${Math.min(ratPage*PER_PAGE,total)} dari ${total}` : '';
  const ul = document.getElementById('rat-pagination');
  ul.innerHTML = '';
  for (let p=1; p<=pages; p++) {
    ul.insertAdjacentHTML('beforeend', `<li class="page-item ${p===ratPage?'active':''}">
      <button class="page-link" onclick="ratPage=${p};renderRatingTable()">${p}</button></li>`);
  }
}


async function ratDelete(id) {
  const { isConfirmed } = await Swal.fire({
    title:'Hapus penilaian ini?', text:'Data tidak dapat dikembalikan.',
    icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus!', cancelButtonText:'Batal', confirmButtonColor:'#ef4444',
  });
  if (!isConfirmed) return;
  try {
    const res = await fetch('/api/rating/delete', { method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({rate_id:id}) });
    const json = await res.json();
    if (json.status === 'success') {
      ratingAll = ratingAll.filter(r => r.rate_id != id);
      renderRatingSummary();
      renderRatingTable();
      Swal.fire({ icon:'success', title:'Terhapus!', text:json.message, timer:1500, showConfirmButton:false });
    } else Swal.fire({ icon:'error', title:'Gagal', text:json.message });
  } catch(e) { Swal.fire({ icon:'error', title:'Error', text:'Kesalahan jaringan.' }); }
}

/* ═══════════════════════════════════════════════════════════
   SKM / ZI SURVEY PAGES
   ═══════════════════════════════════════════════════════════ */
async function loadSKM() {
  const m = document.getElementById('skm-month')?.value ?? '';
  const y = document.getElementById('skm-year')?.value ?? '';
  try {
    const res = await fetch(`/api/survey/skm?month=${m}&year=${y}`);
    const data = await res.json();
    const tbody = document.getElementById('skm-tbody');
    if (!data.length) {
      tbody.innerHTML = '<tr><td colspan="16" class="text-center py-4 text-muted">Tidak ada data.</td></tr>';
    } else {
      tbody.innerHTML = data.map(d => {
        let qs = '';
        for (let i=1; i<=9; i++) qs += `<td class="text-center">${d['quest_'+i]??''}</td>`;
        return `<tr>
          <td style="white-space:nowrap;font-size:.78rem;">${d.created_at??''}</td>
          <td>${d.name??''}</td><td>${d.phone??''}</td><td>${d.age??''}</td>
          <td>${d.study??''}</td><td>${d.job??''}</td><td>${d.product??''}</td>${qs}
        </tr>`;
      }).join('');
    }
  } catch(e) { console.error(e); }
}

async function loadZI() {
  const m = document.getElementById('zi-month')?.value ?? '';
  const y = document.getElementById('zi-year')?.value ?? '';
  try {
    const res = await fetch(`/api/survey/zi?month=${m}&year=${y}`);
    const data = await res.json();
    const tbody = document.getElementById('zi-tbody');
    if (!data.length) {
      tbody.innerHTML = '<tr><td colspan="22" class="text-center py-4 text-muted">Tidak ada data.</td></tr>';
    } else {
      tbody.innerHTML = data.map(d => {
        let qs = '';
        for (let i=1; i<=13; i++) qs += `<td class="text-center">${d['quest_'+i]??''}</td>`;
        return `<tr>
          <td style="white-space:nowrap;font-size:.78rem;">${d.created_at??''}</td>
          <td>${d.name??''}</td><td>${d.contact??''}</td><td>${d.age??''}</td>
          <td>${d.study??''}</td><td>${d.job??''}</td><td>${d.suggest??''}</td><td>${d.product??''}</td>${qs}
        </tr>`;
      }).join('');
    }
  } catch(e) { console.error(e); }
}

/* ── Shared Helpers ──────────────────────────────────────── */
function fmtDate(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  return d.toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
}

function valueBadge(val) {
  const m = {
    '5': '<span class="badge rounded-pill bg-success-subtle text-success"><i class="bi bi-hand-thumbs-up-fill me-1"></i>Bagus</span>',
    '3': '<span class="badge rounded-pill bg-primary-subtle text-primary"><i class="bi bi-hand-thumbs-up me-1"></i>Lumayan</span>',
    '1': '<span class="badge rounded-pill bg-danger-subtle text-danger"><i class="bi bi-hand-thumbs-down-fill me-1"></i>Buruk</span>',
  };
  return m[String(val)] ?? `<span class="badge bg-secondary">${val}</span>`;
}


function jobBadge(job) {
  const m = {
    FO:   '<span class="badge badge-job bg-primary-subtle text-primary">Front Office</span>',
    FD:   '<span class="badge badge-job bg-success-subtle text-success">Front Desk</span>',
    OPR:  '<span class="badge badge-job bg-info-subtle text-info">Operator</span>',
    NONE: '<span class="badge badge-job bg-secondary-subtle text-secondary">Tidak Ada</span>',
  };
  return m[job] ?? '<span class="badge badge-job bg-secondary-subtle text-secondary">—</span>';
}
