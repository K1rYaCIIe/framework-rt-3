use axum::{
    extract::{Path, Query, State},
    http::StatusCode,
    Json,
};
use serde_json::Value;
use crate::domain::{Health, Trend};
use crate::services::SpaceService;
use crate::config::Config;
use std::collections::HashMap;
use chrono::Utc;
use std::sync::Arc;

pub struct AppState {
    pub service: Arc<SpaceService>,
    pub config: Config,
}

pub async fn health() -> Json<Health> {
    Json(Health { status: "ok", now: Utc::now() })
}

pub async fn last_iss(State(state): State<Arc<AppState>>) -> Result<Json<Value>, (StatusCode, String)> {
    state.service.get_last_iss().await
        .map(Json)
        .map_err(|e| (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()))
}

pub async fn trigger_iss(State(state): State<Arc<AppState>>) -> Result<Json<Value>, (StatusCode, String)> {
    state.service.trigger_iss_fetch().await
        .map_err(|e| (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()))?;
    last_iss(State(state)).await
}

pub async fn iss_trend(State(state): State<Arc<AppState>>) -> Result<Json<Trend>, (StatusCode, String)> {
    state.service.get_iss_trend().await
        .map(Json)
        .map_err(|e| (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()))
}

pub async fn osdr_sync(State(state): State<Arc<AppState>>) -> Result<Json<Value>, (StatusCode, String)> {
    let written = state.service.fetch_osdr().await
        .map_err(|e| (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()))?;
    Ok(Json(serde_json::json!({ "written": written })))
}

pub async fn osdr_list(State(state): State<Arc<AppState>>) -> Result<Json<Value>, (StatusCode, String)> {
    state.service.list_osdr(state.config.osdr_list_limit).await
        .map(Json)
        .map_err(|e| (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()))
}

pub async fn space_latest(Path(src): Path<String>, State(state): State<Arc<AppState>>) -> Result<Json<Value>, (StatusCode, String)> {
    state.service.get_space_latest(&src).await
        .map(Json)
        .map_err(|e| (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()))
}

pub async fn space_refresh(Query(q): Query<HashMap<String,String>>, State(state): State<Arc<AppState>>) -> Result<Json<Value>, (StatusCode, String)> {
    let list = q.get("src").cloned().unwrap_or_else(|| "apod,neo,flr,cme,spacex".to_string());
    let refreshed = state.service.refresh_cache(&list).await
        .map_err(|e| (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()))?;
    Ok(Json(serde_json::json!({ "refreshed": refreshed })))
}

pub async fn space_summary(State(state): State<Arc<AppState>>) -> Result<Json<Value>, (StatusCode, String)> {
    state.service.get_summary().await
        .map(Json)
        .map_err(|e| (StatusCode::INTERNAL_SERVER_ERROR, e.to_string()))
}

