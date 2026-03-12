# Amber

<p align="center">
    <img src="public/icon.png" width="128" alt="Activity Record Logo">
    <br>
    <strong>Smart, Private & Local Activity Tracking</strong>
    <br>
    <em>No cloud. No subscription. Your data, your privacy.</em>
</p>

---

## 🚀 About

**Amber** is a privacy-first desktop application built with **NativePHP**. It helps you track your daily activities, manage projects, and generate intelligent
reports without ever sending your sensitive data to the cloud.

The app sits in your menu bar, silently recording your activity events (file changes, app usage, etc.) and uses local/secure AI to help you summarize your day into professional
activity reports.

## ✨ Key Features

- **🛡️ Privacy First:** All activity data is stored locally in a SQLite database. No tracking, no external servers.
- **🤖 AI-Powered Reports:** Automatically summarize your activity events into clear, concise reports using the Laravel AI SDK.
- **⏱️ Smart Session Tracking:** Start, stop, and switch between projects directly from your macOS/Windows menu bar.
- **📁 Project & Client Management:** Organize your work by client and project for precise billing or personal tracking.
- **📊 Export Options:** Generate activity reports in multiple formats (PDF, CSV) ready for your clients or team.
- **🔔 Native Integration:** Get real-time feedback via system notifications, dock badge updates, and a dedicated menu bar interface.
- **🔍 File Watcher:** (Optional) Automatically detect activity based on file system changes in your development folders.
- **🔗 Deep Link Support:** Trigger session actions from external tools (Raycast, Alfred, scripts) using `amber://` URLs.

## 🖼 Screenshots

*Coming soon...*

> [!TIP]
> This section will be updated with the final UI once the first stable version is released.

## 🔗 Deep Linking

Amber supports deep links via the `amber://` URL scheme, allowing external tools (Raycast, Alfred, shell scripts, etc.) to control sessions.

| URL | Action |
|-----|--------|
| `amber://session/start?project=<id>` | Start a session on the given project |
| `amber://session/start` | Start a session on the first active project |
| `amber://session/stop` | Stop the currently active session |

**Example — open from terminal:**
```bash
open "amber://session/start?project=<project-ulid>"
open "amber://session/stop"
```

## 🛠 Tech Stack

Amber is built with the latest modern web and desktop technologies:

- **Framework:** [Laravel 12](https://laravel.com)
- **Desktop Engine:** [NativePHP](https://nativephp.com)
- **Frontend:** [Vue 3](https://vuejs.org) with [Inertia.js v2](https://inertiajs.com)
- **Styling:** [Tailwind CSS v4](https://tailwindcss.com)
- **Database:** SQLite
- **AI Integration:** [Laravel AI SDK](https://github.com/laravel/ai)
- **Testing:** [Pest 4](https://pestphp.com)

## 📦 Installation

### For Developers

If you want to run the application from source or contribute to its development:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/ngiraud/amber.git
   cd amber
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Set up your environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Prepare the database:**
   ```bash
   php artisan native:migrate --seed
   ```

5. **Run the application:**
   ```bash
   composer native:dev
   ```

## ⚖️ License

This project is open-sourced software licensed under the **[MIT license](LICENSE)**.

---

<p align="center">
    Built with ❤️ using <strong>NativePHP</strong> & <strong>Laravel</strong>.
</p>
