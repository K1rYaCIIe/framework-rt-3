@extends('layouts.app')

@section('content')
<div class="container pb-5">
  <div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
      <div class="border rounded p-2 text-center card-hover">
        <div class="small text-muted">Скорость МКС</div>
        <div class="fs-4">{{ isset(($iss['payload'] ?? [])['velocity']) ? number_format($iss['payload']['velocity'],0,'',' ') : '—' }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="border rounded p-2 text-center card-hover">
        <div class="small text-muted">Высота МКС</div>
        <div class="fs-4">{{ isset(($iss['payload'] ?? [])['altitude']) ? number_format($iss['payload']['altitude'],0,'',' ') : '—' }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="border rounded p-2 text-center card-hover">
        <div class="small text-muted">Широта</div>
        <div class="fs-6">{{ isset(($iss['payload'] ?? [])['latitude']) ? number_format($iss['payload']['latitude'],4) : '—' }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="border rounded p-2 text-center card-hover">
        <div class="small text-muted">Долгота</div>
        <div class="fs-6">{{ isset(($iss['payload'] ?? [])['longitude']) ? number_format($iss['payload']['longitude'],4) : '—' }}</div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card shadow-sm h-100 card-hover">
        <div class="card-body">
          <h5 class="card-title">
            <i class="bi bi-telescope"></i> JWST — выбранное наблюдение
          </h5>
          <div class="text-muted">
            Этот блок остаётся как был (JSON/сводка). Основная галерея ниже.
          </div>
          @if(isset($jw_observation_summary) && !empty($jw_observation_summary))
            <div class="mt-3">
              <pre class="bg-light p-2 rounded small">{{ json_encode($jw_observation_summary, JSON_PRETTY_PRINT) }}</pre>
            </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card shadow-sm h-100 card-hover">
        <div class="card-body">
          <h5 class="card-title">
            <i class="bi bi-globe"></i> МКС — положение и движение
          </h5>
          <div id="map" class="rounded mb-2 border" style="height:300px"></div>
          <div class="row g-2">
            <div class="col-6"><canvas id="issSpeedChart" height="110"></canvas></div>
            <div class="col-6"><canvas id="issAltChart"   height="110"></canvas></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card shadow-sm card-hover">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="card-title m-0">
              <i class="bi bi-images"></i> JWST — последние изображения
            </h5>
            <form id="jwstFilter" class="row g-2 align-items-center">
              <div class="col-auto">
                <select class="form-select form-select-sm" name="source" id="srcSel">
                  <option value="jpg" selected>Все JPG</option>
                  <option value="suffix">По суффиксу</option>
                  <option value="program">По программе</option>
                </select>
              </div>
              <div class="col-auto">
                <input type="text" class="form-control form-control-sm" name="suffix" id="suffixInp" placeholder="_cal / _thumb" style="width:140px;display:none">
                <input type="text" class="form-control form-control-sm" name="program" id="progInp" placeholder="2734" style="width:110px;display:none">
              </div>
              <div class="col-auto">
                <select class="form-select form-select-sm" name="instrument" style="width:130px">
                  <option value="">Любой инструмент</option>
                  <option>NIRCam</option><option>MIRI</option><option>NIRISS</option><option>NIRSpec</option><option>FGS</option>
                </select>
              </div>
              <div class="col-auto">
                <select class="form-select form-select-sm" name="perPage" style="width:90px">
                  <option>12</option><option selected>24</option><option>36</option><option>48</option>
                </select>
              </div>
              <div class="col-auto">
                <button class="btn btn-sm btn-primary" type="submit">Показать</button>
              </div>
            </form>
          </div>

          <div class="jwst-slider">
            <button class="btn btn-light border jwst-nav jwst-prev" type="button" aria-label="Prev">‹</button>
            <div id="jwstTrack" class="jwst-track border rounded"></div>
            <button class="btn btn-light border jwst-nav jwst-next" type="button" aria-label="Next">›</button>
          </div>

          <div id="jwstInfo" class="small text-muted mt-2"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-12">
      <div class="card shadow-sm card-hover">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="card-title m-0">
              <i class="bi bi-stars"></i> Астрономические события (AstronomyAPI)
            </h5>
            <form id="astroForm" class="row g-2 align-items-center">
              <div class="col-auto">
                <input type="number" step="0.0001" class="form-control form-control-sm" name="lat" value="55.7558" placeholder="lat">
              </div>
              <div class="col-auto">
                <input type="number" step="0.0001" class="form-control form-control-sm" name="lon" value="37.6176" placeholder="lon">
              </div>
              <div class="col-auto">
                <input type="number" min="1" max="30" class="form-control form-control-sm" name="days" value="7" style="width:90px" title="дней">
              </div>
              <div class="col-auto">
                <button class="btn btn-sm btn-primary" type="submit">Показать</button>
              </div>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr><th>#</th><th>Тело</th><th>Событие</th><th>Когда (UTC)</th><th>Дополнительно</th></tr>
              </thead>
              <tbody id="astroBody">
                <tr><td colspan="5" class="text-muted">нет данных</td></tr>
              </tbody>
            </table>
          </div>

          <details class="mt-2">
            <summary>Детали запроса</summary>
            <pre id="astroRaw" class="bg-light rounded p-2 small m-0" style="white-space:pre-wrap"></pre>
          </details>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-12">
      <div class="card shadow-sm card-hover">
        <div class="card-header fw-semibold">
          <i class="bi bi-file-text"></i> CMS — Информационный блок
        </div>
        <div class="card-body">
          <div id="cmsContent">
            <div class="text-muted">Загрузка CMS контента...</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- JavaScript остается таким же --}}
<script>
document.addEventListener('DOMContentLoaded', async function () {
  if (typeof L !== 'undefined' && typeof Chart !== 'undefined') {
    const last = @json(($iss['payload'] ?? []));
    let lat0 = Number(last.latitude || 0), lon0 = Number(last.longitude || 0);
    const map = L.map('map', { attributionControl:false }).setView([lat0||0, lon0||0], lat0?3:2);
    L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', { noWrap:true }).addTo(map);
    const trail  = L.polyline([], {weight:3}).addTo(map);
    const marker = L.marker([lat0||0, lon0||0]).addTo(map).bindPopup('МКС');

    const speedChart = new Chart(document.getElementById('issSpeedChart'), {
      type: 'line', data: { labels: [], datasets: [{ label: 'Скорость', data: [] }] },
      options: { responsive: true, scales: { x: { display: false } } }
    });
    const altChart = new Chart(document.getElementById('issAltChart'), {
      type: 'line', data: { labels: [], datasets: [{ label: 'Высота', data: [] }] },
      options: { responsive: true, scales: { x: { display: false } } }
    });

    async function loadTrend() {
      try {
        const r = await fetch('/api/iss/trend?limit=240');
        const js = await r.json();
        const pts = Array.isArray(js.points) ? js.points.map(p => [p.lat, p.lon]) : [];
        if (pts.length) {
          trail.setLatLngs(pts);
          marker.setLatLng(pts[pts.length-1]);
        }
        const t = (js.points||[]).map(p => new Date(p.at).toLocaleTimeString());
        speedChart.data.labels = t;
        speedChart.data.datasets[0].data = (js.points||[]).map(p => p.velocity);
        speedChart.update();
        altChart.data.labels = t;
        altChart.data.datasets[0].data = (js.points||[]).map(p => p.altitude);
        altChart.update();
      } catch(e) {}
    }
    loadTrend();
    setInterval(loadTrend, 15000);
  }

  const track = document.getElementById('jwstTrack');
  const info  = document.getElementById('jwstInfo');
  const form  = document.getElementById('jwstFilter');
  const srcSel = document.getElementById('srcSel');
  const sfxInp = document.getElementById('suffixInp');
  const progInp= document.getElementById('progInp');

  function toggleInputs(){
    sfxInp.style.display  = (srcSel.value==='suffix')  ? '' : 'none';
    progInp.style.display = (srcSel.value==='program') ? '' : 'none';
  }
  srcSel.addEventListener('change', toggleInputs); toggleInputs();

  async function loadFeed(qs){
    track.innerHTML = '<div class="p-3 text-muted">Загрузка…</div>';
    info.textContent= '';
    try{
      const url = '/api/jwst/feed?'+new URLSearchParams(qs).toString();
      const r = await fetch(url);
      const js = await r.json();
      track.innerHTML = '';
      (js.items||[]).forEach(it=>{
        const fig = document.createElement('figure');
        fig.className = 'jwst-item m-0';
        fig.innerHTML = `
          <a href="${it.link||it.url}" target="_blank" rel="noreferrer">
            <img loading="lazy" src="${it.url}" alt="JWST">
          </a>
          <figcaption class="jwst-cap">${(it.caption||'').replaceAll('<','&lt;')}</figcaption>`;
        track.appendChild(fig);
      });
      info.textContent = `Источник: ${js.source} · Показано ${js.count||0}`;
    }catch(e){
      track.innerHTML = '<div class="p-3 text-danger">Ошибка загрузки</div>';
    }
  }

  form.addEventListener('submit', function(ev){
    ev.preventDefault();
    const fd = new FormData(form);
    const q = Object.fromEntries(fd.entries());
    loadFeed(q);
  });

  document.querySelector('.jwst-prev').addEventListener('click', ()=> track.scrollBy({left:-600, behavior:'smooth'}));
  document.querySelector('.jwst-next').addEventListener('click', ()=> track.scrollBy({left: 600, behavior:'smooth'}));

  loadFeed({source:'jpg', perPage:24});

  const astroForm = document.getElementById('astroForm');
  const astroBody = document.getElementById('astroBody');
  const astroRaw  = document.getElementById('astroRaw');

  async function loadAstro(q){
    astroBody.innerHTML = '<tr><td colspan="5" class="text-muted">Загрузка…</td></tr>';
    const url = '/api/astro/events?' + new URLSearchParams(q).toString();
    try{
      const r  = await fetch(url);
      const js = await r.json();
      
      if (js.ok && js.data) {
        astroRaw.textContent = JSON.stringify(js.data, null, 2);
        displayAstroEvents(js.data);
      } else {
        astroBody.innerHTML = '<tr><td colspan="5" class="text-danger">Ошибка: ' + (js.error?.message || 'Неизвестная ошибка') + '</td></tr>';
        astroRaw.textContent = JSON.stringify(js, null, 2);
      }
    }catch(e){
      astroBody.innerHTML = '<tr><td colspan="5" class="text-danger">Ошибка загрузки</td></tr>';
      astroRaw.textContent = e.toString();
    }
  }

  function displayAstroEvents(data) {
    const events = extractEvents(data);
    if (events.length === 0) {
      astroBody.innerHTML = '<tr><td colspan="5" class="text-muted">События не найдены</td></tr>';
      return;
    }
    
    astroBody.innerHTML = events.map((event, i) => `
      <tr>
        <td>${i+1}</td>
        <td>${event.name || '—'}</td>
        <td>${event.type || '—'}</td>
        <td><small>${event.when || '—'}</small></td>
        <td>${event.extra || ''}</td>
      </tr>
    `).join('');
  }

  function extractEvents(data) {
    const events = [];
    
    function traverse(obj) {
      if (!obj || typeof obj !== 'object') return;
      
      if (Array.isArray(obj)) {
        obj.forEach(traverse);
        return;
      }
      
      if (obj.name && (obj.type || obj.eventType)) {
        events.push({
          name: obj.name,
          type: obj.type || obj.eventType || obj.category,
          when: obj.time || obj.date || obj.when || obj.utcTime,
          extra: obj.magnitude || obj.altitude || obj.azimuth || ''
        });
      }
      
      // Рекурсивно обходим все свойства
      Object.values(obj).forEach(traverse);
    }
    
    traverse(data);
    return events.slice(0, 50);
  }

  astroForm.addEventListener('submit', ev=>{
    ev.preventDefault();
    const q = Object.fromEntries(new FormData(astroForm).entries());
    loadAstro(q);
  });

  loadAstro({lat: 55.7558, lon: 37.6176, days: 7});

  async function loadCmsContent() {
    try {
      const response = await fetch('/api/cms/dashboard_experiment');
      const data = await response.json();
      
      if (data.ok && data.content) {
        document.getElementById('cmsContent').innerHTML = data.content;
      } else {
        document.getElementById('cmsContent').innerHTML = 
          '<div class="alert alert-warning">CMS контент временно недоступен</div>';
      }
    } catch (error) {
      document.getElementById('cmsContent').innerHTML = 
        '<div class="alert alert-secondary">Информационный блок CMS</div>';
    }
  }
  
  loadCmsContent();
});
</script>

<style>
.card-hover {
    transition: all 0.3s ease;
}
.card-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.1);
}
.jwst-slider{position:relative}
.jwst-track{
    display:flex; gap:.75rem; overflow:auto; scroll-snap-type:x mandatory; padding:.25rem;
}
.jwst-item{flex:0 0 180px; scroll-snap-align:start}
.jwst-item img{width:100%; height:180px; object-fit:cover; border-radius:.5rem}
.jwst-cap{font-size:.85rem; margin-top:.25rem}
.jwst-nav{position:absolute; top:40%; transform:translateY(-50%); z-index:2}
.jwst-prev{left:-.25rem} .jwst-next{right:-.25rem}
</style>
@endsection
