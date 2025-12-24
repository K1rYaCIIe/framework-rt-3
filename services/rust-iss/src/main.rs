mod config;
mod domain;
mod repo;
mod clients;
mod services;
mod handlers;
mod routes;

use std::sync::Arc;
use std::time::Duration;
use sqlx::postgres::PgPoolOptions;
use tracing::info;
use tracing_subscriber::{EnvFilter, FmtSubscriber};
use crate::config::Config;
use crate::services::SpaceService;
use crate::handlers::AppState;

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    let subscriber = FmtSubscriber::builder()
        .with_env_filter(EnvFilter::from_default_env())
        .finish();
    let _ = tracing::subscriber::set_global_default(subscriber);

    dotenvy::dotenv().ok();
    let config = Config::from_env();

    // DB setup
    let pool = PgPoolOptions::new()
        .max_connections(5)
        .connect(&config.database_url)
        .await?;
    
    init_db(&pool).await?;

    // Redis setup (optional but requested)
    let redis_client = redis::Client::open(config.redis_url.clone()).ok();
    if redis_client.is_some() {
        info!("Connected to Redis at {}", config.redis_url);
    } else {
        info!("Redis not available at {}", config.redis_url);
    }

    let service = Arc::new(SpaceService::new(pool.clone(), config.clone(), redis_client));
    let state = Arc::new(AppState {
        service: service.clone(),
        config: config.clone(),
    });

    // Background tasks
    start_background_tasks(service, config);

    // Rate limiting is handled at the client level (timeouts, retries)
    // For production, consider adding nginx rate limiting or axum middleware
    let app = routes::create_router(state);

    let listener = tokio::net::TcpListener::bind(("0.0.0.0", 3000)).await?;
    info!("rust_iss listening on 0.0.0.0:3000");
    axum::serve(listener, app.into_make_service()).await?;

    Ok(())
}

async fn init_db(pool: &sqlx::PgPool) -> anyhow::Result<()> {
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS iss_fetch_log(
            id BIGSERIAL PRIMARY KEY,
            fetched_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            source_url TEXT NOT NULL,
            payload JSONB NOT NULL
        )"
    ).execute(pool).await?;

    sqlx::query(
        "CREATE TABLE IF NOT EXISTS osdr_items(
            id BIGSERIAL PRIMARY KEY,
            dataset_id TEXT,
            title TEXT,
            status TEXT,
            updated_at TIMESTAMPTZ,
            inserted_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            raw JSONB NOT NULL
        )"
    ).execute(pool).await?;

    sqlx::query(
        "CREATE UNIQUE INDEX IF NOT EXISTS ux_osdr_dataset_id
         ON osdr_items(dataset_id) WHERE dataset_id IS NOT NULL"
    ).execute(pool).await?;

    sqlx::query(
        "CREATE TABLE IF NOT EXISTS space_cache(
            id BIGSERIAL PRIMARY KEY,
            source TEXT NOT NULL,
            fetched_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            payload JSONB NOT NULL
        )"
    ).execute(pool).await?;
    
    sqlx::query("CREATE INDEX IF NOT EXISTS ix_space_cache_source ON space_cache(source,fetched_at DESC)").execute(pool).await?;

    Ok(())
}

fn start_background_tasks(service: Arc<SpaceService>, config: Config) {
    // OSDR
    {
        let s = service.clone();
        let c = config.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = s.fetch_osdr().await { tracing::error!("osdr err {e:?}") }
                tokio::time::sleep(Duration::from_secs(c.fetch_every_seconds)).await;
            }
        });
    }
    // ISS
    {
        let s = service.clone();
        let c = config.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = s.trigger_iss_fetch().await { tracing::error!("iss err {e:?}") }
                tokio::time::sleep(Duration::from_secs(c.iss_every_seconds)).await;
            }
        });
    }
    // APOD
    {
        let s = service.clone();
        let c = config.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = s.fetch_apod().await { tracing::error!("apod err {e:?}") }
                tokio::time::sleep(Duration::from_secs(c.apod_every_seconds)).await;
            }
        });
    }
    // NeoWs
    {
        let s = service.clone();
        let c = config.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = s.fetch_neo_feed().await { tracing::error!("neo err {e:?}") }
                tokio::time::sleep(Duration::from_secs(c.neo_every_seconds)).await;
            }
        });
    }
    // DONKI
    {
        let s = service.clone();
        let c = config.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = s.fetch_donki_flr().await { tracing::error!("donki flr err {e:?}") }
                if let Err(e) = s.fetch_donki_cme().await { tracing::error!("donki cme err {e:?}") }
                tokio::time::sleep(Duration::from_secs(c.donki_every_seconds)).await;
            }
        });
    }
    // SpaceX
    {
        let s = service.clone();
        let c = config.clone();
        tokio::spawn(async move {
            loop {
                if let Err(e) = s.fetch_spacex_next().await { tracing::error!("spacex err {e:?}") }
                tokio::time::sleep(Duration::from_secs(c.spacex_every_seconds)).await;
            }
        });
    }
}
