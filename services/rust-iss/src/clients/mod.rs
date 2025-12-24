use std::time::Duration;
use reqwest::{Client, ClientBuilder};
use serde_json::Value;
use anyhow::Result;

pub struct ApiClient {
    client: Client,
}

impl ApiClient {
    pub fn new() -> Self {
        let client = ClientBuilder::new()
            .timeout(Duration::from_secs(30))
            .user_agent("CassiopeiaSpaceBot/1.0")
            .build()
            .unwrap_or_else(|_| Client::new());
        Self { client }
    }

    pub async fn get_json(&self, url: &str, query: &[(&str, String)]) -> Result<Value> {
        let mut req = self.client.get(url);
        if !query.is_empty() {
            req = req.query(query);
        }
        let resp = req.send().await?;
        if !resp.status().is_success() {
            anyhow::bail!("API request failed with status {}", resp.status());
        }
        Ok(resp.json().await?)
    }
}

