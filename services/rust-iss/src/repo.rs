use sqlx::{PgPool, Row};
use serde_json::Value;
use chrono::{DateTime, Utc};
use crate::domain::{OsdrItem};
use anyhow::Result;

pub struct IssRepo;
impl IssRepo {
    pub async fn get_last(pool: &PgPool) -> Result<Option<(i64, DateTime<Utc>, String, Value)>> {
        let row = sqlx::query(
            "SELECT id, fetched_at, source_url, payload
             FROM iss_fetch_log
             ORDER BY id DESC LIMIT 1"
        ).fetch_optional(pool).await?;

        if let Some(row) = row {
            Ok(Some((
                row.get("id"),
                row.get("fetched_at"),
                row.get("source_url"),
                row.get("payload"),
            )))
        } else {
            Ok(None)
        }
    }

    pub async fn get_last_two(pool: &PgPool) -> Result<Vec<(DateTime<Utc>, Value)>> {
        let rows = sqlx::query("SELECT fetched_at, payload FROM iss_fetch_log ORDER BY id DESC LIMIT 2")
            .fetch_all(pool).await?;
        
        Ok(rows.into_iter().map(|r| (r.get("fetched_at"), r.get("payload"))).collect())
    }

    pub async fn save(pool: &PgPool, url: &str, payload: Value) -> Result<()> {
        sqlx::query("INSERT INTO iss_fetch_log (source_url, payload) VALUES ($1, $2)")
            .bind(url).bind(payload).execute(pool).await?;
        Ok(())
    }
}

pub struct OsdrRepo;
impl OsdrRepo {
    pub async fn list(pool: &PgPool, limit: i64) -> Result<Vec<OsdrItem>> {
        let rows = sqlx::query(
            "SELECT id, dataset_id, title, status, updated_at, inserted_at, raw
             FROM osdr_items
             ORDER BY inserted_at DESC
             LIMIT $1"
        ).bind(limit).fetch_all(pool).await?;

        Ok(rows.into_iter().map(|r| OsdrItem {
            id: r.get("id"),
            dataset_id: r.get("dataset_id"),
            title: r.get("title"),
            status: r.get("status"),
            updated_at: r.get("updated_at"),
            inserted_at: r.get("inserted_at"),
            raw: r.get("raw"),
        }).collect())
    }

    pub async fn upsert(pool: &PgPool, item: Value, ds_id: Option<String>, title: Option<String>, status: Option<String>, updated: Option<DateTime<Utc>>) -> Result<()> {
        if let Some(ds) = ds_id {
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1,$2,$3,$4,$5)
                 ON CONFLICT (dataset_id) DO UPDATE
                 SET title=EXCLUDED.title, status=EXCLUDED.status,
                     updated_at=EXCLUDED.updated_at, raw=EXCLUDED.raw"
            ).bind(ds).bind(title).bind(status).bind(updated).bind(item).execute(pool).await?;
        } else {
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1,$2,$3,$4,$5)"
            ).bind::<Option<String>>(None).bind(title).bind(status).bind(updated).bind(item).execute(pool).await?;
        }
        Ok(())
    }
}

pub struct CacheRepo;
impl CacheRepo {
    pub async fn get_latest(pool: &PgPool, source: &str) -> Result<Option<(DateTime<Utc>, Value)>> {
        let row = sqlx::query(
            "SELECT fetched_at, payload FROM space_cache
             WHERE source = $1 ORDER BY id DESC LIMIT 1"
        ).bind(source).fetch_optional(pool).await?;

        Ok(row.map(|r| (r.get("fetched_at"), r.get("payload"))))
    }

    pub async fn save(pool: &PgPool, source: &str, payload: Value) -> Result<()> {
        sqlx::query("INSERT INTO space_cache(source, payload) VALUES ($1,$2)")
            .bind(source).bind(payload).execute(pool).await?;
        Ok(())
    }

    pub async fn get_count(pool: &PgPool, table: &str) -> Result<i64> {
        let query = format!("SELECT count(*) AS c FROM {}", table);
        let row = sqlx::query(&query).fetch_one(pool).await?;
        Ok(row.get::<i64, _>("c"))
    }
}

