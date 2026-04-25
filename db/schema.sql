CREATE DATABASE IF NOT EXISTS ppf3jn;
USE ppf3jn;

CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name  VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS schools (
    school_id   INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS application_cycles (
    cycle_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NULL,
    cycle_name VARCHAR(100) NOT NULL,
    UNIQUE (user_id, cycle_name),
    CONSTRAINT fk_application_cycles_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS cities (
    city_id    INT AUTO_INCREMENT PRIMARY KEY,
    city_name  VARCHAR(100) NOT NULL,
    state_name VARCHAR(100) NOT NULL,
    UNIQUE (city_name, state_name)
);

CREATE TABLE IF NOT EXISTS companies (
    company_id   INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS terms (
    term_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    school_id   INT NOT NULL,
    start_date  DATE NOT NULL,
    end_date    DATE NOT NULL,
    degree_type VARCHAR(100) NOT NULL,
    major       VARCHAR(150) NOT NULL,
    CONSTRAINT fk_terms_user   FOREIGN KEY (user_id)   REFERENCES users(user_id)     ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_terms_school FOREIGN KEY (school_id) REFERENCES schools(school_id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS profiles (
    user_id         INT PRIMARY KEY,
    biography       TEXT,
    profile_picture VARCHAR(255),
    CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    role_title     VARCHAR(255) NOT NULL,
    status         VARCHAR(50)  NOT NULL,
    company_id     INT NOT NULL,
    city_id        INT NOT NULL,
    cycle_id       INT NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_applications_company FOREIGN KEY (company_id) REFERENCES companies(company_id)              ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_applications_city    FOREIGN KEY (city_id)    REFERENCES cities(city_id)                    ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_applications_cycle   FOREIGN KEY (cycle_id)   REFERENCES application_cycles(cycle_id)       ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_application_status  CHECK (status IN ('Draft','Submitted','Interview','Offer','Rejected','Withdrawn'))
);

CREATE TABLE IF NOT EXISTS submits (
    user_id        INT NOT NULL,
    application_id INT NOT NULL,
    submitted_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, application_id),
    CONSTRAINT fk_submits_user        FOREIGN KEY (user_id)        REFERENCES users(user_id)               ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_submits_application FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS documents (
    doc_id    INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    CONSTRAINT chk_file_name_not_empty CHECK (file_name <> '')
);

CREATE TABLE IF NOT EXISTS uploads (
    user_id     INT NOT NULL,
    doc_id      INT NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, doc_id),
    CONSTRAINT fk_uploads_user FOREIGN KEY (user_id) REFERENCES users(user_id)       ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_uploads_doc  FOREIGN KEY (doc_id)  REFERENCES documents(doc_id)    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS application_documents (
    application_id INT NOT NULL,
    doc_id         INT NOT NULL,
    PRIMARY KEY (application_id, doc_id),
    CONSTRAINT fk_appdocs_application FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_appdocs_doc         FOREIGN KEY (doc_id)         REFERENCES documents(doc_id)            ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS user_connections (
    user_id           INT NOT NULL,
    connected_user_id INT NOT NULL,
    connected_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, connected_user_id),
    CONSTRAINT fk_connections_user           FOREIGN KEY (user_id)           REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_connections_connected_user FOREIGN KEY (connected_user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- seed

INSERT IGNORE INTO companies (company_name) VALUES
('Google'), ('Meta'), ('Amazon'), ('Apple'), ('Microsoft'),
('Stripe'), ('Notion'), ('Figma'), ('Airbnb'), ('Uber'), ('OpenAI'), ('Nvidia');

INSERT IGNORE INTO cities (city_name, state_name) VALUES
('San Francisco', 'CA'), ('Seattle', 'WA'), ('New York', 'NY'),
('Austin', 'TX'), ('Boston', 'MA'), ('Chicago', 'IL'),
('Los Angeles', 'CA'), ('Denver', 'CO'), ('Atlanta', 'GA'),
('Remote', '');

INSERT IGNORE INTO schools (school_name) VALUES
('University of Virginia'),
('Virginia Tech'),
('George Mason University'),
('James Madison University'),
('College of William & Mary'),
('Virginia Commonwealth University'),
('Old Dominion University'),
('Liberty University'),
('Harvard University'),
('Stanford University'),
('Massachusetts Institute of Technology'),
('Yale University'),
('Princeton University'),
('UC Berkeley'),
('Georgia Institute of Technology'),
('Carnegie Mellon University'),
('Cornell University'),
('University of Michigan'),
('Purdue University');
