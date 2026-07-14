<p align="center">
  <h1 align="center">RFPMW — CRM</h1>
  <p align="center">Система управления контрагентами.</p>
  <p align="center">
    <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white" alt="PHP 8.3+">
    <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
    <img src="https://img.shields.io/badge/AdminLTE-3-00A65A?logo=adminlte&logoColor=white" alt="AdminLTE 3">
  </p>
</p>

---

## 📖 О проекте

**RFPMW** — внутренняя CRM для ведения базы контрагентов, контактных лиц и управления
воронкой продаж: от проекта и запроса до коммерческого предложения. Система ведёт журнал
взаимодействий с сотрудниками контрагентов и автоматически считает суммы по позициям.

Интерфейс построен на **AdminLTE 3** и адаптирован под русскую локаль. Доступ разграничен по
ролям: менеджер работает только со «своими» контрагентами, администратор видит и может
редактировать всё.

---

## 🛠 Стек технологий

| Слой | Технология |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | AdminLTE 3 (Bootstrap 5), Blade |
| База данных | SQLite (по умолчанию) / MySQL |
| Аутентификация | laravel/ui (вход по **username**, не по email) |
| Авторизация (RBAC) | собственные роли `admin` / `manager` (без spatie) |
| Интеграции | DaData (реквизиты контрагента по ИНН) |
| Код-стайл | Laravel Pint |
| Тесты | PHPUnit 12 |

---

## ⚙️ Требования

- PHP **8.3+**
- Composer

---

## 🚀 Быстрый старт

```bash
# 1. Установить зависимости
composer install

# 2. Подготовить окружение
cp .env.example .env
php artisan key:generate

# 3. Применить миграции
php artisan migrate

# 4. Запустить тестовый сервер и наполнить тестовыми данными (только для разработки!)
php artisan serve
php artisan db:seed
```

Откройте приложение по адресу из `APP_URL`.

### Учётные записи

**Разработка:** после `php artisan db:seed` создаются тестовые учётки `admin`/`111111` и `manager`/`222222`.

**Продакшен:** создайте администратора интерактивно:

```bash
php artisan app:create-admin
```
Команда запрашивает имя, логин и пароль (минимум 8 символов) и создаёт пользователя с ролью `admin`. Требует, чтобы роль уже существовала в БД (создаётся сидером ролей).

---

## 🔧 Конфигурация

Основные переменные окружения (`.env`):

| Переменная | Описание |
|---|---|
| `APP_LOCALE` | Локаль интерфейса (`ru`). |
| `DB_CONNECTION` | `sqlite` (по умолчанию) или `mysql`. |
| `DADATA_API_KEY` | Ключ API DaData для автозаполнения реквизитов по ИНН. |
| `DADATA_URL` | Endpoint DaData (задан по умолчанию). |

### База данных (MySQL)

По умолчанию используется SQLite (ничего настраивать не нужно). Чтобы запустить проект
на **MySQL**, сначала создайте пустую базу данных — Laravel создаёт таблицы, но не саму БД:

```sql
-- в консоли MySQL или в phpMyAdmin
CREATE DATABASE rfpmw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Затем пропишите в `.env` параметры подключения:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1      # адрес сервера БД (локально — 127.0.0.1)
DB_PORT=3306           # порт MySQL (по умолчанию 3306)
DB_DATABASE=rfpmw      # имя созданной базы данных
DB_USERNAME=root       # пользователь MySQL
DB_PASSWORD=           # пароль пользователя (пусто, если без пароля)
```

После этого примените миграции:

```bash
php artisan migrate
```

---

## 🧩 Архитектура

- **Авторизация и доступ (RBAC):** роли реализованы на собственных моделях `Role` + `User`
  (`isAdmin()` / `isManager()`), доступ к роутам регулируется middleware `can:is-admin`.
  Роли **не** используют пакет spatie.
- **Data scoping:** админ видит все записи, менеджер — только те, что привязаны к закреплённым за
  ним контрагентам. Фильтрация выполняется в контроллерах.
- **Слои:** контроллеры (`app/Http/Controllers`) + модели Eloquent (`app/Models`) + FormRequest
  для валидации.
- **UI:** шаблоны Blade поверх AdminLTE 3 (Bootstrap 5). Промежуточный layout `layouts.admin`
  (`adminlte::page`) расширяется всеми страницами CRM.

### Ключевые модели

| Модель | Назначение |
|---|---|
| `User` / `Role` | Пользователь системы и его роль (1 = Менеджер, 2 = Администратор). |
| `Contractor` | Контрагент (юридическое лицо), закреплён за менеджером. |
| `ContactPerson` | Контактное лицо (разделяемая сущность). |
| `EmployedPerson` | Сотрудник контрагента (привязка контактного лица к контрагенту). |
| `Project` / `ProjectItem` | Проект и его позиции. |
| `Request` / `RequestItem` | Запрос и его позиции. |
| `Proposal` / `ProposalItem` | Коммерческое предложение и его позиции. |
| `Event` | Событие взаимодействия (звонок / письмо / встреча). |
| `Item` | Артикул (элемент справочника товаров/услуг). |

---

## ✅ Тесты

```bash
php artisan test --compact                              # все тесты
php artisan test --compact tests/Feature/ExampleTest.php # один файл
php artisan test --compact --filter=testName            # по имени
```
