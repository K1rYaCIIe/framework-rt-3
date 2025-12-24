@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="animate__animated animate__fadeInLeft">NASA Open Space Data Repository (OSDR)</h2>
        <p class="text-muted">Browse biological and physical research data</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control bg-dark text-white border-secondary" placeholder="Search by title or ID...">
                </div>
                <div class="col-md-3">
                    <select id="sortCol" class="form-select bg-dark text-white border-secondary">
                        <option value="title">Sort by Title</option>
                        <option value="id">Sort by ID</option>
                        <option value="date" selected>Sort by Date</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="sortDir" class="form-select bg-dark text-white border-secondary">
                        <option value="asc">Ascending</option>
                        <option value="desc" selected>Descending</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="osdrTable">
                    <thead>
                        <tr>
                            <th>Dataset ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody id="osdrBody">
                        <tr><td colspan="4" class="text-center py-5">Loading data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let allItems = [];

    function renderTable(items) {
        const body = document.getElementById('osdrBody');
        const search = document.getElementById('searchInput').value.toLowerCase();
        const sortCol = document.getElementById('sortCol').value;
        const sortDir = document.getElementById('sortDir').value;

        let filtered = items.filter(it => 
            (it.title && it.title.toLowerCase().includes(search)) || 
            (it.dataset_id && it.dataset_id.toLowerCase().includes(search))
        );

        filtered.sort((a, b) => {
            let valA, valB;
            if (sortCol === 'title') { valA = a.title || ''; valB = b.title || ''; }
            else if (sortCol === 'id') { valA = a.dataset_id || ''; valB = b.dataset_id || ''; }
            else { valA = a.updated_at || a.inserted_at; valB = b.updated_at || b.inserted_at; }

            if (valA < valB) return sortDir === 'asc' ? -1 : 1;
            if (valA > valB) return sortDir === 'asc' ? 1 : -1;
            return 0;
        });

        body.innerHTML = filtered.map(it => `
            <tr class="animate__animated animate__fadeIn">
                <td class="text-info">${it.dataset_id || 'N/A'}</td>
                <td>${it.title || 'Untitled'}</td>
                <td><span class="badge bg-secondary">${it.status || 'Unknown'}</span></td>
                <td class="small text-muted">${new Date(it.updated_at || it.inserted_at).toLocaleDateString()}</td>
            </tr>
        `).join('') || '<tr><td colspan="4" class="text-center py-5">No items found</td></tr>';
    }

    async function loadData() {
        try {
            const res = await fetch('/api/osdr/list');
            const data = await res.json();
            allItems = data.items || [];
            renderTable(allItems);
        } catch (e) {
            document.getElementById('osdrBody').innerHTML = '<tr><td colspan="4" class="text-center text-danger py-5">Failed to load data</td></tr>';
        }
    }

    document.getElementById('searchInput').addEventListener('input', () => renderTable(allItems));
    document.getElementById('sortCol').addEventListener('change', () => renderTable(allItems));
    document.getElementById('sortDir').addEventListener('change', () => renderTable(allItems));

    loadData();
</script>
@endsection
