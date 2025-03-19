<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Laravel Project Setup Guide for multi-tenant e-commerce

This guide will walk you through the steps to set up and run the Laravel project locally.

---

## **Prerequisites**

Before you begin, ensure you have the following installed on your system:

- PHP (>= 8.0)
- Composer
- MySQL or any other database supported by Laravel
- Git

---

## **Step 1: Clone the Repository**

Clone the project repository to your local machine:

```bash
git clone https://github.com/YamenAbbas422/TechTask.git
cd TechTask
```
## **Step 2: Install Dependencies**
Install PHP dependencies using Composer:
```bash
composer install
```
---

## **Step 3: Configure Environment**
Copy the .env.example file to .env:

```bash
cp .env.example .env
```
Open the .env file and update the following values:

Database credentials:

```bash
DB_DATABASE=your_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```
Generate an application key:

```bash
php artisan key:generate
```
---

## **Step 4: Run Migrations**
Run the database migrations to create the necessary tables:

```bash
php artisan migrate
```
---
## **Step 5: Install Laravel Passport**
Run the Passport migrations:
```bash
php artisan migrate
```
Generate encryption keys for Passport:
```bash
php artisan passport:install
```
---
## **Step 6: Run the Project**
Start the Laravel development server:

```bash
php artisan serve
```
Access the project in your browser:
``` bash
http://localhost:8000
```
