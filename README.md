# Orders Management Application

Полноценное приложение для управления заказами на Symfony 7.x с интеграцией внешних сервисов, поиском и агрегацией данных.

## Архитектура

Приложение построено по принципам чистой архитектуры с разделением на слои:

- **Controller** → **Service** → **Repository** → **Entity/DTO**
- Использование SOLID принципов и Dependency Injection
- Паттерны: Repository, Factory, Adapter, Strategy, DTO

## Технологический стек

- **Backend**: Symfony 7.3, PHP 8.2+
- **База данных**: MySQL 8.0
- **Кеширование**: Redis
- **Поиск**: Manticore Search
- **HTTP клиент**: Guzzle
- **Парсинг HTML**: Symfony DomCrawler
- **Тестирование**: PHPUnit
- **Контейнеризация**: Docker & Docker Compose

## Требования

- Docker & Docker Compose
- Git

## Быстрый старт

1. Клонируйте репозиторий:
```bash
git clone <repository-url>
cd symfony-test3
```

2. Настройте переменные окружения:
```bash
cp env.example .env
# Отредактируйте .env при необходимости
```

3. Запустите приложение:

**На Linux/macOS:**
```bash
make dev
# или
make up
# или
docker-compose up --build -d
```

**На Windows:**
```cmd
# Используйте batch файл
windows-commands.bat dev
# или
windows-commands.bat up
# или
docker-compose -f docker-compose.windows.yml up --build -d
```

4. Выполните миграции (если не использовали make dev / windows-commands.bat dev):

**На Linux/macOS:**
```bash
docker-compose exec app bin/console doctrine:migrations:migrate
```

**На Windows:**
```cmd
docker-compose -f docker-compose.windows.yml exec app bin/console doctrine:migrations:migrate
```

5. Заполните индекс Manticore (если не использовали make dev / windows-commands.bat dev):

**На Linux/macOS:**
```bash
docker-compose exec app bin/console app:seed-manticore
```

**На Windows:**
```cmd
docker-compose -f docker-compose.windows.yml exec app bin/console app:seed-manticore
```

6. Проверьте работоспособность:

**На Linux/macOS:**
```bash
make check-health
```

**На Windows:**
```cmd
windows-commands.bat check-health
```

## API Endpoints

### 1. Получение цены товара
```bash
curl "http://localhost:8080/api/price?factory=cobsa&collection=manual&article=manu7530bcbm-manualbaltic7-5x30"
```

### 2. Агрегация заказов
```bash
curl "http://localhost:8080/api/orders/aggregate?group_by=month&page=1&per_page=20"
```

### 3. Создание заказа через SOAP
```bash
curl -X POST "http://localhost:8080/api/soap/orders" \
  -H "Content-Type: text/xml" \
  -d '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
      <createOrder>
        <client_name>John</client_name>
        <client_surname>Doe</client_surname>
        <email>john@example.com</email>
        <items>
          <item>
            <article>test-article</article>
            <amount>10</amount>
          </item>
        </items>
      </createOrder>
    </soap:Body>
  </soap:Envelope>'
```

### 4. Получение заказа
```bash
curl "http://localhost:8080/api/orders/1"
# или по hash
curl "http://localhost:8080/api/orders/abc123?by=hash"
```

### 5. Поиск заказов
```bash
curl "http://localhost:8080/api/orders/search?q=john*&page=1&per_page=20"
```

## Тестирование

Запуск всех тестов:
```bash
make test
# или
docker-compose exec app ./vendor/bin/phpunit
```

Запуск только unit тестов:
```bash
docker-compose exec app ./vendor/bin/phpunit tests/Unit
```

Запуск только functional тестов:
```bash
docker-compose exec app ./vendor/bin/phpunit tests/Functional
```

## Документация API

Swagger документация доступна по адресу: `http://localhost:8080/api/docs`

## Структура базы данных

Основные таблицы:
- `orders` - заказы
- `orders_article` - позиции заказов

Подробная схема в файле `db/dump/improved_schema.sql`

## Команды

### Linux/macOS (Makefile)

#### Основные команды
- `make up` - запуск всех сервисов
- `make down` - остановка всех сервисов
- `make dev` - запуск development окружения с автоматической настройкой
- `make test` - запуск тестов
- `make build` - пересборка контейнеров
- `make logs` - просмотр логов
- `make shell` - доступ к контейнеру приложения

#### Команды качества кода
- `make cs-fix` - исправить стиль кода с помощью PHP CS Fixer
- `make cs-check` - проверить стиль кода (dry-run)

#### Команды для разработки
- `make migrate` - выполнить миграции БД
- `make seed-manticore` - заполнить индекс Manticore
- `make cache-clear` - очистить кеш приложения
- `make install` - установить зависимости

#### Команды для продакшна
- `make prod-build` - сборка production образов
- `make prod-up` - запуск production окружения
- `make prod-down` - остановка production окружения

#### Утилиты
- `make backup-db` - создать резервную копию БД
- `make restore-db BACKUP=file.sql` - восстановить БД из резервной копии
- `make check-health` - проверить здоровье всех сервисов
- `make clean` - очистить контейнеры и volumes

