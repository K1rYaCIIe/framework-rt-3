use serde::Serialize;
use chrono::{DateTime, Utc};
use serde_json::Value;
use validator::Validate;

#[derive(Serialize, Validate)]
pub struct Health {
    pub status: &'static str,
    pub now: DateTime<Utc>,
}

#[derive(Serialize, Validate)]
pub struct Trend {
    pub movement: bool,
    pub delta_km: f64,
    pub dt_sec: f64,
    pub velocity_kmh: Option<f64>,
    pub from_time: Option<DateTime<Utc>>,
    pub to_time: Option<DateTime<Utc>>,
    pub from_lat: Option<f64>,
    pub from_lon: Option<f64>,
    pub to_lat: Option<f64>,
    pub to_lon: Option<f64>,
}

#[derive(Serialize, Validate)]
pub struct OsdrItem {
    pub id: i64,
    #[validate(length(min = 1))]
    pub dataset_id: Option<String>,
    pub title: Option<String>,
    pub status: Option<String>,
    pub updated_at: Option<DateTime<Utc>>,
    pub inserted_at: DateTime<Utc>,
    pub raw: Value,
}

