


CREATE TABLE IF NOT EXISTS iss_fetch_log (
    id BIGSERIAL PRIMARY KEY,
    fetched_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    source_url TEXT NOT NULL,
    payload JSONB NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS telemetry_legacy (
    id BIGSERIAL PRIMARY KEY,
    recorded_at TIMESTAMPTZ NOT NULL,
    voltage NUMERIC(6,2) NOT NULL CHECK (voltage BETWEEN -1000 AND 1000),
    temp NUMERIC(6,2) NOT NULL CHECK (temp BETWEEN -273 AND 1000),
    source_file TEXT NOT NULL,
    is_valid BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);


CREATE TABLE IF NOT EXISTS osdr_items (
    id BIGSERIAL PRIMARY KEY,
    dataset_id TEXT,
    title TEXT,
    status TEXT,
    updated_at TIMESTAMPTZ,
    inserted_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    raw JSONB NOT NULL
);


CREATE TABLE IF NOT EXISTS space_cache (
    id BIGSERIAL PRIMARY KEY,
    source TEXT NOT NULL,
    fetched_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    payload JSONB NOT NULL
);


CREATE TABLE IF NOT EXISTS cms_blocks (
    id BIGSERIAL PRIMARY KEY,
    slug TEXT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);


CREATE INDEX IF NOT EXISTS idx_iss_fetch_log_fetched_at ON iss_fetch_log(fetched_at DESC);
CREATE INDEX IF NOT EXISTS idx_telemetry_recorded_at ON telemetry_legacy(recorded_at DESC);
CREATE INDEX IF NOT EXISTS idx_cms_blocks_slug_active ON cms_blocks(slug, is_active);
CREATE INDEX IF NOT EXISTS idx_space_cache_source ON space_cache(source, fetched_at DESC);


INSERT INTO cms_blocks(slug, title, content, is_active) 
VALUES
('dashboard_experiment', 'Экспериментальный блок', '<div class="alert alert-info"><h4>Экспериментальный блок</h4><p>Этот контент загружается из базы данных CMS</p></div>', true),
('welcome', 'Добро пожаловать', '<h3>Добро пожаловать в Space Dashboard</h3><p>Система мониторинга космических данных в реальном времени</p>', true),
('about', 'О проекте', '<h3>Космический дашборд</h3><p>Проект для сбора и визуализации данных с:</p><ul><li>Международной космической станции (ISS)</li><li>Телескопа Джеймса Уэбба (JWST)</li><li>NASA OSDR</li><li>Астрономических событий</li></ul>', true)
ON CONFLICT (slug) DO UPDATE SET 
    title = EXCLUDED.title,
    content = EXCLUDED.content,
    updated_at = NOW();
