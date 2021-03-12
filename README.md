# Google Translate middleware
A PHP CLI tool to automate translations for PIM translatable attributes through Google Translate API

## Technical requirements
PHP 7.3 (minimal) installed locally
Or
Docker

## 1.Installation

### 1.1 with PHP locally
run `PHP composer update`

### 1.2 trough Docker
Start the container
`docker-compose up -d`

Then run
`docker-compose exec fpm composer update`

## 2.Configuration

Before running the tool, you must set each environment variables listed inside the .env file situated at the root of the project

to begin, configure the PIM URL target and the related API connection information
```
PIM_URL=https://pim.url.com/
PIM_API_CLIENT_ID=your_pim_api_client_id
PIM_API_CLIENT_SECRET=your_pim_api_client_secret
PIM_API_USER=your_pim_api_username
PIM_API_PASSWORD=your_pim_api_password
```

Then you have to configure the locales source and destination for the translation
```
LOCALE_SOURCE=en_US
LOCALE_DESTINATION=fr_FR
```
Finally set the scope and the targeted attribute(s) separated by a comma
```
SCOPE_DESTINATION=ecommerce
TARGET_ATTRIBUTES=description,short_description
```

## 3. Run translator

### 3.1 with PHP locally
run `php translate.php`

### 3.2 trough Docker
Start the container if not yet started
`docker-compose up -d`

Then run
`docker-compose exec fpm php translate.php`



