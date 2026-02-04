<div align="center">
  <a href="https://github.com/koamishin/KoamiStarterKit">
    <!-- Replace with actual logo URL if available, or keep using the text/emoji representation -->
    <img src="public/koamishin-logo.svg" alt="Logo" width="300" height="auto">
  </a>

  <h1 align="center">Koamishin Starterkit</h1>

  <p align="center">
    <strong>The Opinionated Laravel Starter Kit for Modern Artisans</strong>
  </p>

  <p align="center">
    <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel 12" /></a>
    <a href="https://vuejs.org"><img src="https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vue.js" alt="Vue 3" /></a>
    <a href="https://inertiajs.com"><img src="https://img.shields.io/badge/Inertia-v2-9553E9?style=for-the-badge&logo=inertia" alt="Inertia v2" /></a>
    <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind_CSS-4-38B2AC?style=for-the-badge&logo=tailwind-css" alt="Tailwind 4" /></a>
    <a href="https://ui.shadcn.com"><img src="https://img.shields.io/badge/Shadcn-Vue-000000?style=for-the-badge&logo=shadcnui" alt="Shadcn Vue" /></a>
  </p>
</div>

<br/>

## 🚀 Why This Exists?

I’m tired of repeating the same setup every time I start a Laravel project—installing frontend tools, tweaking TypeScript, wiring up linters, hunting for UI components.  

So I built **Koamishin Starterkit**: one command, zero friction, and I’m straight into shipping features instead of fighting config files.  

Use it as-is, fork it, or cherry-pick the parts you like—whatever gets you coding faster.
---

## ✨ Features

**Battery-included, but not bloated.** Everything you need to ship.

- **🔐 Complete Authentication**: Powered by **Fortify**. Login, Registration, 2FA, Email Verification, and Profile Management ready to go.
- **👥 Roles & Permissions**: Built-in **Spatie Permissions**. Manage **Admins** (Filament access) and **Users** (Inertia access) out of the box.
- **🕵️‍♂️ User Impersonation**: Admins can easily impersonate users to troubleshoot issues, with a visible banner and quick "Leave" action.
- **🎛️ Admin Panel**: Pre-configured **Filament** admin dashboard with User Management.
- **🎨 40+ UI Components**: Beautiful, accessible components from **Shadcn Vue**, plus dark mode and multiple layouts.
- **🛠️ Type-Safe Routing**: **Wayfinder** ensures your frontend knows your backend routes. No more broken links.
- **⚡ High Performance**: **Laravel Octane** + **Inertia.js v2** + **Vite** for instant page loads.
- **🚢 Production Ready**: **Docker** support, **GitHub Actions** CI/CD, and strict code quality tools (Pint, PHPStan, Rector) pre-configured.

---

## 🏁 Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM/Bun

### Installation

You can create a new project using Composer:

```bash
composer create-project koamishin/koamistarterkit my-app
cd my-app
```

Or use laravel new command:

```bash
laravel new my-app --using=starter-kit=koamishin/koamistarterkit

```

### ⚙️ Setup & Configuration

Once installed, personalize the starter kit with your own project details using our setup wizard:

```bash
php artisan setup:starter-kit
```

This interactive tool will:

- 🎨 **Personalize** `composer.json` with your author and package details.
- 🐳 **Configure Docker** settings (Docker Hub vs GHCR).
- 🤖 **Update GitHub Actions** workflows to use your repository and registry.

### Development

Start the development server with one simple command:

```bash
composer run dev
```

This runs both the Laravel server and the Vite development server concurrently.

---

## 📦 What's Inside?

### UI Components (Shadcn)

This starter kit includes a comprehensive suite of UI components to jumpstart your development:

<details>
<summary><strong>Click to view all included components</strong></summary>

- **Form Elements**: Input, Select, Checkbox, Radio, Switch, Slider, Textarea, Form, Combobox
- **Feedback**: Alert, Badge, Progress, Skeleton, Sonner (Toast), Spinner, Tooltip
- **Overlay**: Dialog, Drawer, Sheet, Popover, Hover Card, Context Menu, Dropdown Menu
- **Layout**: Card, Aspect Ratio, Resizable, Scroll Area, Separator
- **Navigation**: Sidebar, Navigation Menu, Breadcrumb, Tabs, Menubar, Pagination, Stepper
- **Data Display**: Table, Avatar, Accordion, Collapsible, Carousel, Calendar
- **Charts**: Extensive charting library support

</details>

---

## 🤝 Contributing

This is a community-friendly project. If you find a bug or have an idea for an improvement, please feel free to open an issue or submit a pull request.

1.  Fork the Project
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3.  Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4.  Push to the Branch (`git push origin feature/AmazingFeature`)
5.  Open a Pull Request

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

<div align="center">
  <p>Built with ❤️ by Koamishin</p>
</div>
