use std::collections::HashMap;
use crate::repo::{IssRepo, OsdrRepo, CacheRepo};
use crate::clients::ApiClient;
use crate::config::Config;
use crate::domain::Trend;
use sqlx::PgPool;
use serde_json::{Value, json};
use chrono::{DateTime, Utc, NaiveDateTime, TimeZone};
use anyhow::Result;

pub struct SpaceService {
    pool: PgPool,
    client: ApiClient,
    config: Config,
    redis: Option<redis::Client>,
}

impl SpaceService {
    pub fn new(pool: PgPool, config: Config, redis: Option<redis::Client>) -> Self {
        Self {
            pool,
            client: ApiClient::new(),
            config,
            redis,
        }
    }

    pub async fn get_last_iss(&self) -> Result<Value> {
        let last = IssRepo::get_last(&self.pool).await?;
        if let Some((id, fetched_at, source_url, payload)) = last {
            Ok(json!({
                "id": id, "fetched_at": fetched_at, "source_url": source_url, "payload": payload
            }))
        } else {
            Ok(json!({"message":"no data"}))
        }
    }

    pub async fn trigger_iss_fetch(&self) -> Result<()> {
        let json = self.client.get_json(&self.config.where_iss_url, &[]).await?;
        IssRepo::save(&self.pool, &self.config.where_iss_url, json).await
    }

    pub async fn get_iss_trend(&self) -> Result<Trend> {
        let rows = IssRepo::get_last_two(&self.pool).await?;
        if rows.len() < 2 {
            return Ok(Trend {
                movement: false, delta_km: 0.0, dt_sec: 0.0, velocity_kmh: None,
                from_time: None, to_time: None,
                from_lat: None, from_lon: None, to_lat: None, to_lon: None
            });
        }

        let (t2, p2) = &rows[0];
        let (t1, p1) = &rows[1];

        let lat1 = self.num(&p1["latitude"]);
        let lon1 = self.num(&p1["longitude"]);
        let lat2 = self.num(&p2["latitude"]);
        let lon2 = self.num(&p2["longitude"]);
        let v2 = self.num(&p2["velocity"]);

        let mut delta_km = 0.0;
        let mut movement = false;
        if let (Some(a1), Some(o1), Some(a2), Some(o2)) = (lat1, lon1, lat2, lon2) {
            delta_km = self.haversine_km(a1, o1, a2, o2);
            movement = delta_km > 0.1;
        }
        let dt_sec = (*t2 - *t1).num_milliseconds() as f64 / 1000.0;

        Ok(Trend {
            movement,
            delta_km,
            dt_sec,
            velocity_kmh: v2,
            from_time: Some(*t1),
            to_time: Some(*t2),
            from_lat: lat1, from_lon: lon1, to_lat: lat2, to_lon: lon2,
        })
    }

    pub async fn fetch_osdr(&self) -> Result<usize> {
        let json = self.client.get_json(&self.config.nasa_api_url, &[]).await?;
        let items = if let Some(a) = json.as_array() { a.clone() }
            else if let Some(v) = json.get("items").and_then(|x| x.as_array()) { v.clone() }
            else if let Some(v) = json.get("results").and_then(|x| x.as_array()) { v.clone() }
            else { vec![json.clone()] };

        let mut written = 0usize;
        for item in items {
            let id = self.s_pick(&item, &["dataset_id","id","uuid","studyId","accession","osdr_id"]);
            let title = self.s_pick(&item, &["title","name","label"]);
            let status = self.s_pick(&item, &["status","state","lifecycle"]);
            let updated = self.t_pick(&item, &["updated","updated_at","modified","lastUpdated","timestamp"]);
            OsdrRepo::upsert(&self.pool, item, id, title, status, updated).await?;
            written += 1;
        }
        Ok(written)
    }

    pub async fn list_osdr(&self, limit: i64) -> Result<Value> {
        let items = OsdrRepo::list(&self.pool, limit).await?;
        Ok(json!({ "items": items }))
    }

    pub async fn get_space_latest(&self, src: &str) -> Result<Value> {
        let last = CacheRepo::get_latest(&self.pool, src).await?;
        if let Some((fetched_at, payload)) = last {
            Ok(json!({ "source": src, "fetched_at": fetched_at, "payload": payload }))
        } else {
            Ok(json!({ "source": src, "message":"no data" }))
        }
    }

