# TEMPLATE LARAVEL - INERTIA - VUE - SHADCN

## PURPOSE & OVERVIEW

Template starter lengkap untuk membangun aplikasi web modern menggunakan **Laravel 11** sebagai backend framework dengan **Inertia.js** sebagai bridge ke **Vue 3** untuk frontend, dilengkapi dengan **shadcn/ui** (Reka UI) sebagai UI component library berbasis Tailwind CSS.

**Gunakan skill ini ketika:**
- User ingin membuat project baru dengan stack Laravel-Inertia-Vue-Shadcn
- User perlu menambah fitur/page/component ke template ini
- User bertanya tentang struktur project atau best practices
- User ingin understand bagaimana stack ini bekerja bersama
- User memerlukan guidance tentang authentication, routing, atau component structure

## TECHNOLOGY STACK & RATIONALE

### **Backend: Laravel 11**
- Full-stack PHP framework dengan built-in routing, authentication, middleware
- Fitur ORM (Eloquent) untuk database interactions
- Blade templating engine (tetapi diganti dengan Vue di sini)
- Excellent for rapid development dengan security best practices

### **Bridge: Inertia.js**
- Menghubungkan Laravel backend dengan Vue frontend secara seamless
- Mengirim data dari Laravel controller ke Vue component seperti normal page rendering
- Automatic URL history handling dan component caching
- Eliminates API complexity untuk SPA-like experience

### **Frontend: Vue 3 (Composition API)**
- Progressive JavaScript framework untuk UI interactivity
- Composition API untuk reusable logic
- Single File Components (.vue files) dengan style scoping
- Excellent DX dengan hot module replacement (HMR)

### **Build Tool: Vite**
- Ultra-fast build tool dengan native ES modules support
- Fast HMR untuk development experience
- Optimized production builds
- Diintegrasikan via laravel-vite-plugin

### **Styling: Tailwind CSS 3**
- Utility-first CSS framework
- Rapid prototyping dan consistent design system
- @tailwindcss/forms plugin untuk pre-styled form elements
- Reduced CSS bundle size

### **UI Components: Shadcn/UI (via Reka UI)**
- High-quality, accessible Vue components
- Built on top of Tailwind CSS
- Copy-paste philosophy (components bukan installed)
- Includes Button, Card, Dialog, Form inputs, etc.

## SETUP & INSTALLATION

```bash
# 1. Clone atau download template
git clone https://github.com/ajiji471/laravel-inertia-vue-template
cd laravel-inertia-vue-template

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Copy environment file
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Create database & run migrations
php artisan migrate

# 7. Start development servers (di terminal terpisah):
# Terminal 1 - PHP server:
php artisan serve

# Terminal 2 - Vite dev server:
npm run dev

# Production build:
npm run build
```

**Requirements:**
- PHP >= 8.2
- Node.js >= 18
- npm atau yarn
- Database (SQLite, MySQL, PostgreSQL, etc.)

## PROJECT STRUCTURE EXPLAINED

### **`app/Http/Controllers/`** - Backend Logic
```
Controllers/
├── Auth/              # Authentication-related controllers
│  ├── LoginController.php
│  ├── RegisterController.php
│  └── PasswordResetController.php
└── ProfileController.php  # User profile management
```
- Menangani business logic dan data processing
- Menerima requests dan return Inertia responses
- Menggunakan Service layer untuk complex logic (recommended)

### **`resources/js/Components/`** - Vue Components
```
Components/
├── ui/               # Shadcn/reka-ui components (copy-paste)
│  ├── button/
│  ├── card/
│  ├── dialog/
│  └── form/
├── Layouts/          # Layout wrapper components
│  ├── AuthenticatedLayout.vue
│  └── GuestLayout.vue
└── [Custom Components] # Reusable app-specific components
```
- **UI Components**: Pre-built dari shadcn (atomic level)
- **Custom Components**: Domain-specific, reusable logic
- Naming: PascalCase (e.g., UserCard.vue, ProductForm.vue)

### **`resources/js/Pages/`** - Inertia Pages
```
Pages/
├── Auth/            # Authentication pages
│  ├── Login.vue
│  ├── Register.vue
│  └── ForgotPassword.vue
├── Profile/         # User profile pages
│  └── Edit.vue
├── Dashboard.vue    # Main dashboard
└── Welcome.vue      # Landing page
```
- Full-page components yang dirender oleh Inertia
- Correspond to routes di `routes/web.php`
- File path = URL structure (e.g., Pages/Products/Index.vue → /products)

