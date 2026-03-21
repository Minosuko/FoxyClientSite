-- OAuth2 Tables for FoxyClientSite

CREATE TABLE IF NOT EXISTS oauth_clients (
    client_id VARCHAR(80) NOT NULL PRIMARY KEY,
    client_secret VARCHAR(80),
    redirect_uri VARCHAR(2000) NOT NULL,
    grant_types VARCHAR(80),
    scope VARCHAR(255),
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS oauth_access_tokens (
    access_token VARCHAR(255) NOT NULL PRIMARY KEY,
    client_id VARCHAR(80) NOT NULL,
    user_id INT NOT NULL,
    expires TIMESTAMP NOT NULL,
    scope VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS oauth_authorization_codes (
    authorization_code VARCHAR(80) NOT NULL PRIMARY KEY,
    client_id VARCHAR(80) NOT NULL,
    user_id INT NOT NULL,
    redirect_uri VARCHAR(2000) NOT NULL,
    expires TIMESTAMP NOT NULL,
    scope VARCHAR(255),
    code_challenge VARCHAR(128),
    code_challenge_method VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default client for FoxyClient Launcher
-- client_id: foxyapp
-- client_secret: foxysecret_123 (used for confidential flow if needed)
-- redirect_uri: http://localhost:25564/callback
INSERT IGNORE INTO oauth_clients (client_id, client_secret, redirect_uri, grant_types, scope) 
VALUES ('foxyapp', 'foxysecret_123', 'http://localhost:25564/callback', 'authorization_code', 'account_info');