    pub async fn refresh_cache(&self, src_list: &str) -> Result<Vec<&'static str>> {
        let mut done = Vec::new();
        for s in src_list.split(',').map(|x| x.trim().to_lowercase()) {
            match s.as_str() {
                "apod"   => { self.fetch_apod().await?;       done.push("apod"); }
                "neo"    => { self.fetch_neo_feed().await?;   done.push("neo"); }
                "flr"    => { self.fetch_donki_flr().await?;  done.push("flr"); }
                "cme"    => { self.fetch_donki_cme().await?;  done.push("cme"); }
                "spacex" => { self.fetch_spacex_next().await?; done.push("spacex"); }
                _ => {}
            }
        }
        Ok(done)
    }

    pub async fn get_summary(&self) -> Result<Value> {
        if let Some(ref client) = self.redis {
            if let Ok(mut conn) = client.get_tokio_connection().await {
                use redis::AsyncCommands;
                let cached: Option<String> = conn.get("space_summary_cache").await.ok();
                if let Some(s) = cached {
                    if let Ok(v) = serde_json::from_str(&s) {
                        return Ok(v);
                    }
                }
            }
        }

        let apod   = self.latest_from_cache("apod").await;
        let neo    = self.latest_from_cache("neo").await;
        let flr    = self.latest_from_cache("flr").await;
        let cme    = self.latest_from_cache("cme").await;
        let spacex = self.latest_from_cache("spacex").await;

        let iss_last = self.get_last_iss().await?;
        let osdr_count = CacheRepo::get_count(&self.pool, "osdr_items").await.unwrap_or(0);

        let res = json!({
            "apod": apod, "neo": neo, "flr": flr, "cme": cme, "spacex": spacex,
            "iss": iss_last, "osdr_count": osdr_count
        });

        if let Some(ref client) = self.redis {
            if let Ok(mut conn) = client.get_tokio_connection().await {
                use redis::AsyncCommands;
                let _: Result<(), _> = conn.set_ex("space_summary_cache", res.to_string(), 60).await;
            }
        }

        Ok(res)
    }

    // Internal fetchers
    pub async fn fetch_apod(&self) -> Result<()> {
        let mut query = vec![("thumbs", "true".to_string())];
        if !self.config.nasa_api_key.is_empty() {
            query.push(("api_key", self.config.nasa_api_key.clone()));
        }
        let json = self.client.get_json("https://api.nasa.gov/planetary/apod", &query).await?;
        CacheRepo::save(&self.pool, "apod", json).await
    }

    pub async fn fetch_neo_feed(&self) -> Result<()> {
        let today = Utc::now().date_naive();
        let start = today - chrono::Days::new(2);
        let mut query = vec![
            ("start_date", start.to_string()),
            ("end_date", today.to_string()),
        ];
        if !self.config.nasa_api_key.is_empty() {
            query.push(("api_key", self.config.nasa_api_key.clone()));
        }
        let json = self.client.get_json("https://api.nasa.gov/neo/rest/v1/feed", &query).await?;
        CacheRepo::save(&self.pool, "neo", json).await
    }

    pub async fn fetch_donki_flr(&self) -> Result<()> {
        let (from, to) = self.last_days(5);
        let mut query = vec![("startDate", from), ("endDate", to)];
        if !self.config.nasa_api_key.is_empty() {
            query.push(("api_key", self.config.nasa_api_key.clone()));
        }
        let json = self.client.get_json("https://api.nasa.gov/DONKI/FLR", &query).await?;
        CacheRepo::save(&self.pool, "flr", json).await
    }

    pub async fn fetch_donki_cme(&self) -> Result<()> {
        let (from, to) = self.last_days(5);
        let mut query = vec![("startDate", from), ("endDate", to)];
        if !self.config.nasa_api_key.is_empty() {
            query.push(("api_key", self.config.nasa_api_key.clone()));
        }
        let json = self.client.get_json("https://api.nasa.gov/DONKI/CME", &query).await?;
        CacheRepo::save(&self.pool, "cme", json).await
    }

    pub async fn fetch_spacex_next(&self) -> Result<()> {
        let json = self.client.get_json("https://api.spacexdata.com/v4/launches/next", &[]).await?;
        CacheRepo::save(&self.pool, "spacex", json).await
    }

    // Helpers
    async fn latest_from_cache(&self, src: &str) -> Value {
        CacheRepo::get_latest(&self.pool, src).await.ok().flatten()
            .map(|(at, payload)| json!({"at": at, "payload": payload}))
            .unwrap_or(json!({}))
    }

    fn last_days(&self, n: i64) -> (String, String) {
        let to = Utc::now().date_naive();
        let from = to - chrono::Days::new(n as u64);
        (from.to_string(), to.to_string())
    }

    fn num(&self, v: &Value) -> Option<f64> {
        if let Some(x) = v.as_f64() { return Some(x); }
        if let Some(s) = v.as_str() { return s.parse::<f64>().ok(); }
        None
    }

    fn haversine_km(&self, lat1: f64, lon1: f64, lat2: f64, lon2: f64) -> f64 {
        let rlat1 = lat1.to_radians();
        let rlat2 = lat2.to_radians();
        let dlat = (lat2 - lat1).to_radians();
        let dlon = (lon2 - lon1).to_radians();
        let a = (dlat / 2.0).sin().powi(2) + rlat1.cos() * rlat2.cos() * (dlon / 2.0).sin().powi(2);
        let c = 2.0 * a.sqrt().atan2((1.0 - a).sqrt());
        6371.0 * c
    }

    fn s_pick(&self, v: &Value, keys: &[&str]) -> Option<String> {
        for k in keys {
            if let Some(x) = v.get(*k) {
                if let Some(s) = x.as_str() { if !s.is_empty() { return Some(s.to_string()); } }
                else if x.is_number() { return Some(x.to_string()); }
            }
        }
        None
    }

    fn t_pick(&self, v: &Value, keys: &[&str]) -> Option<DateTime<Utc>> {
        for k in keys {
            if let Some(x) = v.get(*k) {
                if let Some(s) = x.as_str() {
                    if let Ok(dt) = s.parse::<DateTime<Utc>>() { return Some(dt); }
                    if let Ok(ndt) = NaiveDateTime::parse_from_str(s, "%Y-%m-%d %H:%M:%S") {
                        return Some(Utc.from_utc_datetime(&ndt));
                    }
                } else if let Some(n) = x.as_i64() {
                    return Some(Utc.timestamp_opt(n, 0).single().unwrap_or_else(Utc::now));
                }
            }
        }
        None
    }
}

