# Chain Store Api

A robust e-commerce backend system built with Laravel, designed for managing chain stores and online retail operations.
This platform provides comprehensive product management, user reviews, content publishing, and customer support features.

## Table of Contents

-   [Features](#features)
-   [Product Structure & DDD](#product-structure--ddd)
-   [Tech Stack](#tech-stack)
-   [How to Run](#how-to-run)
-   [Contributing](#contributing)
-   [License](#license)

---

## Features

-   **Product Management**: Complete product catalog with brands, categories, colors, and pricing
-   **User Reviews & Ratings**: Customers can review products and rate their experiences
-   **Content Management**: Blog posts with likes and popularity tracking
-   **User Profiles**: Customer accounts with favorites and activity tracking
-   **Support System**: Ticket-based customer support with subjects and messaging
-   **Notifications**: Real-time user notifications system
-   **File Management**: Image, video, and file upload capabilities
-   **Banner Management**: Promotional banners for marketing campaigns
-   **Modular Architecture**: Built using Domain-Driven Design (DDD) principles for scalability and maintainability

---

## Product Structure & DDD

This product is architected using **Domain-Driven Design (DDD)** principles, which means the codebase is organized around the core product domains and their logic. Here's how the structure is laid out:

```
src/
├── Domain/
│   ├── Brand/
│   │   ├── Models/
│   │   │   └── Brand.php
│   │   ├── Repositories/
│   │   │   ├── BrandRepository.php
│   │   │   └── Contracts/
│   │   │       └── IBrandRepository.php
│   ├── Product/
│   │   ├── Models/
│   │   │   ├── Product.php
│   │   │   ├── Category.php
│   │   │   └── Color.php
│   │   └── Repositories/
│   ├── Review/
│   │   ├── Models/
│   │   └── Repositories/
│   ├── User/
│   ├── Ticket/
│   ├── Post/
│   ├── Notification/
│   └── File/
│
├── Application/
│   ├── Api/
│   │   ├── Brand/
│   │   │   ├── Controllers/
│   │   │   ├── Requests/
│   │   │   └── Resources/
│   │   ├── Product/
│   │   ├── Review/
│   │   ├── User/
│   │   ├── Ticket/
│   │   ├── Post/
│   │   ├── Notification/
│   │   └── File/
│   └── Application.php
│
├── Core/
│   ├── Http/
│   ├── Providers/
│   ├── Exceptions/
│   └── Console/
│
└── Support/
```

### DDD Layers Explained

-   **Domain Layer (`src/Domain/`)**:  
    Contains the heart of the product logic. Each subdomain (e.g., Review, User, Product) has its own folder, with:

    -   `Models/`: Eloquent models representing domain entities.
    -   `Repositories/`: Data access logic, often split into `Contracts/` (interfaces) and concrete implementations.

-   **Application Layer (`src/Application/`)**:  
    Coordinates application activities. The `Api/` directory contains controllers, requests, and resources for each subdomain, handling HTTP requests and responses.

-   **Core Layer (`src/Core/`)**:  
    Contains shared infrastructure, such as HTTP handling, service providers, exceptions, and console commands.

-   **Support Layer (`src/Support/`)**:  
    (If used) Contains helpers, utilities, or cross-cutting concerns.

### Example: Brand Subdomain

-   `Domain/Brand/Models/Brand.php`: The Brand entity with relationships to products, colors, and banners.
-   `Domain/Brand/Repositories/Contracts/IBrandRepository.php`: The repository interface for brands.
-   `Domain/Brand/Repositories/BrandRepository.php`: The concrete implementation of the brand repository.
-   `Application/Api/Brand/Controllers/BrandController.php`: Handles HTTP requests for brands.
-   `Application/Api/Brand/Resources/BrandResource.php`: Transforms brand data for API responses.

### Key Domains

-   **Brand**: Manages product brands and their associated colors and banners
-   **Product**: Handles products, categories, colors, stock, and pricing
-   **Review**: Customer reviews and ratings for products
-   **User**: User authentication, profiles, and account management
-   **Ticket**: Customer support ticketing system with subjects and messages
-   **Post**: Content management for blog posts with likes and engagement
-   **Notification**: User notification system
-   **File**: File upload and management (images, videos, documents)

---

## Tech Stack

-   **Framework**: Laravel 11.x
-   **Admin Panel**: Filament 3.x
-   **Authentication**: Laravel Sanctum
-   **Database**: SQLite (development), supports MySQL/PostgreSQL
-   **Image Processing**: Intervention Image
-   **Architecture**: Domain-Driven Design (DDD)
-   **API**: RESTful API with resource transformers

---

## How to Run

1. **Clone the repository:**

    ```bash
    git clone https://github.com/yourusername/chain-store-backend.git
    cd chain-store-backend
    ```

2. **Install dependencies:**

    ```bash
    composer install
    ```

3. **Set up your environment:**

    - Copy `.env.example` to `.env` and configure your database and other settings.

    ```bash
    cp .env.example .env
    ```

4. **Generate application key:**

    ```bash
    php artisan key:generate
    ```

5. **Run migrations:**

    ```bash
    php artisan migrate
    ```

6. **Seed the database (optional):**

    ```bash
    php artisan db:seed
    ```

7. **Create storage link:**

    ```bash
    php artisan storage:link
    ```

8. **Start the development server:**
    ```bash
    php artisan serve
    ```

The API will be available at `http://localhost:8000`

---

## Contributing

Contributions are welcome! Please open issues or submit pull requests for any improvements or bug fixes.

---

## License

This product is open-source and available under the [MIT License](LICENSE).