### **`app/Models/`** - Database Models
```
Models/
└── User.php         # Eloquent model untuk users table
```
- Laravel Eloquent ORM untuk database interactions
- Definisikan relationships, scopes, mutators di sini

### **`database/migrations/`** - Schema Definitions
```
migrations/
├── create_users_table.php
├── create_cache_table.php
└── create_jobs_table.php
```
- Version control untuk database schema
- Run: `php artisan migrate`

### **`routes/`** - URL Routes
```
web.php        # Web routes (return Inertia responses)
auth.php       # Authentication routes (included in web.php)
```
- Define URL routes yang point ke controllers
- Inertia automatically handle client-side routing

### **`config/`** - Application Configuration
- `app.php` - App name, timezone, providers
- `auth.php` - Authentication guards dan providers
- `database.php` - Database connections
- `mail.php` - Email configuration

## KEY FEATURES & WORKFLOWS

### **Authentication System**
Template include lengkap authentication:
- User registration dengan email verification
- Login dengan remember me
- Password reset
- Middleware-protected routes
- Profile edit & password change

**Files involved:**
- Controllers: `app/Http/Controllers/Auth/`
- Pages: `resources/js/Pages/Auth/`
- Routes: `routes/auth.php`
- Middleware: `app/Http/Middleware/Authenticate.php`

### **Creating New Pages**

1. **Create Controller:**
```php
// app/Http/Controllers/ProductController.php
namespace App\Http\Controllers;

class ProductController extends Controller {
    public function index() {
        $products = Product::all();
        return inertia('Products/Index', ['products' => $products]);
    }
}
```

2. **Create Vue Page:**
```vue
<!-- resources/js/Pages/Products/Index.vue -->
<template>
  <AuthenticatedLayout>
    <div class="py-12">
      <h1 class="text-3xl font-bold">Products</h1>
      <!-- Content here -->
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
defineProps({
  products: Array
})
</script>
```

3. **Add Route:**
```php
// routes/web.php
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
```

### **Creating New Components**

```vue
<!-- resources/js/Components/ProductCard.vue -->
<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ product.name }}</CardTitle>
    </CardHeader>
    <CardContent>
      <p>{{ product.description }}</p>
    </CardContent>
  </Card>
</template>

<script setup>
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card'

defineProps({
  product: {
    type: Object,
    required: true
  }
})
</script>
```

### **Form Handling**

Using Inertia form helper:
```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: ''
})

const submit = () => {
  form.post('/users', {
    onSuccess: () => alert('User created!'),
    onError: (errors) => console.log(errors)
  })
}
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="form.name" type="text" />
    <button type="submit" :disabled="form.processing">Save</button>
  </form>
</template>
```

## FOLDER ORGANIZATION PATTERNS

**Backend (Laravel):**
```
app/
├── Http/Controllers/[FeatureName]/
├── Models/[ModelName].php
├── Actions/               # Business logic classes (optional but recommended)
├── Services/              # Reusable services
└── Requests/[FeatureName]/[ActionName]Request.php
```

**Frontend (Vue):**
```
resources/js/
├── Components/ui/        # Shadcn components (readonly)
├── Components/Common/    # Reusable components
├── Pages/[Feature]/      # Feature pages
├── Layouts/              # Layout wrappers
├── lib/                  # Utility functions
└── stores/               # Pinia stores (if using state management)
```

## BEST PRACTICES

### **1. Component Naming**
- Vue components: PascalCase (ProductCard.vue)
- Pages: PascalCase (Products/Index.vue)
- Props: camelCase
- Classes: PascalCase

### **2. File Organization**
- Group by feature, not by type
- Good: `Components/Products/ProductCard.vue`, `Components/Products/ProductForm.vue`
- Avoid: `Components/Cards/ProductCard.vue`, `Components/Forms/ProductForm.vue`

### **3. Props & Emits**
```vue
<script setup>
const props = defineProps({
  modelValue: String,
  disabled: Boolean
})

const emit = defineEmits(['update:modelValue'])
</script>
```

### **4. Styling**
- Use Tailwind utilities primarily
- Avoid inline styles
- Custom CSS in `<style scoped>` untuk component-specific styles
- Follow Tailwind's responsive patterns (sm:, md:, lg:)

### **5. API Calls (Inertia)**
- Gunakan `route()` helper untuk URL generation
- Tidak perlu fetch/axios untuk standard CRUD (gunakan inertia form)
- Untuk API requests khusus, gunakan axios dari dependencies

