# DeepL Translate middleware
A PHP CLI tool to automate translations for PIM translatable attributes through DeepL Translate API

## Technical requirements

PHP 7.3 (minimal) installed locally

Or

Docker, see here for installing it on your computer ➡️ https://docs.docker.com/desktop/#download-and-install

## 1.Installation

open a command line shell.

Clone this repository : `git clone git@github.com:akeneo-presales/deepl_translate_middleware.git`

then go inside the project folder : `cd google_translate_middleware`

### 1.1 install the dependencies with PHP locally
run `php composer update`

### 1.2 install the dependencies trough Docker
Start the container
`docker-compose up -d`

Then run
`docker-compose exec php composer update`

## 2.Configuration

### 2.1 Environment variables Configuration

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
Next you can set the categories  (separated by a comma - optionnal parameter), the source and destinations scopes (could be the same for each of them) and the targeted attribute(s) (separated by a comma)
```
CATEGORIES_SOURCE=
SCOPE_SOURCE=ecommerce
SCOPE_DESTINATION=ecommerce
TARGET_ATTRIBUTES=description,short_description
```
Finnaly you have to set your DeepL API Key 
```
DEEPL_API_KEY=9999999999999
```

### 2.2 DeepL Translate API Configuration

You need an authentication key to access to the API.
You can find your key in your account settings https://www.deepl.com/pro-account. It is important to keep your key confidential. You should not put the key in Javascript code distributed publicly.

If your authentication key becomes compromised, you can recreate a new key and discard the old one in your account settings https://www.deepl.com/pro-account.

## 3. Run the translator tool

### 3.1 with PHP locally
run `php translate.php`

### 3.2 trough Docker
Start the container if not yet started
`docker-compose up -d`

Then run
`docker-compose exec php php translate.php`



