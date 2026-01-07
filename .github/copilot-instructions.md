# TileStove Map Editor - AI Coding Instructions

You are an expert AI coding agent working on the TileStove Map Editor. This project is a **Laravel 12** application using **Inertia.js 2.0**, **Vue 3**, and **TailwindCSS**.

## 🏗 Architecture & Patterns

### Backend (Laravel)
- **Domain-Driven Design (Lite)**: Logic is organized into domain-specific layers, not just MVC.
  - **Services** (`app/Services`): Encapsulate complex business logic (e.g., `MapImportService`, `MapDisplayService`). **Always** put logic here, not in Controllers.
  - **Repositories** (`app/Repositories`): Handle database access (e.g., `TileMapRepository`). Use these instead of direct Eloquent calls in Services/Controllers.
  - **DTOs** (`app/DataTransferObjects`): Use for structured data passing between layers.
  - **Value Objects** (`app/ValueObjects`): Immutable objects for domain concepts (e.g., `Tile`).
  - **Enums** (`app/Enums`): Use PHP Enums for fixed sets of values (e.g., `LayerType`).
- **Strict Typing**: All PHP files must start with `declare(strict_types=1);`. Use return types and property types everywhere.
- **API/Docs**: Uses `l5-swagger` for API documentation.

### Frontend (Vue 3 + Inertia)
- **Structure**:
  - `resources/js/pages`: Inertia page components.
  - `resources/js/components`: Reusable UI components.
  - `resources/js/layouts`: Page layouts.
  - `resources/js/stores`: Pinia stores for global state.
  - `resources/js/dtos`: TypeScript DTOs for type safety.
- **Styling**: TailwindCSS. Avoid custom CSS unless absolutely necessary.
- **TypeScript**: Strict mode is enabled. Define interfaces/types for all props and state.

## 🛠 Workflows & Commands

- **Development**:
  - `composer dev`: Starts the PHP server (and likely other dev services).
  - `npm run dev`: Starts the Vite development server.
- **Testing**:
  - **Backend**: `vendor/bin/pest` (Pest PHP). **Prefer Pest over PHPUnit syntax.**
  - **Frontend Unit**: `npm run test:unit` (Vitest).
  - **E2E**: `npm run test:e2e` (Playwright).
- **Code Quality**:
  - `npm run lint`: ESLint.
  - `npm run typecheck`: Vue-TSC.
  - `npm run format`: Prettier.

## 🧩 Key Implementation Details

- **Map Import System**: Uses a Strategy pattern (`ImporterInterface`). When adding new formats, implement this interface and register in `MapImportService`.
- **Tile Data**: Handled via `Tile` Value Object and `TileArrayCast` for Eloquent models.
- **Inertia**: Use `router` from `@inertiajs/vue3` for navigation. Use `usePage()` for shared props.

## 🚨 Rules for AI Agents

1.  **Context First**: Before editing, check `app/Services` and `app/Repositories` to see if logic already exists.
2.  **Type Safety**: Never use `mixed` or `any` unless impossible to avoid. Create DTOs/Interfaces if missing.
3.  **Testing**: When modifying logic, update or create a corresponding Pest test (backend) or Vitest spec (frontend).
4.  **No Raw SQL**: Use Eloquent or Query Builder within Repositories.
5.  **Modern Syntax**: Use PHP 8.2+ features (readonly classes, match expressions) and Vue 3 Composition API (`<script setup lang="ts">`).
