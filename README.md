# NEXUS Smart Archive

### Introduction to Artificial Intelligence Project — PHP + MySQL + Google Drive API

NEXUS Smart Archive is an intelligent, cloud-integrated digital asset management system. It leverages a custom Natural Language Processing (NLP) engine and a Weighted Decision Tree algorithm to categorize, index, and retrieve multimedia files seamlessly from Google Drive.

---

## 📦 Project Structure

```text
nexus-archive/
├── index.php               ← Main user interface (Dashboard, Chatbot, File Browser)
├── php.ini                 ← PHP configuration for upload size limits
├── README.md               ← Project documentation
├── includes/
│   ├── config.php          ← Database & API configuration (REQUIRED UPDATE)
│   ├── credentials.json    ← Google Service Account credentials
│   ├── decision_tree.php   ← Core AI Engine (NLP & Decision Tree logic)
│   └── drive_service.php   ← Google Drive API service class
├── api/
│   ├── delete_dummy.php    ← Endpoint to clear demo data
│   ├── files.php           ← Endpoint for the file browser table
│   ├── search.php          ← POST endpoint for Chatbot queries
│   ├── seed_dummy.php      ← Endpoint to inject demo data (Remove in production)
│   ├── stats.php           ← GET endpoint for dashboard analytics
│   ├── sync.php            ← POST endpoint for Drive synchronization
│   ├── test_drive.php      ← Drive API diagnostic tool
│   └── upload.php          ← POST endpoint for direct file uploads
├── assets/
│   ├── css/style.css       ← Vanilla CSS stylesheet (Glassmorphism UI)
│   └── js/app.js           ← Vanilla JavaScript for frontend interactivity
└── setup/
    └── database.sql        ← Database schema and initial keyword weights

```

---

## 🚀 Getting Started

### Prerequisites

* PHP 8.1+ (Required extensions: `pdo_mysql`, `openssl`, `json`)
* MySQL 5.7+ or MariaDB 10.4+
* Web server: Apache, Nginx, or PHP Built-in Server (`php -S localhost:8000`)

### Step 1 — Database Setup

Import the initial schema and AI keyword weights into your MySQL server:

```bash
mysql -u root -p < setup/database.sql

```

### Step 2 — Application Configuration

Edit the `includes/config.php` file to match your local environment:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Your MySQL username
define('DB_PASS', '');              // Your MySQL password
define('DB_NAME', 'smart_archive');

define('DRIVE_FOLDER_ID', 'CHANGE_THIS_ID'); // Your target Google Drive Folder ID

```

### Step 3 — Running the Server

Start the application using the PHP built-in server:

```bash
cd nexus-archive
php -S localhost:8000

```

Open your web browser and navigate to: **http://localhost:8000**

### Step 4 — Demo Data Initialization

Click the **+ Data Demo** button in the top right corner of the application to inject 50+ simulated records. Test the AI chatbot by typing commands like: `find graduation photos from 2026`.

---

## 🔑 Google Drive API Setup (Production Environment)

### A. Google Cloud Console Project

1. Navigate to Google Cloud Console.
2. Click **New Project** and assign a project name.
3. On the left sidebar, navigate to **APIs & Services** → **Library**.
4. Search for **Google Drive API** and click **Enable**.

### B. Service Account Creation

1. Navigate to **APIs & Services** → **Credentials**.
2. Click **Create Credentials** → **Service Account**.
3. Provide a service account name and click **Create and Continue**.
4. Assign the Role as **Editor** (Crucial for application upload capabilities).
5. Open the newly created service account.
6. Navigate to the **Keys** tab → **Add Key** → **Create new key** → **JSON**.
7. The `credentials.json` file will download automatically.

### C. Folder Access Sharing

1. Open your target Google Drive folder containing the archives.
2. Click **Share**.
3. Paste the service account email (found in `credentials.json` under `client_email`).
4. Set the permission to **Editor**.

### D. Obtaining Folder ID

Extract the Folder ID directly from the Google Drive URL:

```text
https://drive.google.com/drive/folders/1a4rqpxxxxxxxxxxxxxxxxx
                                         ↑ THIS IS THE FOLDER_ID

```

### E. Credentials Placement

Move the downloaded JSON file into the `includes` directory:

```bash
cp ~/Downloads/credentials.json nexus-archive/includes/credentials.json

```

### F. Synchronization & Upload

Toggle the **Auto-Sync** switch in the application to automatically pull new files every minute, or click **↻ Sinkronisasi Drive** for a manual fetch. You can now use the internal upload form to push files directly to the cloud.

---

## 🌳 Decision Tree AI Mechanism

```text
Input: "find graduation photos from 2026"
         ↓
    Tokenization & Lowercase
         ↓
  Database Keyword Matching & Weight Calculation
  ┌───────────────────────────────────────────────┐
  │  "graduation" → Photo · Graduation (10 pts)   │ ← Highest Score Branch
  │  "photo"      → Photo · (Any)      (5 pts)    │
  └───────────────────────────────────────────────┘
         ↓
  Dynamic Query Construction: 
  SELECT * FROM files WHERE jenis='Foto' AND kategori='Wisuda' AND tahun=2026
         ↓
  Return formatted JSON response to Chatbot UI

```

To enhance the AI's vocabulary, insert new keywords directly into the `dt_keywords` table:

```sql
INSERT INTO dt_keywords (keyword, jenis, kategori, bobot)
VALUES ('ceremony', 'Foto', 'Wisuda', 8);

```

---

## 📋 Project Deliverables

| No | Deliverable | Location / Requirement |
| --- | --- | --- |
| 1 | Source Code | All files within this directory structure |
| 2 | Dataset | Exported `files` and `dt_keywords` tables (CSV/SQL) |
| 3 | Application Dashboard | Accessible via `http://localhost:8000` |
| 4 | Demonstration Video | Screen recording of the Chatbot and Upload features |
| 5 | Final Report | Separate Word/PDF documentation |

---

## ❓ Troubleshooting

| Error Output | Resolution |
| --- | --- |
| `DB connection failed` | Verify `DB_HOST`, `DB_USER`, and `DB_PASS` in `config.php`. |
| `credentials.json not found` | Ensure the file is correctly placed inside the `includes/` directory. |
| `Request had insufficient authentication scopes` | Ensure the JWT scope in `drive_service.php` is set to `auth/drive` (remove `.readonly`). |
| Files missing after synchronization | Verify that the target folder is shared with the Service Account email. |
| `Service Accounts do not have storage quota` | Use a Workspace Shared Drive or rely on manual Drive uploads via the application UI link. |
| Broken thumbnail images | Click manual Sync; ensure the code uses the permanent `drive.google.com/thumbnail?id=` format. |