### **6. Authentication**
- Use `auth()` helper di controller untuk current user
- Frontend: Import `usePage` dari Inertia untuk accessing props
- Middleware: `auth`, `verified`, `guest` sudah tersedia

### **7. Database Relationships**
```php
// app/Models/User.php
class User extends Model {
    public function posts() {
        return $this->hasMany(Post::class);
    }
}
```

### **8. Validation**
```php
// Di controller atau FormRequest
$validated = request()->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users'
]);
```

## COMMON TASKS & SOLUTIONS

### **Add New Database Table**
```bash
php artisan make:migration create_products_table
# Edit database/migrations/[timestamp]_create_products_table.php
php artisan migrate
```

### **Add Authentication to Route**
```php
Route::get('/dashboard', [DashboardController::class, 'show'])
    ->middleware('auth')
    ->name('dashboard');
```

### **Use Tailwind Custom Colors**
Edit `tailwind.config.js`:
```js
theme: {
  extend: {
    colors: {
      primary: '#your-color'
    }
  }
}
```

### **Add Dark Mode Support**
Tailwind already configured. Use `dark:` prefix:
```html
<div class="bg-white dark:bg-black">Content</div>
```

### **Redirect After Form Submission**
```php
return redirect()->route('dashboard')
    ->with('success', 'Profile updated!');
```

## FILE LOCATION QUICK REFERENCE

| Task | Location |
|------|----------|
| Create user-facing page | `resources/js/Pages/` |
| Add reusable component | `resources/js/Components/` |
| Write business logic | `app/Http/Controllers/` |
| Define database table | `database/migrations/` |
| Add URL route | `routes/web.php` |
| Global styles | `resources/css/app.css` |
| Environment variables | `.env` file |
| Database model | `app/Models/` |
| Form validation | `app/Http/Requests/` |

## TESTING

Template include test structure:
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test tests/Feature/Auth/AuthenticationTest.php
```

Tests located di `tests/Feature/` dan `tests/Unit/`

## IMPORTANT NOTES & GOTCHAS

1. **Inertia Response Format:**
   - Always return `inertia('PageName', $props)` from controller
   - Props automatically available di Vue template

2. **Asset Compilation:**
   - Run `npm run dev` di development
   - Run `npm run build` sebelum deployment
   - Assets automatically versioned di production

3. **Database Migrations:**
   - Always create migrations untuk schema changes
   - Never modify existing migrations
   - Run `php artisan migrate:rollback` untuk undo

4. **Environment Variables:**
   - Copy `.env.example` ke `.env`
   - Generate `APP_KEY`: `php artisan key:generate`
   - Secrets like DB passwords di `.env` (not in version control)

5. **CSRF Protection:**
   - Built-in dengan Inertia forms
   - Included automatically di POST/PUT/PATCH requests

6. **Component Scope:**
   - Shadcn components di `resources/js/Components/ui/` adalah template read-only
   - Modify copy-nya jika perlu customize
   - Jangan edit dari node_modules

7. **Hot Module Replacement (HMR):**
   - Vite dev server auto-refresh on file changes
   - Jika tidak work: restart `npm run dev`
   - Check browser console untuk errors

## SCALING CONSIDERATIONS

Untuk aplikasi yang lebih complex:

- **State Management:** Gunakan Pinia untuk global state
- **API Routes:** Tambah `routes/api.php` untuk REST API
- **Services:** Extract business logic ke `app/Services/`
- **Jobs & Queues:** Untuk async tasks
- **Caching:** Gunakan Redis untuk session/caching
- **Testing:** Expand test coverage dengan factories & seeders

## USEFUL COMMANDS

```bash
# Laravel Commands
php artisan tinker                    # Interactive shell
php artisan make:controller ControllerName
php artisan make:model ModelName -m   # Model + migration
php artisan make:migration create_table_name
php artisan migrate:refresh           # Reset & migrate
php artisan db:seed                   # Seed database

# Node Commands
npm install [package-name]            # Add npm package
npm run dev                           # Development server
npm run build                         # Production build

