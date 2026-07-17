-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Таблица встреч
CREATE TABLE IF NOT EXISTS meetings (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL,
    time TIME NOT NULL,
    address TEXT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    created_by VARCHAR(255),
    updated_at TIMESTAMP,
    updated_by VARCHAR(255)
);

-- Таблица логов действий
CREATE TABLE IF NOT EXISTS actions_log (
    id SERIAL PRIMARY KEY,
    action_time TIMESTAMP DEFAULT NOW(),
    ip VARCHAR(45),
    username VARCHAR(255),
    action VARCHAR(50),
    details TEXT
);

-- Создаём тестового пользователя admin / admin (пароль захеширован)
INSERT INTO users (username, password)
VALUES ('admin', '$2y$10$X8z5sJ9Z6zQ7y8w9v0u1w2e3r4t5y6u7i8o9p0q1r2s3t4u5v6w7x8y9z0')
ON CONFLICT (username) DO NOTHING;