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

Let's be real. Setting up a new Laravel project often involves the same repetitive dance: installing your favorite frontend tools, configuring TypeScript, setting up linting, adding UI components... it's a hassle.

I created **Koamishin Starterkit** because I got tired of that dance.

I wanted a **"battery-included"** starting point where I could run one command and immediately start building the features that actually matter. No more "hassle things"—just pure coding.

---

## ✨ Features

This kit is **opinionated**. It comes pre-wired with the tools I believe offer the best Developer Experience (DX) for building modern web applications in 2026.

### 🛠 Backend
-   **[Laravel 12](https://laravel.com)**: The latest and greatest.
-   **[Laravel Octane](https://laravel.com/docs/octane)**: Supercharged performance with Frankentrans.
-   **[Laravel Fortify](https://laravel.com/docs/fortify)**: Headless authentication backend.
-   **[Wayfinder](https://github.com/laravel/wayfinder)**: TypeScript types for your routes.
-   **[Pest](https://pestphp.com)**: Elegant testing framework.

### 🎨 Frontend
-   **[Vue 3](https://vuejs.org)**: The progressive JavaScript framework.
-   **[Inertia.js v2](https://inertiajs.com)**: Build single-page apps without the complexity.
-   **[Tailwind CSS 4](https://tailwindcss.com)**: Next-gen utility-first CSS.
-   **[Shadcn Vue](https://www.shadcn-vue.com/)**: Beautiful, reusable components built with Radix Vue.
-   **[Ziggy](https://github.com/tighten/ziggy)**: Use your Laravel routes in JavaScript.

### 🧹 Code Quality
-   **[Laravel Pint](https://laravel.com/docs/pint)**: PHP code style fixer (built on CS Fixer).
-   **[Prettier](https://prettier.io)**: Opinionated code formatter for JS/Vue/HTML.
-   **[ESLint](https://eslint.org)**: Find and fix problems in your JavaScript.

---

## 🏁 Getting Started

### Prerequisites
-   PHP 8.2+
-   Composer
-   Node.js & NPM/Bun

### Installation

You can create a new project using Composer:

```bash
composer create-project koamishin/koamistarterkit my-app
cd my-app
```

### Development

Start the development server with one simple command:

```bash
composer run dev
```

This runs both the Laravel server and the Vite development server concurrently.

---

## 📦 What's Inside?

### UI Components (Shadcn)
We've pre-installed the core components you need to build a dashbaord or landing page:
-   Buttons, Cards, Inputs
-   Dropdowns, Dialogs, sheets
-   Sidebar, Navigation Menu
-   And more...

### Authentication
Ready-to-use authentication views and logic powered by Fortify:
-   Login & Registration
-   Password Reset
-   Email Verification

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