# Useful shortcuts
php artisan serve --port=8001         # Run on different port
npm run build -- --watch              # Watch mode for build
```

---

**Versi Template:** 1.0  
**Last Updated:** 2024  
**Compatibility:** Laravel 11+, Vue 3, Tailwind CSS 3, Vite 4+

## STRUCTURE

```
laravel-inertia-vue-template
 ┣ app
 ┃ ┣ Http
 ┃ ┃ ┣ Controllers
 ┃ ┃ ┃ ┣ Auth
 ┃ ┃ ┃ ┃ ┣ AuthenticatedSessionController.php
 ┃ ┃ ┃ ┃ ┣ ConfirmablePasswordController.php
 ┃ ┃ ┃ ┃ ┣ EmailVerificationNotificationController.php
 ┃ ┃ ┃ ┃ ┣ EmailVerificationPromptController.php
 ┃ ┃ ┃ ┃ ┣ NewPasswordController.php
 ┃ ┃ ┃ ┃ ┣ PasswordController.php
 ┃ ┃ ┃ ┃ ┣ PasswordResetLinkController.php
 ┃ ┃ ┃ ┃ ┣ RegisteredUserController.php
 ┃ ┃ ┃ ┃ ┗ VerifyEmailController.php
 ┃ ┃ ┃ ┣ Controller.php
 ┃ ┃ ┃ ┗ ProfileController.php
 ┃ ┃ ┣ Middleware
 ┃ ┃ ┃ ┗ HandleInertiaRequests.php
 ┃ ┃ ┗ Requests
 ┃ ┃ ┃ ┣ Auth
 ┃ ┃ ┃ ┃ ┗ LoginRequest.php
 ┃ ┃ ┃ ┗ ProfileUpdateRequest.php
 ┃ ┣ Models
 ┃ ┃ ┗ User.php
 ┃ ┗ Providers
 ┃ ┃ ┗ AppServiceProvider.php
 ┣ bootstrap
 ┃ ┣ cache
 ┃ ┃ ┣ .gitignore
 ┃ ┃ ┣ packages.php
 ┃ ┃ ┗ services.php
 ┃ ┣ app.php
 ┃ ┗ providers.php
 ┣ config
 ┃ ┣ app.php
 ┃ ┣ auth.php
 ┃ ┣ cache.php
 ┃ ┣ database.php
 ┃ ┣ filesystems.php
 ┃ ┣ logging.php
 ┃ ┣ mail.php
 ┃ ┣ queue.php
 ┃ ┣ services.php
 ┃ ┗ session.php
 ┣ database
 ┃ ┣ factories
 ┃ ┃ ┗ UserFactory.php
 ┃ ┣ migrations
 ┃ ┃ ┣ 0001_01_01_000000_create_users_table.php
 ┃ ┃ ┣ 0001_01_01_000001_create_cache_table.php
 ┃ ┃ ┗ 0001_01_01_000002_create_jobs_table.php
 ┃ ┣ seeders
 ┃ ┃ ┗ DatabaseSeeder.php
 ┣ public
 ┃ ┣ build
 ┃ ┣ .htaccess
 ┃ ┣ favicon.ico
 ┃ ┣ hot
 ┃ ┣ index.php
 ┃ ┗ robots.txt
 ┣ resources
 ┃ ┣ css
 ┃ ┃ ┗ app.css
 ┃ ┣ js
 ┃ ┃ ┣ Components
 ┃ ┃ ┃ ┣ ui
 ┃ ┃ ┃ ┃ ┣ button
 ┃ ┃ ┃ ┃ ┃ ┣ Button.vue
 ┃ ┃ ┃ ┃ ┃ ┗ index.js
 ┃ ┃ ┃ ┃ ┗ card
 ┃ ┃ ┃ ┃ ┃ ┣ Card.vue
 ┃ ┃ ┃ ┃ ┃ ┣ CardAction.vue
 ┃ ┃ ┃ ┃ ┃ ┣ CardContent.vue
 ┃ ┃ ┃ ┃ ┃ ┣ CardDescription.vue
 ┃ ┃ ┃ ┃ ┃ ┣ CardFooter.vue
 ┃ ┃ ┃ ┃ ┃ ┣ CardHeader.vue
 ┃ ┃ ┃ ┃ ┃ ┣ CardTitle.vue
 ┃ ┃ ┃ ┃ ┃ ┗ index.js
 ┃ ┃ ┃ ┣ ApplicationLogo.vue
 ┃ ┃ ┃ ┣ Checkbox.vue
 ┃ ┃ ┃ ┣ DangerButton.vue
 ┃ ┃ ┃ ┣ Dropdown.vue
 ┃ ┃ ┃ ┣ DropdownLink.vue
 ┃ ┃ ┃ ┣ InputError.vue
 ┃ ┃ ┃ ┣ InputLabel.vue
 ┃ ┃ ┃ ┣ Modal.vue
 ┃ ┃ ┃ ┣ NavLink.vue
 ┃ ┃ ┃ ┣ PrimaryButton.vue
 ┃ ┃ ┃ ┣ ResponsiveNavLink.vue
 ┃ ┃ ┃ ┣ SecondaryButton.vue
 ┃ ┃ ┃ ┗ TextInput.vue
 ┃ ┃ ┣ Layouts
 ┃ ┃ ┃ ┣ AuthenticatedLayout.vue
 ┃ ┃ ┃ ┗ GuestLayout.vue
 ┃ ┃ ┣ lib
 ┃ ┃ ┃ ┗ utils.js
 ┃ ┃ ┣ Pages
 ┃ ┃ ┃ ┣ Auth
 ┃ ┃ ┃ ┃ ┣ ConfirmPassword.vue
 ┃ ┃ ┃ ┃ ┣ ForgotPassword.vue
 ┃ ┃ ┃ ┃ ┣ Login.vue
 ┃ ┃ ┃ ┃ ┣ Register.vue
 ┃ ┃ ┃ ┃ ┣ ResetPassword.vue
 ┃ ┃ ┃ ┃ ┗ VerifyEmail.vue
 ┃ ┃ ┃ ┣ Profile
 ┃ ┃ ┃ ┃ ┣ Partials
 ┃ ┃ ┃ ┃ ┃ ┣ DeleteUserForm.vue
 ┃ ┃ ┃ ┃ ┃ ┣ UpdatePasswordForm.vue
 ┃ ┃ ┃ ┃ ┃ ┗ UpdateProfileInformationForm.vue
 ┃ ┃ ┃ ┃ ┗ Edit.vue
 ┃ ┃ ┃ ┣ Dashboard.vue
 ┃ ┃ ┃ ┗ Welcome.vue
 ┃ ┃ ┣ app.js
 ┃ ┃ ┗ bootstrap.js
 ┃ ┗ views
 ┃ ┃ ┗ app.blade.php
 ┣ routes
 ┃ ┣ auth.php
 ┃ ┣ console.php
 ┃ ┗ web.php
 ┣ storage
 ┣ tests
 ┃ ┣ Feature
 ┃ ┃ ┣ Auth
 ┃ ┃ ┃ ┣ AuthenticationTest.php
 ┃ ┃ ┃ ┣ EmailVerificationTest.php
 ┃ ┃ ┃ ┣ PasswordConfirmationTest.php
 ┃ ┃ ┃ ┣ PasswordResetTest.php
 ┃ ┃ ┃ ┣ PasswordUpdateTest.php
 ┃ ┃ ┃ ┗ RegistrationTest.php
 ┃ ┃ ┣ ExampleTest.php
 ┃ ┃ ┗ ProfileTest.php
 ┃ ┣ Unit
 ┃ ┃ ┗ ExampleTest.php
 ┃ ┗ TestCase.php
 ┣ vendor
 ┣ .editorconfig
 ┣ .env
 ┣ .env.example
 ┣ .gitattributes
 ┣ .gitignore
 ┣ artisan
 ┣ components.json
 ┣ composer.json
 ┣ composer.lock
 ┣ jsconfig.json
 ┣ LICENSE
 ┣ package-lock.json
 ┣ package.json
 ┣ phpunit.xml
 ┣ postcss.config.js
 ┣ README.md
 ┣ SKILL.md
 ┣ tailwind.config.js
 ┗ vite.config.js
 ```

 ## Packages

 ```json

 {
    "$schema": "https://www.schemastore.org/package.json",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "@inertiajs/vue3": "^2.0.0",
        "@tailwindcss/forms": "^0.5.3",
        "@tailwindcss/vite": "^4.0.0",
        "@vitejs/plugin-vue": "^6.0.0",
        "autoprefixer": "^10.4.12",
        "axios": "^1.11.0",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^2.0.0",
        "postcss": "^8.4.31",
        "tailwindcss": "^3.2.1",
        "tw-animate-css": "^1.4.0",
        "vite": "^7.0.7",
        "vue": "^3.4.0"
    },
    "dependencies": {
        "@lucide/vue": "^1.17.0",
        "class-variance-authority": "^0.7.1",
        "clsx": "^2.1.1",
        "reka-ui": "^2.9.9",
        "tailwind-merge": "^3.6.0"
    }
}

```