### Windows (Batch файл)

#### Основные команды
- `windows-commands.bat up` - запуск всех сервисов
- `windows-commands.bat down` - остановка всех сервисов
- `windows-commands.bat dev` - запуск development окружения с автоматической настройкой
- `windows-commands.bat test` - запуск тестов
- `windows-commands.bat build` - пересборка контейнеров
- `windows-commands.bat logs` - просмотр логов
- `windows-commands.bat shell` - доступ к контейнеру приложения

#### Команды качества кода
- `windows-commands.bat cs-fix` - исправить стиль кода с помощью PHP CS Fixer
- `windows-commands.bat cs-check` - проверить стиль кода (dry-run)

#### Команды для разработки
- `windows-commands.bat migrate` - выполнить миграции БД
- `windows-commands.bat seed-manticore` - заполнить индекс Manticore
- `windows-commands.bat cache-clear` - очистить кеш приложения

#### Утилиты
- `windows-commands.bat check-health` - проверить здоровье всех сервисов
- `windows-commands.bat help` - показать справку по командам

## Разработка

### Структура проекта

```
src/
├── Controller/          # HTTP контроллеры
├── Service/            # Бизнес-логика
├── Repository/         # Доступ к данным
├── Entity/             # Doctrine сущности
├── DTO/               # Data Transfer Objects
├── Interface/         # Интерфейсы
└── Exception/         # Кастомные исключения

tests/
├── Unit/              # Unit тесты
├── Functional/        # Functional тесты
└── Fixtures/          # Тестовые данные

docker/
├── nginx/             # Конфигурация Nginx
├── php/               # Конфигурация PHP
└── manticore/         # Конфигурация Manticore

db/
├── dump/              # SQL дампы
└── migrations/        # Doctrine миграции
```

### Добавление новых функций

1. Создайте Entity/DTO
2. Добавьте Repository с методами доступа к данным
3. Реализуйте Service с бизнес-логикой
4. Создайте Controller для HTTP endpoints
5. Напишите тесты
6. Обновите документацию

## Мониторинг и логи

Логи приложения:
```bash
docker-compose logs -f app
```

Логи базы данных:
```bash
docker-compose logs -f db
```

## Производительность

- Кеширование ответов скрапинга в Redis
- Оптимизированные индексы в MySQL
- Полнотекстовый поиск через Manticore
- Пагинация для всех списков

## Безопасность

- Валидация всех входных данных
- Защита от SQL инъекций через Doctrine
- Rate limiting для внешних API
- Безопасная обработка ошибок

## Деплой

### Development окружение
```bash
make dev
```

### Production окружение
```bash
# Создайте production конфигурацию
cp env.example .env.prod

# Обновите переменные для production
# APP_ENV=prod
# APP_SECRET=your-production-secret
# DATABASE_URL=mysql://user:password@prod-db:3306/orders_db

# Запустите production окружение
make prod-build
make prod-up
```

### Требования для production
1. Обновите переменные окружения в `.env.prod`
2. Настройте SSL сертификаты
3. Оптимизируйте конфигурацию PHP
4. Настройте мониторинг
5. Создайте резервные копии БД
6. Настройте CI/CD pipeline

## Качество кода

Проект использует следующие инструменты для обеспечения качества кода:

- **PHP CS Fixer** - автоматическое исправление стиля кода
- **PHPUnit** - тестирование
- **PHPStan** - статический анализ (опционально)

```bash
# Проверить стиль кода
make cs-check

# Исправить стиль кода
make cs-fix

# Запустить тесты
make test
```

## Архитектура

### Слои приложения
1. **Controller** - HTTP контроллеры, валидация запросов
2. **Service** - бизнес-логика, оркестрация
3. **Repository** - доступ к данным, запросы к БД
4. **Entity** - модели данных, маппинг БД
5. **DTO** - объекты передачи данных
6. **Interface** - контракты сервисов

### Паттерны проектирования
- **Repository Pattern** - абстракция доступа к данным
- **Factory Pattern** - создание объектов
- **Adapter Pattern** - адаптация внешних сервисов
- **Strategy Pattern** - различные стратегии агрегации
- **DTO Pattern** - передача данных между слоями

## Мониторинг и логи

### Логи
```bash
# Логи приложения
make logs

# Логи конкретного сервиса
docker-compose logs -f app
docker-compose logs -f db
docker-compose logs -f redis
docker-compose logs -f manticore
```

### Health Check
```bash
# Проверить здоровье всех сервисов
make check-health

# Проверить статус контейнеров
docker-compose ps
```

## Troubleshooting

### Частые проблемы

1. **Порты заняты**
   ```bash
   # Измените порты в .env файле
   APP_PORT=8081
   DB_PORT=3307
   ```

2. **Проблемы с БД**
   ```bash
   # Пересоздать БД
   make down
   docker volume rm symfony-test3_db_data
   make up
   ```

3. **Проблемы с кешем**
   ```bash
   make cache-clear
   ```

4. **Проблемы с Manticore**
   ```bash
   # Пересоздать индекс
   make seed-manticore
   ```

## Лицензия

Proprietary
