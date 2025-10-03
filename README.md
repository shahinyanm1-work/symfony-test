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
cp .env.example .env
# Отредактируйте .env при необходимости
```

3. Запустите приложение:
```bash
make up
# или
docker-compose up --build -d
```

4. Выполните миграции:
```bash
docker-compose exec app bin/console doctrine:migrations:migrate
```

5. Заполните индекс Manticore:
```bash
docker-compose exec app bin/console app:seed-manticore
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

## Команды Makefile

- `make up` - запуск всех сервисов
- `make down` - остановка всех сервисов
- `make test` - запуск тестов
- `make build` - пересборка контейнеров
- `make logs` - просмотр логов
- `make shell` - доступ к контейнеру приложения

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

Для продакшн деплоя:

1. Обновите переменные окружения
2. Настройте SSL сертификаты
3. Оптимизируйте конфигурацию PHP
4. Настройте мониторинг
5. Создайте резервные копии БД

## Лицензия

Proprietary
