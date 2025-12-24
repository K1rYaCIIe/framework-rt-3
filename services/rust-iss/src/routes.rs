use axum::{
    routing::get,
    Router,
};
use crate::handlers::{self, AppState};
use std::sync::Arc;
use tower_http::trace::TraceLayer;

pub fn create_router(state: Arc<AppState>) -> Router {
    Router::new()
        .route("/health", get(handlers::health))
        .route("/last", get(handlers::last_iss))
        .route("/fetch", get(handlers::trigger_iss))
        .route("/iss/trend", get(handlers::iss_trend))
        .route("/osdr/sync", get(handlers::osdr_sync))
        .route("/osdr/list", get(handlers::osdr_list))
        .route("/space/:src/latest", get(handlers::space_latest))
        .route("/space/refresh", get(handlers::space_refresh))
        .route("/space/summary", get(handlers::space_summary))
        .layer(TraceLayer::new_for_http())
        .with_state(state)
}

